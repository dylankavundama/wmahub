<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/payment_utils.php';

// Fonction de log dédiée (définie en premier)
function logFlexPay($message) {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    $logFile = $logDir . '/flexpay.log';
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}

// Log entrée du callback
logFlexPay("=== CALLBACK ENTRY ===");

// Récupérer le contenu JSON envoyé par FlexPay
$json = file_get_contents('php://input');
logFlexPay("RAW INPUT: " . $json);

$data = json_decode($json, true);

if (!$data || !isset($data['orderNumber'])) {
    logFlexPay("ERROR: Invalid payload - missing orderNumber");
    http_response_code(400);
    exit("Données invalides.");
}

logFlexPay("Order Number: " . $data['orderNumber']);

$db = getDBConnection();

// Chercher le paiement correspondant
$stmt = $db->prepare("SELECT * FROM payments WHERE order_number = ?");
$stmt->execute([$data['orderNumber']]);
$payment = $stmt->fetch();

if (!$payment) {
    logFlexPay("ERROR: Payment not found for orderNumber: " . $data['orderNumber']);
    http_response_code(404);
    exit("Paiement non trouvé.");
}

logFlexPay("Payment found - ID: " . $payment['id'] . ", User: " . $payment['user_id'] . ", Current Status: " . $payment['status']);

// Récupérer les infos de l'utilisateur pour les notifications
$stmt_user = $db->prepare("SELECT name, email, role FROM users WHERE id = ?");
$stmt_user->execute([$payment['user_id']]);
$user = $stmt_user->fetch();

if (!$user) {
    logFlexPay("ERROR: User not found for ID: " . $payment['user_id']);
    http_response_code(404);
    exit("Utilisateur non trouvé.");
}

logFlexPay("User found - Name: " . $user['name'] . ", Email: " . $user['email']);

// Vérifier le code de réponse FlexPay (comme dans Gayux)
$code = isset($data['code']) ? trim($data['code']) : null;
$status = isset($data['status']) ? strtoupper(trim($data['status'])) : '';
logFlexPay("Response received - Code: '" . ($code ?? 'NOT SET') . "', Status: '" . $status . "' (original: '" . ($data['status'] ?? 'NOT SET') . "')");

// FlexPay envoie code='0' pour succès (priorité sur le statut)
if ($code === '0' || $code === 0 || $status === 'SUCCESS' || $status === 'SUCCESSFUL' || strpos($status, 'SUCCESS') === 0) {
    logFlexPay("Payment SUCCESS detected (code='$code', status='$status') - attempting to process");
    
    if (processSuccessfulPayment($db, $payment, $user)) {
        logFlexPay("SUCCESS: Payment ID " . $payment['id'] . " processed for User " . $payment['user_id']);
        echo "Paiement validé avec succès.";
    } else {
        logFlexPay("ERROR: Failed to process payment ID " . $payment['id'] . " - processSuccessfulPayment returned false");
        http_response_code(500);
        exit("Erreur interne lors de l'activation.");
    }
} else {
    logFlexPay("FAILED: Payment failed (code='$code', status='$status') - marking as failed");
    
    // Échec du paiement
    $stmt_fail = $db->prepare("UPDATE payments SET status = 'failed' WHERE id = ?");
    $stmt_fail->execute([$payment['id']]);
    
    // Notifier l'équipe admin de l'échec
    notifyAdmin('payment', 'Paiement Échoué', [
        'Utilisateur' => $user['name'] ?? 'Inconnu',
        'Type' => $payment['payment_type'] ?? 'Inconnu',
        'Montant' => number_format($payment['amount'], 2) . ' ' . $payment['currency'],
        'Référence' => $payment['reference'],
        'Code FlexPay' => $code ?? 'N/A',
        'Statut' => $status
    ]);
    
    logFlexPay("Admin notified of failed payment");
    echo "Paiement échoué.";
}

logFlexPay("=== CALLBACK END ===");
?>
