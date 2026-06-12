<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    $db = getDBConnection();
    $stmt = $db->query("SELECT a.*, u.photo_url as user_photo FROM ua_artists a LEFT JOIN users u ON a.user_id = u.id WHERE a.is_ua = 1 ORDER BY a.name ASC");
    $artists = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format photo_url if necessary
    foreach ($artists as &$artist) {
        $photo = !empty($artist['photo_url']) ? $artist['photo_url'] : ($artist['user_photo'] ?? '');
        if (!empty($photo)) {
            if (!filter_var($photo, FILTER_VALIDATE_URL)) {
                $photo = ltrim($photo, '/');
                $photo = $baseUrl . '/' . $photo;
            }
            $artist['photo_url'] = $photo;
        } else {
            $artist['photo_url'] = '';
        }
    }

    echo json_encode($artists);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
