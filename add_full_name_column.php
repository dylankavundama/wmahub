<?php
require_once 'includes/config.php';
try {
    $db = getDBConnection();
    // Test if column already exists
    $res = $db->query("SHOW COLUMNS FROM `projects` LIKE 'full_name'");
    if (!$res->fetch()) {
        $db->exec("ALTER TABLE `projects` ADD COLUMN `full_name` VARCHAR(255) AFTER `artist_name` ");
        echo "Column 'full_name' added successfully.";
    } else {
        echo "Column 'full_name' already exists.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
