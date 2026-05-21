<?php
error_reporting(0);
ini_set('display_errors', '0');

require_once __DIR__ . '/../includes/config.php';

if (ob_get_level() > 0) {
    ob_clean();
}

header('Content-Type: application/json; charset=utf-8');

try {
    $db = getDBConnection();

    $userId = $_GET['user_id'] ?? '';

    if (empty($userId)) {
        throw new Exception("user_id parameter is required");
    }

    // Récupérer les infos de l'agent
    $stmt = $db->prepare("SELECT id, name, email, is_active, role, salary FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$agent) {
        throw new Exception("Agent introuvable");
    }

    if ($agent['role'] !== 'employe' && $agent['role'] !== 'admin' && $agent['role'] !== 'superadmin') {
        throw new Exception("L'utilisateur n'est pas un agent/employé.");
    }

    // Calculer les bonus en attente (missions terminées non payées)
    $stmt_bonus = $db->prepare("SELECT SUM(revenue) FROM tasks WHERE user_id = ? AND status = 'termine' AND is_paid = 0");
    $stmt_bonus->execute([$userId]);
    $pending_bonuses = (float)($stmt_bonus->fetchColumn() ?: 0);
    
    $salary = (float)($agent['salary'] ?: 0);
    $total_monthly_revenue = $salary + $pending_bonuses;

    // Récupérer toutes les missions/tâches de l'agent
    $stmt_tasks = $db->prepare("SELECT id, title, description, image_path, revenue, status, is_paid, created_at, rating FROM tasks WHERE user_id = ? ORDER BY created_at DESC");
    $stmt_tasks->execute([$userId]);
    $tasks = $stmt_tasks->fetchAll(PDO::FETCH_ASSOC);

    // Formatter les tâches (chemins d'image complets, conversion de types)
    $formatted_tasks = [];
    foreach ($tasks as $task) {
        $imageUrl = '';
        if (!empty($task['image_path'])) {
            $imageUrl = "https://wmahub.com/" . $task['image_path'];
        }
        $formatted_tasks[] = [
            "id" => (int)$task['id'],
            "title" => $task['title'],
            "description" => $task['description'],
            "image_url" => $imageUrl,
            "revenue" => (float)$task['revenue'],
            "status" => $task['status'],
            "is_paid" => (int)$task['is_paid'],
            "created_at" => $task['created_at'],
            "rating" => (int)$task['rating']
        ];
    }

    // Récupérer l'historique des encaissements
    $stmt_withdraw = $db->prepare("SELECT id, montant, date_encaissement FROM salary_withdrawals WHERE user_id = ? ORDER BY date_encaissement DESC");
    $stmt_withdraw->execute([$userId]);
    $withdrawals = $stmt_withdraw->fetchAll(PDO::FETCH_ASSOC);

    $formatted_withdrawals = [];
    foreach ($withdrawals as $w) {
        $formatted_withdrawals[] = [
            "id" => (int)$w['id'],
            "montant" => (float)$w['montant'],
            "date_encaissement" => $w['date_encaissement']
        ];
    }

    echo json_encode([
        "success" => true,
        "agent" => [
            "id" => (int)$agent['id'],
            "name" => $agent['name'],
            "email" => $agent['email'],
            "is_active" => (int)$agent['is_active'],
            "salary" => $salary,
            "pending_bonuses" => $pending_bonuses,
            "total_monthly_revenue" => $total_monthly_revenue
        ],
        "tasks" => $formatted_tasks,
        "withdrawals" => $formatted_withdrawals
    ]);

} catch (Exception $e) {
    if (ob_get_level() > 0) {
        ob_clean();
    }
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
