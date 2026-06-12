<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "ID de l'artiste manquant."]);
    exit;
}

$artistId = (int)$_GET['id'];

try {
    $db = getDBConnection();
    
    // Fetch artist details with user photo fallback
    $stmt = $db->prepare("SELECT a.*, u.photo_url as user_photo FROM ua_artists a LEFT JOIN users u ON a.user_id = u.id WHERE a.id = ?");
    $stmt->execute([$artistId]);
    $artist = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$artist) {
        http_response_code(404);
        echo json_encode(["error" => "Artiste introuvable."]);
        exit;
    }

    // Format artist photo_url if necessary
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

    // Fetch projects associated with this artist
    $projStmt = $db->prepare("SELECT * FROM distributions WHERE artist_id = ? AND status = 'active' ORDER BY release_date DESC, created_at DESC");
    $projStmt->execute([$artistId]);
    $projects = $projStmt->fetchAll(PDO::FETCH_ASSOC);

    // Format project cover URLs
    foreach ($projects as &$project) {
        if (!empty($project['image_url']) && !filter_var($project['image_url'], FILTER_VALIDATE_URL)) {
            $cover = ltrim($project['image_url'], '/');
            $project['image_url'] = $baseUrl . '/' . $cover;
        }
    }

    echo json_encode([
        "artist" => $artist,
        "projects" => $projects
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
