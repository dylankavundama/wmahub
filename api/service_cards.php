<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

$db = getDBConnection();
$userId = $_SESSION['user_id'];
$isAdmin = ($_SESSION['role'] === 'admin');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'submit':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

        $fullName = $_POST['full_name'] ?? '';
        $role = $_POST['role_title'] ?? '';
        $matricule = $_POST['matricule'] ?? '';
        $department = $_POST['department'] ?? '';
        $bloodGroup = $_POST['blood_group'] ?? '';
        $emergencyContact = $_POST['emergency_contact'] ?? '';
        
        // Handle Photo Upload
        $photoPath = $_POST['existing_photo'] ?? '';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/service_cards/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $fileName = 'card_' . $userId . '_' . time() . '.' . $ext;
            $photoPath = 'uploads/service_cards/' . $fileName;
            
            move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $fileName);
        }

        // Check if card exists
        $stmt = $db->prepare("SELECT id FROM service_cards WHERE user_id = ?");
        $stmt->execute([$userId]);
        $existing = $stmt->fetch();

        if ($existing) {
            $stmt = $db->prepare("UPDATE service_cards SET 
                full_name = ?, role = ?, matricule = ?, department = ?, 
                blood_group = ?, emergency_contact = ?, photo_path = ?, status = 'pending' 
                WHERE user_id = ?");
            $stmt->execute([$fullName, $role, $matricule, $department, $bloodGroup, $emergencyContact, $photoPath, $userId]);
        } else {
            $stmt = $db->prepare("INSERT INTO service_cards 
                (user_id, full_name, role, matricule, department, blood_group, emergency_contact, photo_path, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$userId, $fullName, $role, $matricule, $department, $bloodGroup, $emergencyContact, $photoPath]);
        }

        echo json_encode(['success' => true, 'message' => 'Demande envoyée avec succès']);
        break;

    case 'get_my_card':
        $stmt = $db->prepare("SELECT * FROM service_cards WHERE user_id = ?");
        $stmt->execute([$userId]);
        $card = $stmt->fetch();
        echo json_encode(['success' => true, 'card' => $card]);
        break;

    case 'approve':
        if (!$isAdmin) exit;
        $cardId = $_POST['card_id'] ?? 0;
        $stmt = $db->prepare("UPDATE service_cards SET status = 'approved' WHERE id = ?");
        $stmt->execute([$cardId]);
        echo json_encode(['success' => true]);
        break;

    case 'reject':
        if (!$isAdmin) exit;
        $cardId = $_POST['card_id'] ?? 0;
        $stmt = $db->prepare("UPDATE service_cards SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$cardId]);
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action non supportée']);
        break;
}
?>
