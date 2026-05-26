<?php
/**
 * @deprecated Utiliser api/firebase_auth_mobile.php (Firebase ID token).
 * L'app mobile passe par Firebase Auth + Sign in with Apple.
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

    // 1. Décoder le JWT identityToken (avec gestion robuste du Base64URL)
    $parts = explode('.', $identityToken);
    if (count($parts) < 2) {
        throw new Exception("Invalid Apple Identity Token format");
    }

    $base64Url = $parts[1];
    $base64 = str_replace(['-', '_'], ['+', '/'], $base64Url);
    $padding = strlen($base64) % 4;
    if ($padding) {
        $base64 .= str_repeat('=', 4 - $padding);
    }

    $payload = json_decode(base64_decode($base64), true);
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
        $stmt = $db->prepare("SELECT id, name, email, role, is_active, apple_id FROM users WHERE apple_id = ? OR email = ? LIMIT 1");
        $stmt->execute([$apple_id, $email]);
    } else {
        $stmt = $db->prepare("SELECT id, name, email, role, is_active, apple_id FROM users WHERE apple_id = ? LIMIT 1");
        $stmt->execute([$apple_id]);
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $finalName = $nameProvided ?: 'Utilisateur Apple';
        $finalEmail = $email ?: '';
        
        // Créer un nouvel utilisateur avec rôle NULL (en attente de choix de rôle sur mobile)
        $stmt = $db->prepare("INSERT INTO users (apple_id, name, email, is_active, role) VALUES (?, ?, ?, 0, NULL)");
        $stmt->execute([$apple_id, $finalName, $finalEmail]);
        $userId = $db->lastInsertId();
        
        $user = [
            "id"        => $userId,
            "name"      => $finalName,
            "email"     => $finalEmail,
            "role"      => null,
            "is_active" => 0
        ];
        
        // Notifier l'équipe admin
        try {
            require_once __DIR__ . '/../includes/mailer.php';
            notifyAdmin('registration', 'Nouvel Utilisateur Inscrit (Apple Mobile)', [
                'Nom' => $finalName,
                'Email' => $finalEmail,
                'Méthode' => 'Apple Mobile OAuth',
                'Date' => date('d/m/Y H:i')
            ], 'https://wmahub.com/dashboards/admin/users.php');
        } catch (Exception $e) {
            error_log("Failed to notify admin of new user: " . $e->getMessage());
        }
    } else {
        // Mise à jour de l'apple_id s'il n'était pas encore lié
        if (empty($user['apple_id'])) {
            $update = $db->prepare("UPDATE users SET apple_id = ? WHERE id = ?");
            $update->execute([$apple_id, $user['id']]);
        }
    }

    // 5. Succès
    echo json_encode([
        "success" => true,
        "user" => [
            "id"        => $user['id'],
            "name"      => $user['name'],
            "email"     => $user['email'],
            "role"      => $user['role'],
            "is_active" => (int)$user['is_active']
        ]
    ]);

} catch (Exception $e) {
    if (ob_get_level() > 0) ob_clean();
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
