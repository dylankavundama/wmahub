<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

try {
    $db = getDBConnection();
    
    // On pourrait ajouter une vérification de PIN ici aussi pour plus de sécurité
    
    $amount = $_POST['amount'] ?? 0;
    $type = $_POST['type'] ?? 'income';
    $category = $_POST['category'] ?? 'Autre';
    $description = $_POST['description'] ?? '';
    $date = $_POST['date'] ?? date('Y-m-d');

    if (empty($amount)) {
        throw new Exception("Le montant est obligatoire");
    }

    $stmt = $db->prepare("INSERT INTO accounting_transactions (amount, type, category, description, transaction_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$amount, $type, $category, $description, $date]);

    echo json_encode([
        "success" => true,
        "message" => "Transaction enregistrée avec succès"
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
