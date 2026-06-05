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

    if (!function_exists('columnExists')) {
        function columnExists(PDO $db, string $table, string $column): bool
        {
            $stmt = $db->prepare(
                'SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $stmt->execute([$table, $column]);
            return (int) $stmt->fetchColumn() > 0;
        }
    }

    $hasPhotoUrl = columnExists($db, 'users', 'photo_url');
    $photoCol = $hasPhotoUrl ? "u.photo_url" : "NULL";

    // 2. Recent Projects
    $stmt = $db->prepare("SELECT p.id, p.title, p.artist_name, p.status, p.streams, p.date_sortie, p.created_at, u.name as publisher_name, $photoCol as publisher_photo
                          FROM projects p
                          LEFT JOIN users u ON p.user_id = u.id
                          WHERE p.user_id = ? 
                          ORDER BY p.id DESC 
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
            "notification_count" => (int)$notif_count
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
