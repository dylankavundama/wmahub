<?php
require_once __DIR__ . '/includes/config.php';

/**
 * Script de migration pour ajouter les colonnes 'revenue' et 'is_paid' à la table 'tasks'.
 */

try {
    // Tentative de connexion forcée sur 127.0.0.1
    $dsn = "mysql:host=127.0.0.1;dbname=wmahub;charset=utf8mb4";
    $db = new PDO($dsn, 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "Connexion réussie à la base de données !\n";

    // Vérification de la colonne 'revenue'
    $result = $db->query("SHOW COLUMNS FROM `tasks` LIKE 'revenue'");
    if (!$result->fetch()) {
        $db->exec("ALTER TABLE `tasks` ADD COLUMN `revenue` DECIMAL(10,2) DEFAULT 0.00 AFTER `image_path`;");
        echo "Colonne 'revenue' ajoutée à la table 'tasks'.\n";
    } else {
        echo "La colonne 'revenue' existe déjà.\n";
    }

    // Vérification de la colonne 'is_paid'
    $result = $db->query("SHOW COLUMNS FROM `tasks` LIKE 'is_paid'");
    if (!$result->fetch()) {
        $db->exec("ALTER TABLE `tasks` ADD COLUMN `is_paid` TINYINT(1) DEFAULT 0 AFTER `status`;");
        echo "Colonne 'is_paid' ajoutée à la table 'tasks'.\n";
    } else {
        echo "La colonne 'is_paid' existe déjà.\n";
    }

    echo "\nMigration terminée avec succès !\n";

} catch (Exception $e) {
    die("\nERREUR lors de la migration : " . $e->getMessage() . "\n");
}
?>
