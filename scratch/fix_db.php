<?php
/**
 * Fix rapide : rendre notifications.reference_id nullable
 * et vérifier la présence de contract_signature dans users
 */
require_once __DIR__ . '/../includes/config.php';

try {
    $db = getDBConnection();
    
    // 1. Rendre reference_id nullable dans notifications
    $stmt = $db->query("SELECT IS_NULLABLE FROM information_schema.COLUMNS 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = 'notifications' 
                        AND COLUMN_NAME = 'reference_id'");
    $nullable = $stmt->fetchColumn();
    
    if ($nullable !== 'YES') {
        $db->exec("ALTER TABLE `notifications` MODIFY `reference_id` INT(11) NULL DEFAULT NULL;");
        echo "[OK] notifications.reference_id est maintenant nullable.\n";
    } else {
        echo "[INFO] notifications.reference_id est déjà nullable.\n";
    }
    
    // 2. Vérifier contract_signature dans users
    $stmt2 = $db->query("SHOW COLUMNS FROM users LIKE 'contract_signature'");
    if (!$stmt2->fetch()) {
        $db->exec("ALTER TABLE `users` ADD COLUMN `contract_signature` LONGTEXT NULL DEFAULT NULL AFTER `is_certified`;");
        echo "[OK] users.contract_signature ajouté.\n";
    } else {
        echo "[INFO] users.contract_signature existe déjà.\n";
    }
    
    echo "\nBase de données locale à jour !\n";
    
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}
