<?php
require_once 'c:/xampp/htdocs/wmahub/includes/config.php';
try {
    $db = getDBConnection();
    $res = $db->query('SHOW COLUMNS FROM projects');
    $columns = $res->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns in 'projects' table:\n";
    foreach ($columns as $col) {
        echo "- $col\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
