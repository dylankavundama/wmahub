<?php
require_once __DIR__ . '/../includes/config.php';
$db = getDBConnection();
$stmt = $db->query("SELECT id, name, email FROM users LIMIT 10");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($users);
