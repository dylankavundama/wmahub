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

    function columnExists(PDO $db, string $table, string $column): bool
    {
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $stmt->execute([$table, $column]);
        return (int) $stmt->fetchColumn() > 0;
    }

    $hasPhotoUrl = columnExists($db, 'users', 'photo_url');
    $photoCol = $hasPhotoUrl ? "u.photo_url" : "NULL";

    // Fetch from the 'projects' table which is the source of truth for the web dashboard too
    $stmt = $db->prepare("SELECT p.id, p.title, p.artist_name, p.type as project_type, p.status, p.cover_file as cover_path, p.audio_file as audio_path, p.date_sortie, p.created_at, u.name as publisher_name, $photoCol as publisher_photo
                          FROM projects p
                          LEFT JOIN users u ON p.user_id = u.id
                          WHERE p.user_id = ? 
                          ORDER BY p.created_at DESC");
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
