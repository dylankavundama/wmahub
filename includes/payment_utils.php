<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';

/**
 * Traite un paiement réussi et active les droits correspondants.
 */
function processSuccessfulPayment($db, $payment, $user) {
    if ($payment['status'] === 'success') return true;

    $db->beginTransaction();
    try {
        // 1. Mettre à jour le statut du paiement
        $stmt_pay = $db->prepare("UPDATE payments SET status = 'success' WHERE id = ?");
        $stmt_pay->execute([$payment['id']]);

        if (isset($payment['payment_type']) && $payment['payment_type'] === 'certification') {
            // Mettre à jour l'utilisateur comme certifié
            $stmt_cert = $db->prepare("UPDATE users SET is_certified = 1 WHERE id = ?");
            $stmt_cert->execute([$payment['user_id']]);
            
            createNotification($payment['user_id'], 'success', "Félicitations ! Votre compte est désormais certifié.");
            
            notifyAdmin('certification', 'Nouvelle Certification Distributeur', [
                'Distributeur' => $user['name'] ?? 'Inconnu',
                'Email' => $user['email'] ?? 'N/A',
                'Montant' => number_format($payment['amount'], 2) . ' ' . $payment['currency'],
                'Référence' => $payment['reference']
            ], 'https://wmahub.com/dashboards/admin/distributors.php');
            
            notifySubscriptionTeam('certification', $user['name'], $user['email'], $user['role'], $payment['amount'], $payment['currency'], $payment['reference']);

        } elseif (isset($payment['payment_type']) && $payment['payment_type'] === 'service_card') {
            // Mettre à jour le statut de la demande de carte
            $stmt_card = $db->prepare("UPDATE service_cards SET status = 'pending' WHERE user_id = ? AND status = 'pending_payment'");
            $stmt_card->execute([$payment['user_id']]);
            
            createNotification($payment['user_id'], 'success', "Paiement reçu ! Votre carte de service est en cours de validation.");
            
            notifyAdmin('service_card', 'Nouvelle Demande de Carte de Service', [
                'Utilisateur' => $user['name'] ?? 'Inconnu',
                'Email' => $user['email'] ?? 'N/A',
                'Montant' => number_format($payment['amount'], 2) . ' ' . $payment['currency'],
                'Référence' => $payment['reference']
            ], 'https://wmahub.com/dashboards/admin/service_cards.php');
            
            notifySubscriptionTeam('service_card', $user['name'], $user['email'], $user['role'], $payment['amount'], $payment['currency'], $payment['reference']);

        } else {
            // Logique d'abonnement standard (Smart Renewal)
            $planType = $payment['plan_type'] ?: 'monthly';
            $months = ($planType === 'annual') ? 12 : 1;

            // Smart Renewal: Vérifier s'il y a déjà un abonnement actif pour étendre la date
            $stmt_check_sub = $db->prepare("SELECT end_date FROM subscriptions WHERE user_id = ? AND status = 'active' AND end_date > CURDATE() ORDER BY end_date DESC LIMIT 1");
            $stmt_check_sub->execute([$payment['user_id']]);
            $existing_sub = $stmt_check_sub->fetch();

            if ($existing_sub) {
                // Prolonge à partir de la fin de l'abonnement actuel
                $startDate = $existing_sub['end_date'];
            } else {
                // Nouvel abonnement (commence aujourd'hui)
                $startDate = date('Y-m-d');
            }
            
            $endDate = date('Y-m-d', strtotime("+$months months", strtotime($startDate)));

            // Créer l'abonnement
            $stmt_sub = $db->prepare("INSERT INTO subscriptions (user_id, plan_type, amount, currency, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
            $stmt_sub->execute([
                $payment['user_id'],
                $planType,
                $payment['amount'],
                $payment['currency'],
                $startDate,
                $endDate
            ]);

            $msg = "Félicitations ! Votre abonnement " . ($planType === 'monthly' ? 'mensuel' : 'annuel') . " est activé jusqu'au " . date('d/m/Y', strtotime($endDate));
            createNotification($payment['user_id'], 'success', $msg);
            
            // Envoyer un email de confirmation à l'utilisateur
            if (!empty($user['email'])) {
                sendEmail(
                    $user['email'], 
                    "Activations d'abonnement - WMA Hub", 
                    "Bonjour " . htmlspecialchars($user['name']) . ",<br><br>" .
                    "Nous avons bien reçu votre paiement de <b>" . number_format($payment['amount'], 2) . " " . $payment['currency'] . "</b>.<br>" .
                    "Votre abonnement <b>" . ucfirst($planType) . "</b> a été activé avec succès.<br>" .
                    "Il est valide du <b>" . date('d/m/Y', strtotime($startDate)) . "</b> au <b>" . date('d/m/Y', strtotime($endDate)) . "</b>.<br><br>" .
                    "Merci de votre confiance,<br>L'équipe WMA Hub."
                );
            }
            
            $roleLabel = ($user['role'] === 'distributeur') ? 'Distributeur' : 'Artiste';
            notifyAdmin('subscription', 'Nouvel Abonnement ' . ucfirst($planType), [
                $roleLabel => $user['name'] ?? 'Inconnu',
                'Email' => $user['email'] ?? 'N/A',
                'Plan' => ucfirst($planType),
                'Montant' => number_format($payment['amount'], 2) . ' ' . $payment['currency'],
                'Expire le' => date('d/m/Y', strtotime($endDate)),
                'Référence' => $payment['reference']
            ], 'https://wmahub.com/dashboards/admin/subscriptions.php');
            
            notifySubscriptionTeam('subscription', $user['name'], $user['email'], $user['role'], $payment['amount'], $payment['currency'], $payment['reference'], [
                'Plan' => ucfirst($planType), 
                'Expire le' => date('d/m/Y', strtotime($endDate))
            ]);
        }

        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error processing payment: " . $e->getMessage());
        return false;
    }
}
?>
