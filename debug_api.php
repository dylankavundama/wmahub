<?php
require_once 'includes/config.php';
try {
    $db = getDBConnection();
    $stmt = $db->query("SELECT * FROM hero_slides WHERE is_active = 1 ORDER BY id DESC");
    $slides = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . "://" . $host . "/wmahub/";
    
    foreach ($slides as &$slide) {
        if (!preg_match("~^(?:f|ht)tps?://~i", $slide['image_path'])) {
            $slide['image_path'] = $baseUrl . $slide['image_path'];
        }
    }
    echo json_encode($slides);
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
