<?php
require_once __DIR__ . '/../includes/config.php';
try {
    $db = getDBConnection();
    echo "Connection successful!\n";
    $userId = 1120;
    $db->exec("DELETE FROM ua_artists WHERE user_id = {$userId}");
    echo "Cleaned up test record for Aiglon Makasi.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
