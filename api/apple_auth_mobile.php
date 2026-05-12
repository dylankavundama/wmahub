<?php
/**
 * Endpoint d'authentification Apple pour l'application mobile.
 * Reçoit le identityToken (JWT) envoyé par Flutter et connecte l'utilisateur.
 */
error_reporting(0);
ini_set('display_errors', '0');

require_once __DIR__ . '/../includes/config.php';

if (ob_get_level() > 0) ob_clean();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}

try {
    $db = getDBConnection();

    // Récupérer les données envoyées par le mobile
    $identityToken = $_POST['identityToken'] ?? '';
    $userIdentifier = $_POST['userIdentifier'] ?? ''; // sub
    $emailProvided = $_POST['email'] ?? ''; // Apple l'envoie parfois séparément
    $nameProvided = $_POST['name'] ?? '';

    if (empty($identityToken)) {
        throw new Exception("Apple Identity Token is required");
    }

    // 1. Décoder le JWT identityToken
    $parts = explode('.', $identityToken);
    if (count($parts) < 2) {
        throw new Exception("Invalid Apple Identity Token format");
    }

    $payload = json_decode(base64_decode($parts[1]), true);
    if (!$payload) {
        throw new Exception("Unable to decode Apple Identity Token payload");
    }

    $apple_id = $payload['sub'] ?? $userIdentifier;
    $email = $payload['email'] ?? $emailProvided;

    if (empty($apple_id)) {
        throw new Exception("Apple ID (sub) not found in token");
    }

    // 2. Rechercher l'utilisateur dans la base de données
    // On cherche par apple_id ou par email (si l'email est présent dans le token)
    $user = null;
    if (!empty($email)) {
        $stmt = $db->prepare("SELECT id, name, email, role, apple_id FROM users WHERE apple_id = ? OR email = ? LIMIT 1");
        $stmt->execute([$apple_id, $email]);
    } else {
        $stmt = $db->prepare("SELECT id, name, email, role, apple_id FROM users WHERE apple_id = ? LIMIT 1");
        $stmt->execute([$apple_id]);
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Optionnel : Créer le compte s'il n'existe pas (comportement standard pour OAuth)
        // Mais ici, selon la logique Google mobile, on demande de s'inscrire sur le web d'abord
        echo json_encode([
            "success" => false,
            "error_code" => "NO_ACCOUNT",
            "message" => "Aucun compte WMA HUB trouvé associé à cet identifiant Apple. Veuillez vous inscrire sur notre plateforme d'abord."
        ]);
        exit;
    }

    // 3. Vérification du rôle (Artiste ou Admin uniquement sur mobile)
    $userRole = trim(strtolower($user['role'] ?? ''));
    $allowedRoles = ['artiste', 'admin'];
    
    if (!in_array($userRole, $allowedRoles)) {
        echo json_encode([
            "success" => false,
            "error_code" => "INVALID_ROLE",
            "message" => "L'accès mobile est réservé aux Artistes et Administrateurs."
        ]);
        exit;
    }

    // 4. Mise à jour de l'apple_id s'il n'était pas encore lié
    if (empty($user['apple_id'])) {
        $update = $db->prepare("UPDATE users SET apple_id = ? WHERE id = ?");
        $update->execute([$apple_id, $user['id']]);
    }

    // 5. Succès
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
    if (ob_get_level() > 0) ob_clean();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
