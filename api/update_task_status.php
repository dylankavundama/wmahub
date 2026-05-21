<?php
error_reporting(0);
ini_set('display_errors', '0');

require_once __DIR__ . '/../includes/config.php';

if (ob_get_level() > 0) {
    ob_clean();
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}

try {
    $db = getDBConnection();

    $taskId = $_POST['task_id'] ?? '';
    $userId = $_POST['user_id'] ?? '';
    $status = $_POST['status'] ?? ''; // e.g. en_cours, termine

    if (empty($taskId) || empty($userId) || empty($status)) {
        throw new Exception("task_id, user_id and status are required");
    }

    if (!in_array($status, ['assignee', 'en_cours', 'termine'])) {
        throw new Exception("Statut invalide.");
    }

    // Récupérer la tâche actuelle et vérifier qu'elle appartient à l'agent
    $stmt = $db->prepare("SELECT id, title, user_id, status FROM tasks WHERE id = ? AND user_id = ? LIMIT 1");
    $stmt->execute([$taskId, $userId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        throw new Exception("Tâche introuvable ou non assignée à cet utilisateur.");
    }

    // Récupérer les infos de l'utilisateur pour les notifications
    $stmt_user = $db->prepare("SELECT id, name FROM users WHERE id = ? LIMIT 1");
    $stmt_user->execute([$userId]);
    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

    // Mettre à jour la tâche (en adaptant le completed_at et rating comme sur le web)
    $updateStmt = $db->prepare("UPDATE tasks SET status = ?, completed_at = CASE WHEN ? = 'termine' THEN CURRENT_TIMESTAMP ELSE completed_at END, rating = CASE WHEN ? = 'termine' AND rating IS NULL THEN 3 ELSE rating END WHERE id = ? AND user_id = ?");
    $updateStmt->execute([$status, $status, $status, $taskId, $userId]);

    if ($status === 'termine') {
        // Notifier les administrateurs
        try {
            $admins = $db->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll(PDO::FETCH_ASSOC);
            require_once __DIR__ . '/../includes/mailer.php';
            
            $userName = $user ? $user['name'] : "Employé Mobile";
            
            foreach ($admins as $admin) {
                createNotification($admin['id'], 'task_update', "L'employé " . $userName . " a terminé la mission : " . $task['title'], $taskId);
                
                notifyAdmin('employee', "Mission terminée par " . $userName, [
                    'Mission' => $task['title'],
                    'Employé' => $userName,
                    'Status' => 'TERMINÉ ✓'
                ], "https://wmahub.com/dashboards/admin/task_chat.php?id=" . $taskId);
            }
        } catch (Exception $notifyEx) {
            error_log("Failed to notify admins of task completion: " . $notifyEx->getMessage());
        }
    }

    echo json_encode([
        "success" => true,
        "message" => "Statut de la tâche mis à jour avec succès",
        "task" => [
            "id" => (int)$taskId,
            "status" => $status
        ]
    ]);

} catch (Exception $e) {
    if (ob_get_level() > 0) {
        ob_clean();
    }
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
