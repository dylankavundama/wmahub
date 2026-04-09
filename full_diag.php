<?php
// Try different host strings to connect to MySQL
$hosts = ['localhost', '127.0.0.1', 'localhost:3306', '127.0.0.1:3306'];
$db_name = 'wmahubco_hub';
$user = 'wmahubco_hub';
$pass = 'Y3OS;W-)bsQR6*D6';

foreach ($hosts as $host) {
    try {
        echo "Testing connection to $host...\n";
        $dsn = "mysql:host=$host;dbname=$db_name;charset=utf8mb4";
        $db = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo "Successfully connected to $host!\n";
        
        $res = $db->query("DESCRIBE projects");
        echo "Columns in 'projects':\n";
        while($row = $res->fetch(PDO::FETCH_ASSOC)) {
            echo "  - " . $row['Field'] . "\n";
        }
        break; // Exit loop on success
    } catch (Exception $e) {
        echo "Failed to connect to $host: " . $e->getMessage() . "\n";
    }
}
?>
