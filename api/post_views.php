<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../includes/config.php';

try {
    $db = getDBConnection();
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database connection error: " . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $postId = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
    if ($postId <= 0) {
        // Try reading JSON body if not in query parameters
        $input = json_decode(file_get_contents('php://input'), true);
        $postId = isset($input['post_id']) ? intval($input['post_id']) : 0;
    }
    
    if ($postId <= 0) {
        echo json_encode(["success" => false, "message" => "Invalid post_id"]);
        exit;
    }
    
    $predefinedViews = ($postId * 13 + 37) % 850 + 120;
    
    try {
        $stmt = $db->prepare("INSERT INTO post_views (post_id, views_count) VALUES (?, 1) ON DUPLICATE KEY UPDATE views_count = views_count + 1");
        $stmt->execute([$postId]);
        
        $stmt = $db->prepare("SELECT views_count FROM post_views WHERE post_id = ?");
        $stmt->execute([$postId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $increment = $row ? intval($row['views_count']) : 1;
        
        $totalViews = $predefinedViews + $increment;
        
        echo json_encode([
            "success" => true,
            "post_id" => $postId,
            "predefined_views" => $predefinedViews,
            "increment_views" => $increment,
            "total_views" => $totalViews
        ]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Error writing views: " . $e->getMessage()]);
    }
} else {
    // GET: Retrieve views count for single post_id or multiple post_ids
    $postIdsStr = isset($_GET['post_ids']) ? $_GET['post_ids'] : '';
    $postId = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
    
    if (!empty($postIdsStr)) {
        $ids = array_filter(array_map('intval', explode(',', $postIdsStr)));
        if (empty($ids)) {
            echo json_encode(["success" => false, "message" => "No valid post_ids provided"]);
            exit;
        }
        
        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("SELECT post_id, views_count FROM post_views WHERE post_id IN ($placeholders)");
            $stmt->execute($ids);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $increments = [];
            foreach ($rows as $row) {
                $increments[intval($row['post_id'])] = intval($row['views_count']);
            }
            
            $results = [];
            foreach ($ids as $id) {
                $predefined = ($id * 13 + 37) % 850 + 120;
                $inc = isset($increments[$id]) ? $increments[$id] : 0;
                $results[$id] = $predefined + $inc;
            }
            
            echo json_encode([
                "success" => true,
                "views" => $results
            ]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error reading views: " . $e->getMessage()]);
        }
    } else if ($postId > 0) {
        $predefinedViews = ($postId * 13 + 37) % 850 + 120;
        try {
            $stmt = $db->prepare("SELECT views_count FROM post_views WHERE post_id = ?");
            $stmt->execute([$postId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $increment = $row ? intval($row['views_count']) : 0;
            
            $totalViews = $predefinedViews + $increment;
            
            echo json_encode([
                "success" => true,
                "post_id" => $postId,
                "predefined_views" => $predefinedViews,
                "increment_views" => $increment,
                "total_views" => $totalViews
            ]);
        } catch (PDOException $e) {
            echo json_encode(["success" => false, "message" => "Error reading views: " . $e->getMessage()]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Missing parameters post_id or post_ids"]);
    }
}
