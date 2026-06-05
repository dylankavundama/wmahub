<?php
require_once __DIR__ . '/../includes/config.php';

try {
    $db = getDBConnection();
    
    // Check if column exists
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'contract_signature'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        $db->exec("ALTER TABLE users ADD COLUMN contract_signature LONGTEXT NULL AFTER is_certified");
        echo "Column 'contract_signature' successfully added to 'users' table!\n";
    } else {
        echo "Column 'contract_signature' already exists in 'users' table.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
