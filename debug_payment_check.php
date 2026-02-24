<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/payment_utils.php';

// Order number from the screenshot
$orderNumber = 'SUB-1769262052-1057';

echo "Checking status for: $orderNumber\n";

$ch = curl_init("https://backend.flexpay.cd/api/check/transaction/status/" . $orderNumber);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . FLEXPAY_TOKEN
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";

$result = json_decode($response, true);

if (isset($result['transaction']['status'])) {
    echo "Status chez FlexPay: " . $result['transaction']['status'] . "\n";
    
    if (strtoupper($result['transaction']['status']) === 'SUCCESSFUL' || strtoupper($result['transaction']['status']) === 'SUCCESS') {
        echo "Le paiement EST VALIDE chez FlexPay mais a échoué chez nous.\n";
        echo "Tentative de correction...\n";
        
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT * FROM payments WHERE order_number = ?");
        $stmt->execute([$orderNumber]);
        $payment = $stmt->fetch();
        
        if ($payment) {
            $stmt_u = $db->prepare("SELECT name, email, role FROM users WHERE id = ?");
            $stmt_u->execute([$payment['user_id']]);
            $user = $stmt_u->fetch();
            
            if (processSuccessfulPayment($db, $payment, $user)) {
                echo "CORRECTION RÉUSSIE : Abonnement activé.\n";
            } else {
                echo "ECHEC DE LA CORRECTION.\n";
            }
        } else {
            echo "Paiement non trouvé en base locale.\n";
        }
    }
} else {
    echo "Structure de réponse inconnue.\n";
}
?>
