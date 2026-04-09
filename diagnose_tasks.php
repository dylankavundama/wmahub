<?php
require_once 'includes/config.php';
$dbs = ['wmahubco_millan', 'wmaplus', 'wmahub', 'quincatech', 'usafi', 'wmahubco_hub'];
foreach ($dbs as $dbname) {
    try {
        $db = new PDO("mysql:host=localhost;dbname=$dbname", "root", "");
        echo "Database: $dbname\n";
        
        $tables = $db->query("SHOW TABLES LIKE 'tasks'");
        if ($tables->fetch()) {
            echo "  Found 'tasks' table.\n";
            $cols = $db->query("DESCRIBE tasks");
            while ($row = $cols->fetch(PDO::FETCH_ASSOC)) {
                echo "    " . $row['Field'] . " (" . $row['Type'] . ")\n";
            }
        }
    } catch (Exception $e) {}
}
?>
