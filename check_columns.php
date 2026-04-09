<?php
require_once 'includes/config.php';
try {
    $db = getDBConnection();
    $res = $db->query('SHOW COLUMNS FROM projects');
    while($row = $res->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . "\n";
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
