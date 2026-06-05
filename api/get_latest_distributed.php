<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

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
    
    $hasPhotoUrl = columnExists($db, 'users', 'photo_url');
    $photoCol = $hasPhotoUrl ? "u.photo_url" : "NULL";

    $stmt = $db->prepare("SELECT p.id, p.title, p.artist_name, p.type as project_type, p.status, p.cover_file as cover_path, p.audio_file as audio_path, p.details, p.genre, p.date_sortie, p.languages, p.promo_pack, p.created_at, u.name as publisher_name, $photoCol as publisher_photo
                          FROM projects p
                          LEFT JOIN users u ON p.user_id = u.id
                          WHERE p.status = 'distribue' 
                          ORDER BY p.created_at DESC 
                          LIMIT 6");
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($projects);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
