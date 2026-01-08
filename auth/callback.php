<?php
require_once __DIR__ . '/../includes/config.php';

// Cette page reçoit le code d'autorisation de Google
if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // Dans une implémentation réelle, on échangerait le code contre un token ici.
    // Pour cet exemple, nous allons simuler l'authentification réussie
    // car nous n'avons pas encore les identifiants Client ID/Secret valides.
    
    // SIMULATION : On récupère des infos bidons
    // Remplacez cette partie par l'appel à l'API Google
    $google_id = "simulated_" . bin2hex(random_bytes(8));
    $email = "user@" . bin2hex(random_bytes(4)) . ".com";
    $name = "Artiste Simulation";

    $db = getDBConnection();
    
    // Vérifier si l'utilisateur existe déjà
    $stmt = $db->prepare("SELECT * FROM users WHERE google_id = ? OR email = ?");
    $stmt->execute([$google_id, $email]);
    $user = $stmt->fetch();

    if (!$user) {
        // Créer un nouvel utilisateur
        $stmt = $db->prepare("INSERT INTO users (google_id, name, email) VALUES (?, ?, ?)");
        $stmt->execute([$google_id, $name, $email]);
        $user_id = $db->lastInsertId();
        
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['role'] = null; // Il devra choisir son rôle
        
        header('Location: select-role.php');
    } else {
        // Utilisateur existant
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        
        if ($user['role'] === null) {
            header('Location: select-role.php');
        } elseif ($user['role'] === 'artiste') {
            header('Location: ../dashboards/artiste/index.php');
        } elseif ($user['role'] === 'employe') {
            if ($user['is_active']) {
                header('Location: ../dashboards/admin/index.php');
            } else {
                header('Location: pending.php');
            }
        } else {
            header('Location: ../dashboards/admin/index.php');
        }
    }
    exit;
} else {
    // Si pas de code, retour au login
    header('Location: login.php');
    exit;
}
?>
