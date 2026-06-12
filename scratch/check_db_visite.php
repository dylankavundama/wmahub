<?php
require_once __DIR__ . '/../includes/config.php';
try {
    $db = getDBConnection();
    $stmt = $db->query("SELECT * FROM visite_ua");
    print_r($stmt->fetchAll());
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
