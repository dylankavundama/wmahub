<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

try {
    $db = getDBConnection();

    // 1. Increment today's visits atomically
    $stmt = $db->prepare("
        INSERT INTO visite_ua (visit_date, visits) 
        VALUES (CURRENT_DATE(), 1) 
        ON DUPLICATE KEY UPDATE visits = visits + 1
    ");
    $stmt->execute();

    // 2. Fetch today's visits
    $today_stmt = $db->prepare("SELECT visits FROM visite_ua WHERE visit_date = CURRENT_DATE()");
    $today_stmt->execute();
    $today = $today_stmt->fetchColumn();
    if ($today === false) {
        $today = 0;
    }

    // 3. Fetch total visits
    $total_stmt = $db->prepare("SELECT SUM(visits) AS total FROM visite_ua");
    $total_stmt->execute();
    $total = $total_stmt->fetchColumn();
    if ($total === false || $total === null) {
        $total = 100;
    }

    echo json_encode([
        "success" => true,
        "total" => intval($total),
        "today" => intval($today)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
