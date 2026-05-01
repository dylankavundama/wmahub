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
    
    $user_id = $_POST['user_id'] ?? 0;

    if (empty($user_id)) {
        throw new Exception("User ID is required");
    }

    // Pour la conformité Store, nous pouvons soit supprimer les données, 
    // soit désactiver le compte. Ici nous marquons comme inactif.
    $stmt = $db->prepare("UPDATE users SET is_active = 0, google_id = NULL, apple_id = NULL WHERE id = ?");
    $result = $stmt->execute([$user_id]);

    if ($result) {
        echo json_encode([
            "success" => true,
            "message" => "Votre demande de suppression de compte a été traitée. Votre compte est désormais désactivé."
        ]);
    } else {
        throw new Exception("Erreur lors de la désactivation du compte.");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
