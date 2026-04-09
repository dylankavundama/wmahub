<?php
/**
 * Diagnose database for projects and expenses tables.
 */
require_once 'includes/config.php';
$dbs = ['wmahubco_millan', 'wmaplus', 'wmahub', 'quincatech', 'usafi', 'wmahubco_hub'];
foreach ($dbs as $dbname) {
    try {
        $db = new PDO("mysql:host=localhost;dbname=$dbname", "root", "");
        echo "Database: $dbname\n";
        
        $tables = $db->query("SHOW TABLES LIKE 'expenses'");
        if ($tables->fetch()) {
            echo "  Found 'expenses' table.\n";
            $cols = $db->query("DESCRIBE expenses");
            while ($row = $cols->fetch(PDO::FETCH_ASSOC)) {
                echo "    " . $row['Field'] . " (" . $row['Type'] . ", Null: " . $row['Null'] . ")\n";
            }
        }
    } catch (Exception $e) {}
}
?>
