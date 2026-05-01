<?php
require_once __DIR__ . '/../includes/config.php';
try {
    $db = getDBConnection();
    $db->exec("ALTER TABLE users ADD COLUMN apple_id VARCHAR(255) DEFAULT NULL AFTER google_id");
    echo "Column apple_id added successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
