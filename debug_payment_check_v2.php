<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/payment_utils.php';

// Correct FlexPay Order Number found in DB for user 1057
$orderNumber = 'dBNLe5Zk2EyP243977734735';
$internalRef = 'SUB-1769262052-1057';

echo "<h1>Diagnostic Paiement - V2</h1>";
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
    
    if ($status === 'SUCCESSFUL' || $status === 'SUCCESS') {
        echo "<div style='background:#d4edda; color:#155724; padding:15px; border-radius:5px; border:1px solid #c3e6cb;'>";
        echo "✅ <b>LE PAIEMENT EST CONFIRMÉ CHEZ FLEXPAY !</b><br>";
        echo "Correction de la base de données en cours...<br>";
        
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT * FROM payments WHERE order_number = ?");
        $stmt->execute([$orderNumber]);
        $payment = $stmt->fetch();
        
        if ($payment) {
            $stmt_u = $db->prepare("SELECT name, email, role FROM users WHERE id = ?");
            $stmt_u->execute([$payment['user_id']]);
            $user = $stmt_u->fetch();
            
            if ($payment['status'] === 'success') {
                echo "⚠️ Ce paiement est DÉJÀ marqué comme succès dans votre base.<br>";
            } else {
                if (processSuccessfulPayment($db, $payment, $user)) {
                    echo "🎉 <b>SUCCÈS : L'abonnement de " . htmlspecialchars($user['name']) . " a été activé !</b><br>";
                    echo "L'utilisateur peut maintenant accéder à son dashboard.";
                } else {
                    echo "❌ Erreur lors de l'activation locale (voir logs).";
                }
            }
        } else {
            echo "❌ Erreur : Paiement introuvable en base locale avec cet Order Number.";
        }
        echo "</div>";
    } else {
        echo "<div style='background:#f8d7da; color:#721c24; padding:15px; border-radius:5px; border:1px solid #f5c6cb;'>";
        echo "❌ Le statut chez FlexPay n'est PAS 'SUCCESS'. Il est : $status.<br>";
        echo "Cela signifie que FlexPay n'a pas validé la transaction, même si l'utilisateur a été débité (problème opérateur).<br>";
        echo "L'utilisateur doit contacter le support FlexPay ou son opérateur.";
        echo "</div>";
    }
} else {
    echo "❌ Structure de réponse invalide de la part de FlexPay.";
}
?>
