<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

try {
    $db = getDBConnection();
    
    $user_id = $_GET['user_id'] ?? 0;

    if (empty($user_id)) {
        throw new Exception("User ID is required");
    }

    // Récupération des notifications
    $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Marquer comme lu
    $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")->execute([$user_id]);

    echo json_encode([
        "success" => true,
        "data" => $notifications
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
