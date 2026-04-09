<?php
$dbs = ['wmahubco_millan', 'wmaplus', 'wmahub', 'quincatech', 'usafi'];
foreach ($dbs as $dbname) {
    try {
        $db = new PDO("mysql:host=localhost;dbname=$dbname", "root", "");
        $res = $db->query("SHOW TABLES");
        if ($res) {
            echo "Tables in $dbname:\n";
            while($row = $res->fetch()) {
                echo "  " . $row[0] . "\n";
            }
        }
    } catch (Exception $e) {}
}
?>
