<?php
try {
    $db = new PDO('mysql:host=localhost;dbname=wmahubco_millan', 'root', '');
    $res = $db->query('SHOW COLUMNS FROM projects');
    while($row = $res->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . "\n";
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
