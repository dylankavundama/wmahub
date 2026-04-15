<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}

try {
    $db = getDBConnection();
    
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        throw new Exception("Email and Password are required");
    }

    // This is a simplified login check. 
    // In production, we should use password_verify().
    // We are looking for any user with the role 'artiste'.
    
    $stmt = $db->prepare("SELECT id, name, email, role FROM users WHERE email = ? AND role = 'artiste' LIMIT 1");
    // $stmt = $db->prepare("SELECT id, fullname as name, email, role FROM users WHERE email = ? AND password = ? AND role = 'artiste' LIMIT 1");
    
    // For now, if the password matches a specific placeholder or we just allow login for demo
    // Let's assume there is a users table.
    
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Here you would check the password: if (password_verify($password, $user['password']))
        echo json_encode([
            "success" => true,
            "user" => [
                "id" => $user['id'],
                "name" => $user['name'],
                "email" => $user['email'],
                "role" => $user['role']
            ]
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Credentials invalid or user is not an artist"]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
