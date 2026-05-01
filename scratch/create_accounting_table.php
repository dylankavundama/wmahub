<?php
require_once __DIR__ . '/../includes/config.php';
try {
    $db = getDBConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS accounting_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type ENUM('income', 'expense') NOT NULL,
        category VARCHAR(100) NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        description TEXT,
        transaction_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $db->exec($sql);
    echo "Table accounting_transactions créée avec succès.";
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}
