<?php
require_once __DIR__ . '/includes/config.php';

/**
 * Script de migration pour synchroniser la base de données en ligne.
 * Ce script ajoute les tables et colonnes manquantes identifiées après analyse.
 */

try {
    $db = getDBConnection();
    echo "Démarrage de la migration...\n";

    // 1. Création de la table 'withdrawals' (si elle n'existe pas)
    echo "Vérification de la table 'withdrawals'...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS `withdrawals` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `amount` decimal(10,2) NOT NULL,
        `method` varchar(50) NOT NULL,
        `account_details` text NOT NULL,
        `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        `proof_file` varchar(255) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `processed_at` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        CONSTRAINT `withdrawals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    echo "Table 'withdrawals' prête.\n";

    // 2. Extension de l'ENUM 'type' dans la table 'notifications'
    echo "Mise à jour des types de notifications...\n";
    // On récupère les colonnes actuelles pour vérifier si on doit modifier
    $db->exec("ALTER TABLE `notifications` MODIFY COLUMN `type` ENUM('projet','tache','message','project_update','payment_received','payout_update','revenue_update') NOT NULL;");
    echo "Types de notifications mis à jour.\n";

    // 3. Vérification des colonnes 'streams' et 'revenue' dans 'projects' (au cas où)
    echo "Vérification des colonnes statistiques dans 'projects'...\n";
    $result = $db->query("SHOW COLUMNS FROM `projects` LIKE 'streams'");
    if (!$result->fetch()) {
        $db->exec("ALTER TABLE `projects` ADD COLUMN `streams` BIGINT DEFAULT 0;");
        echo "Colonne 'streams' ajoutée.\n";
    }
    
    $result = $db->query("SHOW COLUMNS FROM `projects` LIKE 'revenue'");
    if (!$result->fetch()) {
        $db->exec("ALTER TABLE `projects` ADD COLUMN `revenue` DECIMAL(10,2) DEFAULT '0.00';");
        echo "Colonne 'revenue' ajoutée.\n";
    }

    // 4. Peupler les paramètres par défaut dans 'site_settings'
    echo "Initialisation des paramètres système...\n";
    $default_settings = [
        ['pack_starter_usd', '15', 'Tarification', 'Prix du Pack Starter (USD)'],
        ['pack_pro_usd', '35', 'Tarification', 'Prix du Pack Pro (USD)'],
        ['pack_premium_usd', '75', 'Tarification', 'Prix du Pack Premium (USD)'],
        ['exchange_rate', '2800', 'Finances', 'Taux de change (1 USD = X CDF)'],
        ['card_fee_usd', '1', 'Services', 'Frais Carte Service (USD)'],
        ['card_fee_cdf', '3000', 'Services', 'Frais Carte Service (CDF)'],
        ['cert_fee_usd', '1', 'Services', 'Frais Certification (USD)'],
        ['cert_fee_cdf', '2800', 'Services', 'Frais Certification (CDF)'],
        ['dist_sub_monthly_usd', '25', 'Abonnements', 'Abo Mensuel Distributeur (USD)'],
        ['dist_sub_monthly_cdf', '70000', 'Abonnements', 'Abo Mensuel Distributeur (CDF)'],
        ['dist_sub_annual_usd', '220', 'Abonnements', 'Abo Annuel Distributeur (USD)'],
        ['dist_sub_annual_cdf', '616000', 'Abonnements', 'Abo Annuel Distributeur (CDF)'],
        ['sub_monthly_usd', '5', 'Abonnements', 'Abo Mensuel Artiste (USD)'],
        ['sub_monthly_cdf', '11000', 'Abonnements', 'Abo Mensuel Artiste (CDF)'],
        ['sub_annual_usd', '40', 'Abonnements', 'Abo Annuel Artiste (USD)'],
        ['sub_annual_cdf', '100000', 'Abonnements', 'Abo Annuel Artiste (CDF)']
    ];

    $stmt_check = $db->prepare("SELECT id FROM site_settings WHERE setting_key = ?");
    $stmt_insert = $db->prepare("INSERT INTO site_settings (setting_key, setting_value, category, description) VALUES (?, ?, ?, ?)");

    foreach ($default_settings as $s) {
        $stmt_check->execute([$s[0]]);
        if (!$stmt_check->fetch()) {
            $stmt_insert->execute($s);
            echo "Paramètre '{$s[0]}' initialisé.\n";
        }
    }
    echo "Paramètres système vérifiés.\n";

    // 5. Vérification de la table 'payments'
    echo "Vérification de la table 'payments'...\n";
    $result = $db->query("SHOW COLUMNS FROM `payments` LIKE 'order_number'");
    if (!$result->fetch()) {
        $db->exec("ALTER TABLE `payments` ADD COLUMN `order_number` VARCHAR(100) NOT NULL AFTER `reference`;");
        echo "Colonne 'order_number' ajoutée à 'payments'.\n";
    }
    // Vérifier l'ENUM status
    $db->exec("ALTER TABLE `payments` MODIFY COLUMN `status` ENUM('pending','success','failed') DEFAULT 'pending';");
    echo "ENUM status vérifié pour 'payments'.\n";

    // 6. Vérification de la table 'subscriptions'
    echo "Vérification de la table 'subscriptions'...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS `subscriptions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `plan_type` enum('monthly','annual') NOT NULL,
        `amount` decimal(10,2) NOT NULL,
        `currency` varchar(3) NOT NULL DEFAULT 'USD',
        `start_date` date NOT NULL,
        `end_date` date NOT NULL,
        `status` enum('active','expired','cancelled') NOT NULL DEFAULT 'active',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    echo "Table 'subscriptions' vérifiée.\n";

    // 7. Vérification de la table 'service_cards'
    echo "Vérification de la table 'service_cards'...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS `service_cards` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `card_type` varchar(50) NOT NULL,
        `status` enum('pending_payment','pending','active','rejected') NOT NULL DEFAULT 'pending_payment',
        `request_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `processed_date` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    echo "Table 'service_cards' vérifiée.\n";

    // 8. Vérification de la colonne 'is_certified' dans 'users'
    echo "Vérification de la colonne 'is_certified' dans 'users'...\n";
    $result = $db->query("SHOW COLUMNS FROM `users` LIKE 'is_certified'");
    if (!$result->fetch()) {
        $db->exec("ALTER TABLE `users` ADD COLUMN `is_certified` TINYINT(1) DEFAULT 0;");
        echo "Colonne 'is_certified' ajoutée à 'users'.\n";
    }
    echo "Table 'users' vérifiée.\n";

    echo "\nMigration terminée avec succès !\n";

} catch (Exception $e) {
    die("\nERREUR lors de la migration : " . $e->getMessage() . "\n");
}
?>
