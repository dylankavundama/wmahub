<?php
require_once __DIR__ . '/../includes/config.php';

// Vérifier si l'utilisateur est authentifié mais sans rôle
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['role'] !== null) {
    header('Location: ../index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';
    if (in_array($role, ['artiste', 'employe'])) {
        $db = getDBConnection();
        $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$role, $_SESSION['user_id']]);
        
        $_SESSION['role'] = $role;
        
        if ($role === 'artiste') {
            header('Location: ../dashboards/artiste/index.php');
        } else {
            header('Location: pending.php');
        }
        exit;
    } else {
        $error = 'Veuillez sélectionner un rôle valide.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choisissez votre profil - WMA Hub</title>
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

        /* Video Background */
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
            filter: brightness(0.3) contrast(1.1);
        }

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
        .role-card-container {
            position: relative;
            z-index: 10;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 2rem;
            border-radius: 2rem;
            box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.8);
            max-width: 800px;
            width: 100%;
            text-align: center;
            animation: slideUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        @media (max-width: 640px) {
            .role-card-container {
                padding: 1.5rem;
                border-radius: 1.5rem;
            }
            .role-card-container h1 {
                font-size: 2rem;
            }
            .role-option {
                padding: 1.5rem;
            }
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .role-card-container h1 { color: #fff; text-shadow: 0 2px 10px rgba(0,0,0,0.3); }
        .role-card-container p { color: rgba(255, 255, 255, 0.6); }

        .role-option {
            background: rgba(255, 255, 255, 0.03);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.5rem;
            padding: 2rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            text-align: center;
        }

        .role-option:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 102, 0, 0.5);
            transform: translateY(-8px);
            box-shadow: 0 20px 40px -10px rgba(255, 102, 0, 0.2);
        }

        .peer:checked + .role-option {
            background: rgba(255, 102, 0, 0.15);
            border-color: #ff6600;
            box-shadow: 0 0 30px rgba(255, 102, 0, 0.3);
        }

        .role-icon {
            width: 64px;
            height: 64px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 1.75rem;
            transition: all 0.4s ease;
            color: #fff;
        }

        .peer:checked + .role-option .role-icon {
            background: #ff6600;
            color: #fff;
            transform: scale(1.1) rotate(5deg);
        }

        .role-title {
            color: #fff;
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }

        .role-desc {
            font-size: 0.9rem;
            line-height: 1.5;
            color: rgba(255, 255, 255, 0.5);
        }

        .submit-btn {
            background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%);
            color: #fff;
            padding: 1.25rem;
            border-radius: 1.25rem;
            font-weight: 700;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px -5px rgba(255, 102, 0, 0.4);
            margin-top: 2rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
        }

        .submit-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 15px 30px -5px rgba(255, 102, 0, 0.6);
            filter: brightness(1.1);
        }

        .submit-btn:active {
            transform: scale(0.98);
        }
    </style>
</head>
<body>
    <div class="video-background">
        <iframe 
            src="https://www.youtube.com/embed/R2a9kSeTnBs?autoplay=1&mute=1&loop=1&playlist=R2a9kSeTnBs&controls=0&showinfo=0&rel=0&iv_load_policy=3" 
            frameborder="0" 
            allow="autoplay; encrypted-media">
        </iframe>
    </div>
    <div class="overlay"></div>

    <div class="role-card-container">
        <header class="mb-12">
            <h1 class="text-4xl font-bold mb-4">Presque fini !</h1>
            <p class="text-lg">Dites-nous quel est votre rôle chez WMA Hub.</p>
        </header>

        <?php if ($error): ?>
            <div class="bg-red-500/20 border border-red-500/50 text-red-100 px-6 py-4 rounded-xl mb-8 text-left">
                <i class="fas fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <label class="cursor-pointer group">
                    <input type="radio" name="role" value="artiste" class="sr-only peer" required>
                    <div class="role-option">
                        <div class="role-icon">
                            <i class="fas fa-microphone-alt"></i>
                        </div>
                        <h3 class="role-title">Artiste</h3>
                        <p class="role-desc">Distribuez votre musique et gérez vos revenus.</p>
                    </div>
                </label>

                <label class="cursor-pointer group">
                    <input type="radio" name="role" value="employe" class="sr-only peer">
                    <div class="role-option">
                        <div class="role-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <h3 class="role-title">Équipe WMA</h3>
                        <p class="role-desc">Accédez aux outils de gestion et d'administration.</p>
                    </div>
                </label>
            </div>

            <button type="submit" class="submit-btn group">
                Confirmer mon profil <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
            </button>
        </form>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>
</body>
</html>
