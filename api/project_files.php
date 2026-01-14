<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$db = getDBConnection();
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

// Restrict to admin and employe
if (!in_array($userRole, ['admin', 'employe'])) {
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'upload':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        
        if (empty($title)) {
            echo json_encode(['success' => false, 'message' => 'Le titre est obligatoire']);
            exit;
        }

        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/project_files/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $originalName = $_FILES['file']['name'];
            $ext = pathinfo($originalName, PATHINFO_EXTENSION);
            $fileName = 'file_' . uniqid() . '.' . $ext;
            $filePath = 'uploads/project_files/' . $fileName;
            $fileSize = $_FILES['file']['size'];
            $fileType = $_FILES['file']['type'];

            if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadDir . $fileName)) {
                $stmt = $db->prepare("INSERT INTO project_files (sender_id, title, description, file_path, file_type, file_size) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$userId, $title, $description, $filePath, $fileType, $fileSize]);
                
                // Optional: Notify others
                $stmt_others = $db->prepare("SELECT id FROM users WHERE role IN ('admin', 'employe') AND id != ?");
                $stmt_others->execute([$userId]);
                $others = $stmt_others->fetchAll();
                foreach ($others as $other) {
                    createNotification($other['id'], 'new_project_file', "Nouveau fichier partagé : $title", $db->lastInsertId());
                }

                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'upload']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Aucun fichier reçu']);
        }
        break;

    case 'get_files':
        $stmt = $db->prepare("SELECT pf.*, u.name as sender_name 
                              FROM project_files pf 
                              JOIN users u ON pf.sender_id = u.id 
                              ORDER BY pf.created_at DESC");
        $stmt->execute();
        $files = $stmt->fetchAll();
        echo json_encode(['success' => true, 'files' => $files]);
        break;

    case 'delete':
        $fileId = $_POST['file_id'] ?? null;
        if (!$fileId) exit;

        // Only sender or admin can delete
        $stmt = $db->prepare("SELECT * FROM project_files WHERE id = ?");
        $stmt->execute([$fileId]);
        $file = $stmt->fetch();

        if ($file && ($file['sender_id'] == $userId || $userRole === 'admin')) {
            // Delete actual file
            $fullPath = __DIR__ . '/../' . $file['file_path'];
            if (file_exists($fullPath)) unlink($fullPath);
            
            // Delete record
            $stmt_del = $db->prepare("DELETE FROM project_files WHERE id = ?");
            $stmt_del->execute([$fileId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Action non autorisée']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action non supportée']);
        break;
}
?>
