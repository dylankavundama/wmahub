<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

try {
    $db = getDBConnection();
    $stmt = $db->query("SELECT * FROM distributions WHERE status = 'active' ORDER BY created_at DESC");
    $distributions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add full path to images if they are local
    foreach ($distributions as &$dist) {
        if (!empty($dist['image_url']) && !filter_var($dist['image_url'], FILTER_VALIDATE_URL)) {
            $dist['image_url'] = "https://wmahub.com/" . $dist['image_url'];
        }
    }

    echo json_encode($distributions);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
