<?php
try {
    $db = new PDO('mysql:host=localhost', 'root', '');
    $res = $db->query('SHOW DATABASES');
    while($row = $res->fetch(PDO::FETCH_ASSOC)) {
        $dbname = $row['Database'];
        try {
            $db2 = new PDO("mysql:host=localhost;dbname=$dbname", "root", "");
            $res2 = $db2->query("SHOW TABLES LIKE 'projects'");
            if ($res2->fetch()) {
                echo "Found 'projects' in database: $dbname\n";
                // Show columns
                $res3 = $db2->query("DESCRIBE projects");
                while($col = $res3->fetch(PDO::FETCH_ASSOC)) {
                    echo "  - " . $col['Field'] . "\n";
                }
            }
        } catch (Exception $e2) {}
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
