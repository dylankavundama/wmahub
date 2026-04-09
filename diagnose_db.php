<?php
require_once 'includes/config.php';
try {
    $db = getDBConnection();
    $res = $db->query("SELECT DATABASE()");
    echo "Current DB: " . $res->fetchColumn() . "\n";
    
    $res2 = $db->query("SHOW TABLES LIKE 'projects'");
    if ($res2->fetch()) {
        echo "Table 'projects' EXISTS.\n";
        $res3 = $db->query("DESCRIBE projects");
        while($row = $res3->fetch(PDO::FETCH_ASSOC)) {
            echo "  " . $row['Field'] . "\n";
        }
    } else {
        echo "Table 'projects' NOT FOUND.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
