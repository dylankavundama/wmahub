<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

try {
    $db = getDBConnection();
    $stmt = $db->query("SELECT * FROM hero_slides WHERE is_active = 1 ORDER BY id DESC");
    $slides = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Improved base URL detection
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $host = $_SERVER['HTTP_HOST'];
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $baseDir = str_replace('/api', '', $scriptDir);
    $baseUrl = $protocol . "://" . $host . rtrim($baseDir, '/') . '/';
    
    foreach ($slides as &$slide) {
        // If image_path is already an absolute URL but from a different host, strip it to make it relative
        if (preg_match("~^(?:f|ht)tps?://~i", $slide['image_path'])) {
            $parsedUrl = parse_url($slide['image_path']);
            if (isset($parsedUrl['path'])) {
                // Keep only the part after /wmahub/ or assume it's relative to root
                $path = $parsedUrl['path'];
                if (strpos($path, '/wmahub/') !== false) {
                    $slide['image_path'] = substr($path, strpos($path, '/wmahub/') + 8);
                } else if (strpos($path, '/asset/') !== false) {
                     $slide['image_path'] = substr($path, strpos($path, '/asset/') + 1);
                }
            }
        }
        
        // Prepend current baseUrl if relative
        if (!preg_match("~^(?:f|ht)tps?://~i", $slide['image_path'])) {
            $slide['image_path'] = $baseUrl . ltrim($slide['image_path'], '/');
        }
    }
    
    echo json_encode($slides);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>
