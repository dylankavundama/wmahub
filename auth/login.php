<?php
require_once __DIR__ . '/../includes/config.php';

// Si déjà connecté, rediriger vers le dashboard approprié
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'artiste') {
        header('Location: ../dashboards/artiste/index.php');
    } elseif ($_SESSION['role'] === 'employe' || $_SESSION['role'] === 'admin') {
        header('Location: ../dashboards/admin/index.php');
    }
    exit;
}

// URL de connexion Google (Version simplifiée sans bibliothèque tierce pour le moment)
$google_login_url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
    'response_type' => 'code',
    'client_id'     => GOOGLE_CLIENT_ID,
    'redirect_uri'  => GOOGLE_REDIRECT_URL,
    'scope'         => 'openid email profile',
    'state'         => bin2hex(random_bytes(16))
]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - WMA Hub</title>
    <link rel="icon" type="image/png" href="../asset/icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
            margin: 0;
            padding: 2rem 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #000;
            overflow-x: hidden;
            overflow-y: auto;
        }

        /* Video Background Container */
        .video-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            pointer-events: none;
            overflow: hidden;
        }

        .video-background iframe {
            position: absolute;
            top: 50%;
            left: 50%;
            min-width: 100%;
            min-height: 100%;
            width: auto;
            height: auto;
            transform: translate(-50%, -50%);
            object-fit: cover;
            filter: brightness(0.4) contrast(1.1);
        }

        /* Overlay to prevent interaction with the video and add texture */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, transparent 20%, rgba(0,0,0,0.4) 100%);
            z-index: 0;
        }

        /* Glassmorphism Card */
        .login-card {
            position: relative;
            z-index: 10;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 2rem;
            border-radius: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            max-width: 400px;
            width: 100%;
            text-align: center;
            animation: fadeIn 1s ease-out;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 1.5rem;
                border-radius: 1.5rem;
            }
            .login-card h1 {
                font-size: 1.5rem;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-card h1 { color: #fff; }
        .login-card p { color: rgba(255, 255, 255, 0.7); }

        .google-btn {
            background: #fff;
            color: #1a1a1a;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            width: 100%;
            justify-content: center;
            margin-top: 1.5rem;
        }

        .google-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(255, 102, 0, 0.3);
            background: #f8f8f8;
        }

        .google-btn img {
            width: 24px;
            height: 24px;
        }

        .footer-text {
            margin-top: 2rem;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.5) !important;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 1.5rem;
        }

        .footer-text a {
            color: #ff6600;
            text-decoration: none;
            font-weight: 500;
        }

        .footer-text a:hover {
            text-decoration: underline;
        }

        .logo-container {
            margin-bottom: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .logo-container img {
            height: 70px;
            filter: drop-shadow(0 0 15px rgba(255, 102, 0, 0.5));
            transition: transform 0.5s ease;
        }

        .logo-container:hover img {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <!-- Video Background -->
    <div class="video-background">
        <iframe 
            src="https://www.youtube.com/embed/R2a9kSeTnBs?autoplay=1&mute=1&loop=1&playlist=R2a9kSeTnBs&controls=0&showinfo=0&rel=0&iv_load_policy=3" 
            frameborder="0" 
            allow="autoplay; encrypted-media" 
            allowfullscreen>
        </iframe>
    </div>
    
    <div class="overlay"></div>

    <div class="login-card">
        <div class="logo-container">
            <img src="../asset/trans.png" alt="WMA Hub">
        </div>
        
        <h1 class="text-3xl font-bold mb-2">Bienvenue sur WMA Hub</h1>
        <p class="mb-6">Propulsez votre talent avec notre plateforme de distribution mondiale.</p>

        <a href="<?= htmlspecialchars($google_login_url) ?>" class="google-btn">
            <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google Logo">
            Se connecter avec Google
        </a>

        <div class="footer-text">
            En vous connectant, vous acceptez nos <br>
            <a href="#">Conditions d'Utilisation</a> & <a href="#">Politique de Confidentialité</a>.
        </div>
    </div>
</body>
</html>
