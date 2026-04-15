<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

try {
    $db = getDBConnection();
    
    $user_id = $_GET['user_id'] ?? 0;

    if (!$user_id) {
        echo json_encode([]);
        exit;
    }

    // Fetch from the 'projects' table which is the source of truth for the web dashboard too
    $stmt = $db->prepare("SELECT id, title, artist_name, type as project_type, status, cover_file as cover_path, audio_file as audio_path, date_sortie, created_at 
                          FROM projects 
                          WHERE user_id = ? 
                          ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Note: The mobile app expects 'cover_path' and 'project_type'
    // We've aliased 'type' to 'project_type' and 'cover_file' to 'cover_path'
    
    echo json_encode($projects);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
