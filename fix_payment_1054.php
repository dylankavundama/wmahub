<?php
require_once __DIR__ . '/includes/config.php';

/**
 * Script de correction manuelle pour l'utilisateur Merite Mufungizi (1054).
 * Ce script passe le paiement ID 6 en 'success' et crée l'abonnement correspondant.
 */

try {
    $db = getDBConnection();
    $userId = 1054;
    $paymentId = 6;

    echo "Démarrage de la correction pour l'utilisateur ID $userId...\n";

    $db->beginTransaction();

    // 1. Vérifier le paiement
    $stmt = $db->prepare("SELECT * FROM payments WHERE id = ? AND user_id = ?");
    $stmt->execute([$paymentId, $userId]);
    $payment = $stmt->fetch();

    if (!$payment) {
        throw new Exception("Paiement ID $paymentId non trouvé pour l'utilisateur $userId.");
    }

    if ($payment['status'] === 'success') {
        echo "Le paiement est déjà marqué comme réussi. Aucune action nécessaire.\n";
        $db->rollBack();
        exit;
    }

    // 2. Mettre à jour le paiement
    $stmt_upd = $db->prepare("UPDATE payments SET status = 'success' WHERE id = ?");
    $stmt_upd->execute([$paymentId]);
    echo "Statut du paiement mis à jour en 'success'.\n";

    // 3. Calculer les dates d'abonnement
    $months = ($payment['plan_type'] === 'annual') ? 12 : 1;
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime("+$months months"));

    // 4. Créer l'abonnement
    $stmt_sub = $db->prepare("INSERT INTO subscriptions (user_id, plan_type, amount, currency, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
    $stmt_sub->execute([
        $userId,
        $payment['plan_type'] ?: 'monthly',
        $payment['amount'],
        $payment['currency'],
        $startDate,
        $endDate
    ]);
    echo "Abonnement " . ($payment['plan_type'] ?: 'mensuel') . " créé jusqu'au $endDate.\n";

    // 5. Créer une notification
    $notifMsg = "Félicitations ! Votre abonnement a été activé manuellement jusqu'au " . date('d/m/Y', strtotime($endDate));
    $stmt_notif = $db->prepare("INSERT INTO notifications (user_id, type, message, reference_id) VALUES (?, 'success', ?, ?)");
    $stmt_notif->execute([$userId, $notifMsg, $paymentId]);
    echo "Notification envoyée à l'utilisateur.\n";

    $db->commit();
    echo "\nCorrection terminée avec succès !\n";

} catch (Exception $e) {
    if (isset($db)) $db->rollBack();
    die("\nERREUR : " . $e->getMessage() . "\n");
}
?>
