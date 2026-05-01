<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

try {
    $db = getDBConnection();
    
    // On pourrait vérifier le rôle ici si on passe le user_id en paramètre
    // Pour l'instant on récupère tout pour l'admin
    
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? 'all';

    $query = "SELECT p.*, u.name as artist_name_user FROM projects p JOIN users u ON p.user_id = u.id WHERE 1=1";
    $params = [];

    if (!empty($search)) {
        $query .= " AND (p.title LIKE ? OR u.name LIKE ? OR p.artist_name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($status !== 'all') {
        $query .= " AND p.status = ?";
        $params[] = $status;
    }

    $query .= " ORDER BY CASE WHEN p.status = 'en_attente' THEN 0 WHEN p.status = 'en_preparation' THEN 1 ELSE 2 END ASC, p.id DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $projects = $stmt->fetchAll();

    echo json_encode([
        "success" => true,
        "projects" => $projects
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
