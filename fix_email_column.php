<?php
require_once __DIR__ . '/includes/config.php';

try {
    $db = getDBConnection();
    
    // Check if column already exists
    $res = $db->query("SHOW COLUMNS FROM `projects` LIKE 'email'");
    if (!$res->fetch()) {
        echo "Adding 'email' column to 'projects' table...\n";
        $db->exec("ALTER TABLE `projects` ADD COLUMN `email` VARCHAR(255) AFTER `full_name` ");
        echo "Column 'email' added successfully!\n";
    } else {
        echo "Column 'email' already exists.\n";
    }
    
    // Redirect to submit.php or show success message
    echo "<br><br><a href='dashboards/artiste/submit.php'>Retourner à la soumission</a>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
