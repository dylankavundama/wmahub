<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

try {
    $db = getDBConnection();
    
    $user_id = $_GET['user_id'] ?? 0;

    if (empty($user_id)) {
        throw new Exception("User ID is required");
    }

    // Récupération du solde actuel
    $stmt_balance = $db->prepare("SELECT balance_usd, balance_cdf FROM users WHERE id = ?");
    $stmt_balance->execute([$user_id]);
    $balance = $stmt_balance->fetch(PDO::FETCH_ASSOC);

    // Récupération de l'historique des revenus (depuis une table de transactions si elle existe, sinon on simule ou on cherche dans les paiements)
    // Ici on cherche dans 'payout_requests' et 'accounting_transactions' liés à l'utilisateur si possible
    // Pour cet exemple, nous allons chercher les revenus distribués
    $stmt_trans = $db->prepare("SELECT amount, type, status, created_at as date, 'Distribution Royalties' as description 
                               FROM payout_requests 
                               WHERE user_id = ? 
                               ORDER BY created_at DESC LIMIT 20");
    $stmt_trans->execute([$user_id]);
    $transactions = $stmt_trans->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => [
            "balance_usd" => (float)($balance['balance_usd'] ?? 0),
            "balance_cdf" => (float)($balance['balance_cdf'] ?? 0),
            "transactions" => $transactions
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
