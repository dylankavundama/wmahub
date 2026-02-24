<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/payment_utils.php';

/**
 * API pour vérifier le statut d'un paiement en temps réel.
 * Appelée par le frontend lors de l'attente d'un paiement Push.
 */

// Fonction de log pour l'API
function logPaymentCheck($message) {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    $logFile = $logDir . '/payment_checks.log';
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}

if (!isset($_GET['orderNumber']) && !isset($_GET['reference'])) {
    logPaymentCheck("ERROR: Missing orderNumber or reference parameter");
    echo json_encode(['status' => 'error', 'message' => 'Paramètre manquant (orderNumber ou reference)']);
    exit;
}

$db = getDBConnection();
$payment = null;

// 1. Recherche du paiement en local
if (isset($_GET['orderNumber'])) {
    $orderNumber = $_GET['orderNumber'];
    logPaymentCheck("CHECK REQUEST: orderNumber=" . $orderNumber);
    $stmt = $db->prepare("SELECT p.*, u.name, u.email, u.role FROM payments p JOIN users u ON p.user_id = u.id WHERE p.order_number = ?");
    $stmt->execute([$orderNumber]);
    $payment = $stmt->fetch();
} elseif (isset($_GET['reference'])) {
    $reference = $_GET['reference'];
    logPaymentCheck("CHECK REQUEST: reference=" . $reference);
    $stmt = $db->prepare("SELECT p.*, u.name, u.email, u.role FROM payments p JOIN users u ON p.user_id = u.id WHERE p.reference = ?");
    $stmt->execute([$reference]);
    $payment = $stmt->fetch();
}

if (!$payment) {
    logPaymentCheck("ERROR: Payment not found locally");
    echo json_encode(['status' => 'error', 'message' => 'Paiement non trouvé']);
    exit;
}

logPaymentCheck("Payment found - ID: " . $payment['id'] . ", Status: " . $payment['status'] . ", OrderNum: " . $payment['order_number']);

// Si déjà succès, on renvoie succès direct
if ($payment['status'] === 'success') {
    logPaymentCheck("Payment already validated locally");
    echo json_encode(['status' => 'success', 'message' => 'Paiement déjà validé']);
    exit;
}

// 2. Vérification avec l'API FlexPay (si pas success local)
// Remarque: L'API FlexPay utilise l'orderNumber pour la vérification
$checkOrderNumber = $payment['order_number'];
if (empty($checkOrderNumber)) {
    logPaymentCheck("ERROR: No orderNumber available for API check");
    echo json_encode(['status' => 'failed', 'message' => 'Numéro de commande introuvable pour vérification']);
    exit;
}

logPaymentCheck("Checking with FlexPay API for OrderNumber: " . $checkOrderNumber);
$ch = curl_init("https://backend.flexpay.cd/api/check/transaction/status/" . $checkOrderNumber);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . FLEXPAY_TOKEN
]);
// Timeout pour éviter de bloquer trop longtemps
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    logPaymentCheck("CURL ERROR: " . $curlError);
}

logPaymentCheck("FlexPay API response - HTTP Code: " . $httpCode . ", Response: " . $response);

if ($httpCode === 200) {
    $result = json_decode($response, true);

    if (isset($result['transaction']) && isset($result['transaction']['status'])) {
        $remoteStatus = strtoupper(trim($result['transaction']['status']));
        logPaymentCheck("Remote status: " . $remoteStatus);
        
        // Comme dans Gayux: SUCCESS, SUCCESSFUL, ou code 0
        if ($remoteStatus === 'SUCCESS' || $remoteStatus === 'SUCCESSFUL' || strpos($remoteStatus, 'SUCCESS') === 0) {
            logPaymentCheck("Remote confirms SUCCESS - activating payment");
            
            // Activer le paiement via notre utilitaire partagé
            if (processSuccessfulPayment($db, $payment, $payment)) {
                logPaymentCheck("SUCCESS: Payment activated via polling");
                echo json_encode(['status' => 'success', 'message' => 'Paiement validé avec succès']);
                exit;
            } else {
                logPaymentCheck("ERROR: processSuccessfulPayment failed");
                echo json_encode(['status' => 'error', 'message' => 'Erreur lors de l\'activation']);
                exit;
            }
        } elseif ($remoteStatus === 'FAILED' || $remoteStatus === 'REFUSED') {
             // Mettre à jour en failed si ce n'est pas déjà le cas
             if ($payment['status'] !== 'failed') {
                 $stmt = $db->prepare("UPDATE payments SET status = 'failed' WHERE id = ?");
                 $stmt->execute([$payment['id']]);
                 logPaymentCheck("Updated local status to FAILED based on remote status");
             }
             echo json_encode(['status' => 'failed', 'message' => 'Paiement échoué chez le fournisseur']);
             exit;
        } else {
            logPaymentCheck("Remote status is pending or other: " . $remoteStatus);
        }
    } else {
        logPaymentCheck("ERROR: Unexpected API response structure: " . json_encode($result));
    }
} else {
    logPaymentCheck("ERROR: FlexPay API returned HTTP " . $httpCode);
}

logPaymentCheck("Payment still pending");
echo json_encode(['status' => 'pending', 'message' => 'Paiement toujours en attente']);
?>
