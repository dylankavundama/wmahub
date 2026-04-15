<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

// basic auth check or just accept user_id (for development, in production use tokens)
$user_id = $_GET['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(["success" => false, "message" => "User ID is required"]);
    exit;
}

try {
    $db = getDBConnection();

    // 1. Stats
    $stmt = $db->prepare("SELECT COUNT(*) as total_projects, 
                                 SUM(streams) as total_streams, 
                                 SUM(CASE WHEN status = 'distribue' THEN 1 ELSE 0 END) as distributed_count,
                                 SUM(revenue) as total_revenue
                          FROM projects WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Recent Projects
    $stmt = $db->prepare("SELECT id, title, artist_name, status, streams, date_sortie, created_at 
                          FROM projects WHERE user_id = ? 
                          ORDER BY created_at DESC 
                          LIMIT 5");
    $stmt->execute([$user_id]);
    $recent_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Notifications count (unread)
    // Assuming a notifications table exists based on what I saw in dashboards/artiste/notifications.php
    // If not, we'll return 0
    $notif_count = 0;
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        $notif_count = $stmt->fetchColumn();
    } catch (Exception $e) { $notif_count = 0; }

    echo json_encode([
        "success" => true,
        "data" => [
            "stats" => [
                "total_projects" => (int)$stats['total_projects'],
                "total_streams" => (int)$stats['total_streams'],
                "distributed_count" => (int)$stats['distributed_count'],
                "total_revenue" => (float)$stats['total_revenue']
            ],
            "recent_projects" => $recent_projects,
            "notification_count" => $notif_count
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
