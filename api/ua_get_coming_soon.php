<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

try {
    $db = getDBConnection();
    
    // Check if the ua_coming_soon table exists
    $tableExists = false;
    try {
        $db->query("SELECT 1 FROM ua_coming_soon LIMIT 1");
        $tableExists = true;
    } catch (Exception $e) {
        $tableExists = false;
    }
    
    if (!$tableExists) {
        echo json_encode([
            "success" => true,
            "title" => "Bientôt disponible",
            "image_url" => "",
            "is_active" => 1,
            "end_date" => null,
            "expired" => false
        ]);
        exit;
    }
    
    $stmt = $db->query("SELECT * FROM ua_coming_soon ORDER BY id LIMIT 1");
    $data = $stmt->fetch();
    
    if (!$data) {
        echo json_encode([
            "success" => true,
            "title" => "Bientôt disponible",
            "image_url" => "",
            "is_active" => 1,
            "end_date" => null,
            "expired" => false
        ]);
        exit;
    }
    
    $expired = false;
    if (!empty($data['end_date'])) {
        $now = new DateTime();
        $end = new DateTime($data['end_date']);
        if ($now > $end) {
            $expired = true;
        }
    }
    
    echo json_encode([
        "success" => true,
        "title" => $data['title'],
        "image_url" => $data['image_url'],
        "is_active" => (int)$data['is_active'],
        "end_date" => $data['end_date'],
        "expired" => $expired
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
