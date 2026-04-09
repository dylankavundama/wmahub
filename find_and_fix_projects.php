<?php
$dbs = ['wmahubco_millan', 'wmaplus', 'wmahub', 'quincatech', 'usafi'];
foreach ($dbs as $dbname) {
    try {
        $db = new PDO("mysql:host=localhost;dbname=$dbname", "root", "");
        $res = $db->query("SHOW TABLES LIKE 'projects'");
        if ($res->fetch()) {
            echo "Found 'projects' in $dbname\n";
            // Check if full_name column is missing
            $col = $db->query("SHOW COLUMNS FROM `projects` LIKE 'full_name'");
            if (!$col->fetch()) {
                $db->exec("ALTER TABLE `projects` ADD COLUMN `full_name` VARCHAR(255) AFTER `artist_name` ");
                echo "Column 'full_name' added to $dbname.projects\n";
            } else {
                echo "Column 'full_name' already exists in $dbname.projects\n";
            }
        }
    } catch (Exception $e) {}
}
?>
