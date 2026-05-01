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
    
    $idToken = $_POST['idToken'] ?? '';

    if (empty($idToken)) {
        throw new Exception("Google ID Token is required");
    }

    // Verify token with Google
    $verifyUrl = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $idToken;
    $response = file_get_contents($verifyUrl);
    $google_data = json_decode($response, true);

    if (isset($google_data['error_description'])) {
        throw new Exception("Invalid Google Token: " . $google_data['error_description']);
    }

    $google_id = $google_data['sub'];
    $email = $google_data['email'];
    $name = $google_data['name'] ?? 'Artiste Google';

    // Check if user exists
    $stmt = $db->prepare("SELECT id, name, email, role FROM users WHERE google_id = ? OR email = ? LIMIT 1");
    $stmt->execute([$google_id, $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        error_log("Google Login Failed: No account found for email $email");
        echo json_encode([
            "success" => false, 
            "error_code" => "NO_ACCOUNT",
            "message" => "Aucun compte trouvé pour $email. Veuillez vous inscrire sur notre site web d'abord."
        ]);
        exit;
    }

    // Normalisation du rôle pour éviter les erreurs de casse ou d'espaces
    $userRole = trim(strtolower($user['role'] ?? ''));

    // Autoriser les Artistes ET les Admins sur mobile
    $allowedRoles = ['artiste', 'admin'];
    if (!in_array($userRole, $allowedRoles)) {
        error_log("Google Login Denied: User $email has role '" . ($user['role'] ?? 'NULL') . "'");
        echo json_encode([
            "success" => false, 
            "error_code" => "INVALID_ROLE",
            "message" => "Désolé, l'accès mobile est réservé uniquement aux Artistes et Administrateurs."
        ]);
        exit;
    }

    // If we are here, user exists and is an artist
    // Update google_id if not set
    if (empty($user['google_id'])) {
        $db->prepare("UPDATE users SET google_id = ? WHERE id = ?")->execute([$google_id, $user['id']]);
    }

    echo json_encode([
        "success" => true,
        "user" => [
            "id" => $user['id'],
            "name" => $user['name'],
            "email" => $user['email'],
            "role" => $user['role']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
