<?php
require_once __DIR__ . '/../includes/config.php';
try {
    $db = getDBConnection();
    $result = $db->query("DESCRIBE projects")->fetchAll();
    foreach ($result as $row) {
        echo $row['Field'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
