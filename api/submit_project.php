<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}

try {
    $db = getDBConnection();
    
    // Get fields from mobile request
    $title = $_POST['title'] ?? '';
    $artist_name = $_POST['artist_name'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $city = $_POST['city'] ?? '';
    $languages = $_POST['languages'] ?? '';
    $details = $_POST['details'] ?? '';
    $date_sortie = $_POST['date_sortie'] ?? date('Y-m-d');
    $promo_pack = $_POST['promo_pack'] ?? 'Aucun';
    $authorization = isset($_POST['authorization']) ? (int)$_POST['authorization'] : 0;
    $project_type = $_POST['project_type'] ?? 'Single';
    $user_id = $_POST['user_id'] ?? 0;

    if (empty($title) || empty($artist_name) || empty($email) || empty($full_name)) {
        throw new Exception("Tous les champs obligatoires (*) doivent être remplis.");
    }

    // Use the same upload directory as the web dashboard
    $uploadDir = __DIR__ . '/../dashboards/artiste/uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $coverFileName = "";
    if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION);
        $coverFileName = time() . '_cover_mobile_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['cover']['tmp_name'], $uploadDir . $coverFileName);
    }

    $audioFileName = "";
    if (isset($_FILES['audio']) && $_FILES['audio']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['audio']['name'], PATHINFO_EXTENSION);
        $audioFileName = time() . '_audio_mobile_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['audio']['tmp_name'], $uploadDir . $audioFileName);
    }

    // Insert into the 'projects' table used by the web dashboard
    $sql = "INSERT INTO projects 
            (user_id, title, artist_name, full_name, email, type, genre, date_sortie, status, audio_file, cover_file, authorization, phone, city, languages, details, promo_pack) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $user_id,
        $title,
        $artist_name,
        $full_name,
        $email,
        $project_type,
        $genre,
        $date_sortie,
        'en_attente',
        $audioFileName,
        $coverFileName,
        $authorization,
        $phone,
        $city,
        $languages,
        $details,
        $promo_pack
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Projet soumis avec succès sur le Hub",
        "project_id" => $db->lastInsertId()
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
