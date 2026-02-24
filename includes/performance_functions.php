<?php
/**
 * WMA HUB - Performance & Archiving Functions
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';

/**
 * Calcule et archive les performances mensuelles le 28 de chaque mois.
 */
function processMonthlyAwards() {
    $db = getDBConnection();
    $today = date('Y-m-d');
    $day = (int)date('d');
    $monthKey = date('Y-m');

    // On ne traite que le 28 (ou après si pas encore fait ce mois)
    if ($day < 28) return;

    // Vérifier si le traitement a déjà été fait pour ce mois
    $stmt = $db->prepare("SELECT id FROM monthly_awards WHERE month = ? LIMIT 1");
    $stmt->execute([$monthKey]);
    if ($stmt->fetch()) return;

    // Récupérer tous les employés actifs
    $employees = $db->query("SELECT id, name, email FROM users WHERE role = 'employe' AND is_active = 1")->fetchAll();
    
    $stats = [];
    foreach ($employees as $emp) {
        // Moyenne des tâches non archivées
        $stmt_t = $db->prepare("SELECT AVG(rating) FROM tasks WHERE user_id = ? AND status = 'termine' AND is_archived = 0");
        $stmt_t->execute([$emp['id']]);
        $avg_t = $stmt_t->fetchColumn() ?: 0;

        // Moyenne des évaluations admin non archivées
        $stmt_e = $db->prepare("SELECT AVG(rating) FROM evaluations WHERE employee_id = ? AND is_archived = 0");
        $stmt_e->execute([$emp['id']]);
        $avg_e = $stmt_e->fetchColumn() ?: 0;

        $final = 0;
        if ($avg_t > 0 && $avg_e > 0) $final = ($avg_t + $avg_e) / 2;
        else $final = max($avg_t, $avg_e);

        if ($final > 0) {
            $stats[] = [
                'id' => $emp['id'],
                'name' => $emp['name'],
                'email' => $emp['email'],
                'score' => $final
            ];
        }
    }

    // Trier par score décroissant
    usort($stats, function($a, $b) {
        return $b['score'] <=> $a['score'];
    });

    // Prendre les 3 meilleurs
    $top3 = array_slice($stats, 0, 3);
    
    foreach ($top3 as $index => $winner) {
        $position = $index + 1;
        
        // Enregistrer l'award
        $ins = $db->prepare("INSERT INTO monthly_awards (month, employee_id, position, score) VALUES (?, ?, ?, ?)");
        $ins->execute([$monthKey, $winner['id'], $position, $winner['score']]);

        // Envoyer l'email
        notifyMonthlyAward($winner['email'], $winner['name'], $position, date('F Y'));
    }

    // ARCHIVAGE : on passe tout à is_archived = 1
    $db->exec("UPDATE tasks SET is_archived = 1 WHERE status = 'termine' AND is_archived = 0");
    $db->exec("UPDATE evaluations SET is_archived = 1 WHERE is_archived = 0");

    return true;
}

/**
 * Récupère le score actuel d'un employé (basé sur les données non archivées)
 */
function getEmployeeCurrentScore($employee_id) {
    $db = getDBConnection();
    
    $stmt_t = $db->prepare("SELECT AVG(rating) FROM tasks WHERE user_id = ? AND status = 'termine' AND is_archived = 0");
    $stmt_t->execute([$employee_id]);
    $avg_t = $stmt_t->fetchColumn() ?: 0;

    $stmt_e = $db->prepare("SELECT AVG(rating) FROM evaluations WHERE employee_id = ? AND is_archived = 0");
    $stmt_e->execute([$employee_id]);
    $avg_e = $stmt_e->fetchColumn() ?: 0;

    if ($avg_t > 0 && $avg_e > 0) return round(($avg_t + $avg_e) / 2, 1);
    return round(max($avg_t, $avg_e), 1);
}
