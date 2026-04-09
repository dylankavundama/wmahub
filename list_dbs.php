<?php
try {
    $db = new PDO('mysql:host=localhost', 'root', '');
    $res = $db->query('SHOW DATABASES');
    while($row = $res->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Database'] . "\n";
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
