<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}

function columnExists(PDO $db, string $table, string $column): bool
{
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    return (int) $stmt->fetchColumn() > 0;
}

try {
    $db = getDBConnection();
    
    $user_id = $_POST['user_id'] ?? 0;

    if (empty($user_id)) {
        throw new Exception("User ID is required");
    }

    // Pour la conformité Store et RGPD, nous désactivons le compte, détachons les IDs sociaux
    // et anonymisons l'e-mail pour permettre une éventuelle réinscription future.
    $hasFirebase = columnExists($db, 'users', 'firebase_uid');
    
    $query = "UPDATE users SET is_active = 0, google_id = NULL, apple_id = NULL";
    if ($hasFirebase) {
        $query .= ", firebase_uid = NULL";
    }
    $query .= ", email = CONCAT(email, '.deleted.', UNIX_TIMESTAMP()) WHERE id = ?";
    
    $stmt = $db->prepare($query);
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
