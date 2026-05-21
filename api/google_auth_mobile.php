<?php
// Supprimer TOUTES les erreurs PHP pour éviter qu'elles contaminent la réponse JSON
error_reporting(0);
ini_set('display_errors', '0');

require_once __DIR__ . '/../includes/config.php';

// Vider le buffer de sortie (notices/warnings de config.php)
if (ob_get_level() > 0) {
    ob_clean();
}

header('Content-Type: application/json; charset=utf-8');

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

    // Verify token with Google via cURL (plus fiable que file_get_contents)
    $verifyUrl = "https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($idToken);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $verifyUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPGET        => true,
    ]);
    $responseRaw = curl_exec($ch);
    $curlError   = curl_error($ch);
    $httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($responseRaw === false || !empty($curlError)) {
        throw new Exception("Impossible de joindre Google : $curlError");
    }

    $google_data = json_decode($responseRaw, true);

    if (!is_array($google_data)) {
        throw new Exception("Réponse invalide de Google (code HTTP $httpCode).");
    }

    if (isset($google_data['error_description'])) {
        throw new Exception("Token Google invalide : " . $google_data['error_description']);
    }

    if (empty($google_data['sub']) || empty($google_data['email'])) {
        throw new Exception("Données Google incomplètes (sub/email manquants).");
    }

    $google_id = $google_data['sub'];
    $email     = $google_data['email'];
    $name      = $google_data['name'] ?? 'Artiste Google';

    // Check if user exists
    $stmt = $db->prepare("SELECT id, name, email, role, is_active FROM users WHERE google_id = ? OR email = ? LIMIT 1");
    $stmt->execute([$google_id, $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Créer un nouvel utilisateur avec rôle NULL (en attente de choix de rôle sur mobile)
        $stmt = $db->prepare("INSERT INTO users (google_id, name, email, is_active, role) VALUES (?, ?, ?, 0, NULL)");
        $stmt->execute([$google_id, $name, $email]);
        $userId = $db->lastInsertId();
        
        $user = [
            "id"        => $userId,
            "name"      => $name,
            "email"     => $email,
            "role"      => null,
            "is_active" => 0
        ];
        
        // Notifier l'équipe admin
        try {
            require_once __DIR__ . '/../includes/mailer.php';
            notifyAdmin('registration', 'Nouvel Utilisateur Inscrit (Google Mobile)', [
                'Nom' => $name,
                'Email' => $email,
                'Méthode' => 'Google Mobile OAuth',
                'Date' => date('d/m/Y H:i')
            ], 'https://wmahub.com/dashboards/admin/users.php');
        } catch (Exception $e) {
            error_log("Failed to notify admin of new user: " . $e->getMessage());
        }
    } else {
        // Update google_id if not set
        if (empty($user['google_id'])) {
            $db->prepare("UPDATE users SET google_id = ? WHERE id = ?")->execute([$google_id, $user['id']]);
        }
    }

    echo json_encode([
        "success" => true,
        "user"    => [
            "id"        => $user['id'],
            "name"      => $user['name'],
            "email"     => $user['email'],
            "role"      => $user['role'],
            "is_active" => (int)$user['is_active']
        ]
    ]);

} catch (Exception $e) {
    // Vider à nouveau au cas où une erreur aurait produit du HTML
    if (ob_get_level() > 0) {
        ob_clean();
    }
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
