<?php
require_once __DIR__ . '/../includes/config.php';

// Cette page reçoit le code d'autorisation de Google
if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // Échange du code contre un token d'accès
    $token_url = 'https://oauth2.googleapis.com/token';
    $params = [
        'code'          => $code,
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => GOOGLE_REDIRECT_URL,
        'grant_type'    => 'authorization_code'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $token_data = json_decode($response, true);

    if (isset($token_data['error'])) {
        die("Erreur lors de l'obtention du token : " . $token_data['error_description']);
    }

    $access_token = $token_data['access_token'];

    // Récupération des informations de l'utilisateur
    $user_info_url = 'https://www.googleapis.com/oauth2/v3/userinfo?access_token=' . $access_token;
    $user_info_response = file_get_contents($user_info_url);
    $user_data = json_decode($user_info_response, true);

    if (!$user_data || !isset($user_data['sub'])) {
        die("Impossible de récupérer les informations de l'utilisateur.");
    }

    $google_id = $user_data['sub'];
    $email = $user_data['email'];
    $name = $user_data['name'];
    $picture = $user_data['picture'] ?? '';

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
        // Mettre à jour les infos si nécessaire
        $stmt = $db->prepare("UPDATE users SET google_id = ?, name = ? WHERE id = ?");
        $stmt->execute([$google_id, $name, $user['id']]);

        // Utilisateur existant
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        // Log de connexion
        try {
            $stmt_log = $db->prepare("INSERT INTO login_logs (user_id) VALUES (?)");
            $stmt_log->execute([$user['id']]);
        } catch (Exception $e) {}
        
        if ($user['role'] === null) {
            header('Location: select-role.php');
        } elseif ($user['role'] === 'artiste') {
            header('Location: ../dashboards/artiste/index.php');
        } elseif ($user['role'] === 'employe') {
            if ($user['is_active']) {
                header('Location: ../dashboards/employe/index.php');
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
