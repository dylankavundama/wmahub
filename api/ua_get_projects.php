<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    $db = getDBConnection();
    
    $query = "SELECT d.*, a.name as ua_artist_name, a.photo_url as ua_artist_photo, u.photo_url as user_photo
              FROM distributions d
              LEFT JOIN ua_artists a ON d.artist_id = a.id
              LEFT JOIN users u ON a.user_id = u.id
              WHERE d.status = 'active'
              ORDER BY d.release_date DESC, d.created_at DESC";
              
    $stmt = $db->query($query);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format URLs and fallback name
    foreach ($projects as &$project) {
        if (!empty($project['image_url']) && !filter_var($project['image_url'], FILTER_VALIDATE_URL)) {
            $cover = ltrim($project['image_url'], '/');
            $project['image_url'] = $baseUrl . '/' . $cover;
        }
        
        // Fallback artist name if not linked to a ua_artist
        if (empty($project['ua_artist_name'])) {
            $project['ua_artist_name'] = $project['artist'];
        }
        
        $photo = !empty($project['ua_artist_photo']) ? $project['ua_artist_photo'] : ($project['user_photo'] ?? '');
        if (!empty($photo)) {
            if (!filter_var($photo, FILTER_VALIDATE_URL)) {
                $photo = ltrim($photo, '/');
                $photo = $baseUrl . '/' . $photo;
            }
            $project['ua_artist_photo'] = $photo;
        } else {
            $project['ua_artist_photo'] = '';
        }
    }

    echo json_encode($projects);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
