<?php
/**
 * Vérifie si l'utilisateur (artiste) a un abonnement actif
 */
function hasActiveSubscription($userId) {
    // Si l'utilisateur est un artiste, l'accès est désormais 100% gratuit
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'artiste') {
        return true;
    }

    try {
        $db = getDBConnection();
        // ... (reste du code existant pour les autres rôles si nécessaire)
        $stmt = $db->prepare("SELECT id, end_date FROM subscriptions WHERE user_id = ? AND status = 'active' AND end_date >= CURDATE() LIMIT 1");
        $stmt->execute([$userId]);
        $subscription = $stmt->fetch();
        
        if ($subscription) {
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        return false;
    }
}
?>
