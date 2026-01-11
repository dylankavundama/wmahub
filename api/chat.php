<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$db = getDBConnection();
$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'send':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;
        
        $message = $_POST['message'] ?? '';
        $receiverId = $_POST['receiver_id'] ?? null; // For private messages or keep NULL for group
        $imagePath = null;

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/chat/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $fileName = 'chat_' . uniqid() . '.' . $ext;
            $imagePath = 'uploads/chat/' . $fileName;
            move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $fileName);
        }

        if (!empty($message) || $imagePath) {
            $stmt = $db->prepare("INSERT INTO global_messages (sender_id, receiver_id, message, image_path) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $receiverId, $message, $imagePath]);
            
            // Generate notification for the receiver if private
            if ($receiverId) {
                $stmt = $db->prepare("INSERT INTO notifications (user_id, type, reference_id) VALUES (?, 'new_message', ?)");
                $stmt->execute([$receiverId, $db->lastInsertId()]);
            } else {
                // Broadcast notification for admins and employees (except sender)
                $stmt = $db->prepare("SELECT id FROM users WHERE role IN ('admin', 'employe') AND id != ?");
                $stmt->execute([$userId]);
                $others = $stmt->fetchAll();
                foreach ($others as $other) {
                    $stmt_notif = $db->prepare("INSERT INTO notifications (user_id, type, reference_id) VALUES (?, 'new_broadcast_message', ?)");
                    $stmt_notif->execute([$other['id'], $db->lastInsertId()]);
                }
            }
        }
        echo json_encode(['success' => true]);
        break;

    case 'get_messages':
        $lastId = $_GET['last_id'] ?? 0;
        // Fetch last 50 messages + any newer than lastId
        $stmt = $db->prepare("SELECT gm.*, u.name as sender_name, u.role as sender_role 
                              FROM global_messages gm 
                              JOIN users u ON gm.sender_id = u.id 
                              WHERE gm.id > ? 
                              ORDER BY gm.created_at ASC LIMIT 100");
        $stmt->execute([$lastId]);
        $messages = $stmt->fetchAll();
        echo json_encode(['success' => true, 'messages' => $messages]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action non supportée']);
        break;
}
?>
