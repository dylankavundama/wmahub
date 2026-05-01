<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

// Sécurité : Idéalement, vérifier un token admin ici
// Pour ce test, nous récupérons les données globales

try {
    $db = getDBConnection();

    // Stats globales
    $total_income = $db->query("SELECT SUM(amount) FROM accounting_transactions WHERE type = 'income'")->fetchColumn() ?: 0;
    $total_expense = $db->query("SELECT SUM(amount) FROM accounting_transactions WHERE type = 'expense'")->fetchColumn() ?: 0;
    $balance = $total_income - $total_expense;

    // Dernières transactions
    $stmt = $db->query("SELECT * FROM accounting_transactions ORDER BY transaction_date DESC, created_at DESC LIMIT 20");
    $transactions = $stmt->fetchAll();

    echo json_encode([
        "success" => true,
        "data" => [
            "stats" => [
                "balance" => (float)$balance,
                "total_income" => (float)$total_income,
                "total_expense" => (float)$total_expense
            ],
            "transactions" => $transactions
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
