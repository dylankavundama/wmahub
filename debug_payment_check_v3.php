<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/payment_utils.php';

// Correct FlexPay Order Number found in DB for user 1057
$orderNumber = 'dBNLe5Zk2EyP243977734735';
$internalRef = 'SUB-1769262052-1057';

if (isset($_POST['force_validate'])) {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM payments WHERE order_number = ?");
    $stmt->execute([$orderNumber]);
    $payment = $stmt->fetch();
    
    if ($payment) {
        $stmt_u = $db->prepare("SELECT name, email, role FROM users WHERE id = ?");
        $stmt_u->execute([$payment['user_id']]);
        $user = $stmt_u->fetch();
        
        if (processSuccessfulPayment($db, $payment, $user)) {
            echo "<div style='background:#d4edda; color:#155724; padding:20px; border-radius:10px; margin-bottom:20px; font-size:1.2em;'>";
            echo "🎉 <b>FORÇAGE RÉUSSI : L'abonnement de " . htmlspecialchars($user['name']) . " a été activé !</b><br>";
            echo "L'utilisateur peut maintenant accéder à son dashboard.";
            echo "</div>";
        } else {
            echo "❌ Erreur lors de l'activation locale.";
        }
    } else {
        echo "❌ Paiement introuvable en base.";
    }
}

echo "<h1>Diagnostic Paiement - V3 (Force Validate)</h1>";
echo "Vérification de l'Order Number FlexPay : <b>$orderNumber</b><br>";
echo "Référence Interne : $internalRef<br><hr>";

$ch = curl_init("https://backend.flexpay.cd/api/check/transaction/status/" . $orderNumber);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . FLEXPAY_TOKEN
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<b>Réponse API FlexPay (HTTP $httpCode) :</b><br>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

$result = json_decode($response, true);

if (isset($result['transaction']['status'])) {
    $status = strtoupper($result['transaction']['status']);
    echo "<h3>Statut détecté : <span style='color:blue'>$status</span></h3>";
} else {
    echo "<h3>Statut détecté : <span style='color:red'>INCONNU (404 ou Erreur)</span></h3>";
    echo "<p>L'API FlexPay ne trouve pas cette transaction. Cela arrive parfois (timeout opérateur).</p>";
    echo "<p>Puisque vous avez la preuve SMS, vous pouvez forcer la validation ci-dessous :</p>";
    
    echo "<form method='POST'>";
    echo "<button type='submit' name='force_validate' style='background:red; color:white; padding:15px 30px; font-size:18px; border:none; border-radius:5px; cursor:pointer;'>";
    echo "⚠️ FORCER LA VALIDATION MAINTENANT";
    echo "</button>";
    echo "</form>";
}
?>
