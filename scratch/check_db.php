<?php
require_once __DIR__ . '/../includes/config.php';
try {
    $db = getDBConnection();
    echo "--- notifications schema ---\n";
    $stmt = $db->query("DESCRIBE notifications");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

    echo "\n--- users contract_signature column check ---\n";
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'contract_signature'");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
