<?php
require_once __DIR__ . '/../includes/config.php';

// Cette page reçoit le code d'autorisation de Google
if (isset($_GET['code'])) {
    $code = $_GET['code'];
    $state = $_GET['state'] ?? '';

    // Validation du state pour prévenir les attaques CSRF
    if (empty($state) || !isset($_SESSION['oauth_state']) || $state !== $_SESSION['oauth_state']) {
        header('Location: login.php?error=invalid_state');
        exit;
    }
    
    // Une fois validé, on supprime le state de la session
    unset($_SESSION['oauth_state']);

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

    // Récupération des informations de l'utilisateur via cURL
    $user_info_url = 'https://www.googleapis.com/oauth2/v3/userinfo?access_token=' . $access_token;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $user_info_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'); // Ajout d'un User-Agent
    $user_info_response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $user_data = json_decode($user_info_response, true);

    if (!$user_data || !isset($user_data['sub'])) {
        error_log("Google Auth Error: HTTP $http_code | cURL Error: $curl_error | Response: $user_info_response");
        die("Impossible de récupérer les informations de l'utilisateur. <br> <b>Détails techniques :</b> HTTP $http_code - $curl_error");
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
        
        // Notifier l'équipe admin par email
        require_once __DIR__ . '/../includes/mailer.php';
        notifyAdmin('registration', 'Nouvel Utilisateur Inscrit', [
            'Nom' => $name,
            'Email' => $email,
            'Méthode' => 'Google OAuth',
            'Date' => date('d/m/Y H:i')
        ], 'https://wmahub.com/dashboards/admin/users.php');
        
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
        } elseif ($user['role'] === 'distributeur') {
            // Définir un flag pour le message de bienvenue si certifié
            if ($user['is_certified']) {
                $_SESSION['show_welcome_certified'] = true;
            }
            header('Location: ../dashboards/distributeur/index.php');
        } elseif ($user['role'] === 'employe') {
            if ($user['is_active']) {
                header('Location: ../dashboards/employe/index.php');
            } else {
                header('Location: pending.php');
            }
        } elseif ($user['role'] === 'superadmin') {
            header('Location: ../dashboards/superadmin/index.php');
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
