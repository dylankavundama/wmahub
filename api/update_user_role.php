<?php
error_reporting(0);
ini_set('display_errors', '0');

require_once __DIR__ . '/../includes/config.php';

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

    $userId = $_POST['user_id'] ?? '';
    $role = $_POST['role'] ?? '';

    if (empty($userId) || empty($role)) {
        throw new Exception("user_id and role are required");
    }

    if (!in_array($role, ['artiste', 'employe', 'simple_user'])) {
        throw new Exception("Rôle invalide. Choisissez entre artiste, employe ou simple_user.");
    }

    // Récupérer les informations actuelles de l'utilisateur
    $stmt = $db->prepare("SELECT id, name, email, role, is_active FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("Utilisateur introuvable");
    }

    // Activation automatique pour les artistes et simple_user, attente pour les employés/agents
    $isActive = ($role === 'employe') ? 0 : 1;

    $updateStmt = $db->prepare("UPDATE users SET role = ?, is_active = ? WHERE id = ?");
    $updateStmt->execute([$role, $isActive, $userId]);

    // Re-récupérer les infos mises à jour
    $user['role'] = $role;
    $user['is_active'] = $isActive;

    // Envoi des e-mails et notifications en tâche de fond (silencieux pour le mobile)
    try {
        require_once __DIR__ . '/../includes/mailer.php';
        
        if ($role === 'artiste' || $role === 'employe') {
            $roleLabel = ($role === 'artiste') ? 'Artiste' : 'Employé/Agent';
            notifyAdmin('registration', 'Nouveau ' . $roleLabel . ' Inscrit (Mobile)', [
                'Nom' => $user['name'],
                'Email' => $user['email'],
                'Rôle' => $roleLabel,
                'Date' => date('d/m/Y H:i')
            ], 'https://wmahub.com/dashboards/admin/users.php');

            sendWelcomeEmail($user['email'], $user['name'], $role);
        }
    } catch (Exception $mailEx) {
        error_log("Failed to send welcome email/notify admin: " . $mailEx->getMessage());
    }

    echo json_encode([
        "success" => true,
        "message" => "Rôle mis à jour avec succès",
        "user" => [
            "id" => (int)$user['id'],
            "name" => $user['name'],
            "email" => $user['email'],
            "role" => $user['role'],
            "is_active" => (int)$user['is_active']
        ]
    ]);

} catch (Exception $e) {
    if (ob_get_level() > 0) {
        ob_clean();
    }
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
