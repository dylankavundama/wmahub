<?php
require_once __DIR__ . '/includes/config.php';

$db = getDBConnection();

if (isset($_GET['make_admin']) && is_numeric($_GET['make_admin'])) {
    $stmt = $db->prepare("UPDATE users SET role = 'admin', is_active = 1 WHERE id = ?");
    $stmt->execute([$_GET['make_admin']]);
    echo "User ID " . $_GET['make_admin'] . " is now ADMIN.<br><br>";
}

$stmt = $db->query("SELECT id, name, email, role, is_active FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h1>Gestion des Accès WMA Hub</h1>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Nom</th><th>Email</th><th>Rôle</th><th>Actif</th><th>Action</th></tr>";
foreach ($users as $user) {
    echo "<tr>";
    echo "<td>{$user['id']}</td>";
    echo "<td>" . htmlspecialchars($user['name']) . "</td>";
    echo "<td>" . htmlspecialchars($user['email']) . "</td>";
    echo "<td>" . ($user['role'] ?: 'Aucun') . "</td>";
    echo "<td>" . ($user['is_active'] ? 'OUI' : 'NON') . "</td>";
    if ($user['role'] !== 'admin') {
        echo "<td><a href='?make_admin={$user['id']}'>Rendre ADMIN</a></td>";
    } else {
        echo "<td>-</td>";
    }
    echo "</tr>";
}
echo "</table>";
?>
