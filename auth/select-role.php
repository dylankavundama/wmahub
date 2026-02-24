<?php
require_once __DIR__ . '/../includes/config.php';

// Vérifier si l'utilisateur est authentifié mais sans rôle
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Correction : On redirige seulement si le rôle est défini et non vide
if (!empty($_SESSION['role'])) {
    if ($_SESSION['role'] === 'artiste') {
        header('Location: ../dashboards/artiste/index.php');
    } elseif ($_SESSION['role'] === 'employe') {
        header('Location: pending.php');
    } elseif ($_SESSION['role'] === 'distributeur') {
        header('Location: ../dashboards/distributeur/index.php');
    } else {
        header('Location: ../index.php');
    }
    exit;
}

// Initialisation du token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$step = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification du token CSRF
    $token = $_POST['csrf_token'] ?? '';
    if (empty($token) || $token !== $_SESSION['csrf_token']) {
        die('Erreur de sécurité : Token CSRF invalide.');
    }
    
    if (isset($_POST['role_selection'])) {
        $role = $_POST['role'] ?? '';
        if (in_array($role, ['artiste', 'employe', 'distributeur'])) {
            if ($role === 'distributeur') {
                $step = 2; // On passe à l'étape suivante pour le distributeur
            } else {
                // Pour artiste et employe, on garde la logique actuelle
                $db = getDBConnection();
                // Activation automatique pour les artistes, attente pour les employés
                $isActive = ($role === 'artiste') ? 1 : 0;
                $stmt = $db->prepare("UPDATE users SET role = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$role, $isActive, $_SESSION['user_id']]);
                $_SESSION['role'] = $role;
                
                // Notifier l'équipe admin
                require_once __DIR__ . '/../includes/mailer.php';
                $roleLabel = $role === 'artiste' ? 'Artiste' : 'Employé';
                notifyAdmin('registration', 'Nouveau ' . $roleLabel . ' Inscrit', [
                    'Nom' => $_SESSION['user_name'],
                    'Email' => $_SESSION['user_email'],
                    'Rôle' => $roleLabel,
                    'Date' => date('d/m/Y H:i')
                ], 'https://wmahub.com/dashboards/admin/users.php');
                
                // Envoyer l'email de bienvenue
                sendWelcomeEmail($_SESSION['user_email'], $_SESSION['user_name'], $role);
                
                if ($role === 'artiste') {
                    header('Location: ../dashboards/artiste/index.php');
                } else {
                    header('Location: pending.php');
                }
                exit;
            }
        } else {
            $error = 'Veuillez sélectionner un rôle valide.';
        }
    } elseif (isset($_POST['complete_distributor'])) {
        $role = 'distributeur';
        $dist_name = trim($_POST['distributor_name'] ?? '');
        $dist_city = trim($_POST['distributor_city'] ?? '');
        $dist_logo = '';

        if (empty($dist_name) || empty($dist_city)) {
            $error = "Le nom de l'organisation et la ville sont obligatoires.";
            $step = 2;
        } else {
            // Gérer l'upload du logo
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../dashboards/distributeur/uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $fileExtension = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                $newFileName = 'logo_' . $_SESSION['user_id'] . '_' . time() . '.' . $fileExtension;
                $targetFile = $uploadDir . $newFileName;
                
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetFile)) {
                    $dist_logo = $newFileName;
                }
            }

            $db = getDBConnection();
            // Activation automatique pour les distributeurs
            $stmt = $db->prepare("UPDATE users SET role = ?, distributor_name = ?, distributor_city = ?, distributor_logo = ?, is_active = 1 WHERE id = ?");
            $stmt->execute([$role, $dist_name, $dist_city, $dist_logo, $_SESSION['user_id']]);
            
            // Notifier l'équipe admin
            require_once __DIR__ . '/../includes/mailer.php';
            notifyAdmin('registration', 'Nouveau Distributeur Inscrit', [
                'Organisation' => $dist_name,
                'Nom' => $_SESSION['user_name'],
                'Email' => $_SESSION['user_email'],
                'Ville' => $dist_city,
                'Date' => date('d/m/Y H:i')
            ], 'https://wmahub.com/dashboards/admin/distributors.php');
            
            // Envoyer l'email de bienvenue
            sendWelcomeEmail($_SESSION['user_email'], $_SESSION['user_name'], $role);
            
            $_SESSION['role'] = $role;
            header('Location: ../dashboards/distributeur/index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choisissez votre profil - WMA Hub</title>
    <link rel="icon" type="image/jpeg" href="../asset/placeholder.jpg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #000;
            overflow-x: hidden;
            overflow-y: auto;
            position: relative;
        }

        .main-container {
            width: 100%;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1rem;
            z-index: 10;
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
            width: 100vw;
            height: 56.25vw; /* 16:9 base */
            min-height: 100vh;
            min-width: 177.77vw; /* 100 * 16 / 9 for portrait cover */
            transform: translate(-50%, -50%);
            filter: brightness(0.3) contrast(1.1);
            pointer-events: none;
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
            allow="autoplay; encrypted-media">
        </iframe>
    </div>
    <div class="overlay"></div>

    <div class="main-container">
        <div class="role-card-container">
        <header class="mb-12">
            <h1 class="text-4xl font-bold mb-4"><?= $step === 1 ? 'Presque fini !' : 'Profil Distributeur' ?></h1>
            <p class="text-lg"><?= $step === 1 ? 'Dites-nous quel est votre rôle chez WMA Hub.' : 'Complétez vos informations professionnelles.' ?></p>
        </header>

        <?php if ($error): ?>
            <div class="bg-red-500/20 border border-red-500/50 text-red-100 px-6 py-4 rounded-xl mb-8 text-left">
                <i class="fas fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="role_selection" value="1">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
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
                    <input type="radio" name="role" value="distributeur" class="sr-only peer">
                    <div class="role-option">
                        <div class="role-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h3 class="role-title">Distributeur</h3>
                        <p class="role-desc">Travaillez en indépendant et gérez vos artistes.</p>
                    </div>
                </label>

                <label class="cursor-pointer group">
                    <input type="radio" name="role" value="employe" class="sr-only peer">
                    <div class="role-option">
                        <div class="role-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <h3 class="role-title">Équipe WMA</h3>
                        <p class="role-desc">Accédez aux outils de gestion internes.</p>
                    </div>
                </label>
            </div>

            <button type="submit" class="submit-btn group">
                Continuer <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
            </button>
            
            <p class="text-center mt-6 text-gray-500 text-[10px] uppercase tracking-widest leading-relaxed">
                En continuant, vous acceptez les <a href="../dashboards/artiste/contrat.php" target="_blank" class="text-orange-500 hover:text-orange-400 transition-colors">Conditions Générales de Distribution</a> de WMA HUB.
            </p>
        </form>
        <?php else: ?>
        <form method="POST" enctype="multipart/form-data" class="text-left space-y-6">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="complete_distributor" value="1">
            
            <div class="space-y-2">
                <label class="text-sm font-bold text-gray-400 uppercase tracking-widest pl-2">Nom de l'organisation / Distributeur</label>
                <input type="text" name="distributor_name" required placeholder="Ex: Independent Beats Records" 
                       class="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-white focus:border-orange-500 outline-none transition-all">
            </div>

            <div class="space-y-2">
                <label class="text-sm font-bold text-gray-400 uppercase tracking-widest pl-2">Ville</label>
                <input type="text" name="distributor_city" required placeholder="Ex: Kinshasa, Lubumbashi..." 
                       class="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-white focus:border-orange-500 outline-none transition-all">
            </div>

            <div class="space-y-2">
                <label class="text-sm font-bold text-gray-400 uppercase tracking-widest pl-2">Logo ou Photo de profil</label>
                <div class="relative group">
                    <input type="file" name="logo" accept="image/*" class="hidden" id="logoUpload">
                    <label for="logoUpload" class="flex items-center justify-center gap-4 bg-white/5 border-2 border-dashed border-white/10 rounded-2xl p-8 cursor-pointer group-hover:border-orange-500/50 transition-all">
                        <i class="fas fa-cloud-upload-alt text-3xl text-gray-500 group-hover:text-orange-500"></i>
                        <span class="text-gray-400" id="fileName">Cliquez pour ajouter un logo</span>
                    </label>
                </div>
            </div>

            <button type="submit" class="submit-btn group mt-8">
                Créer mon compte distributeur <i class="fas fa-check ml-2"></i>
            </button>
            <p class="text-center mt-4">
                <a href="select-role.php" class="text-sm text-gray-500 hover:text-white transition-colors">Retour</a>
            </p>
        </form>
        <script>
            document.getElementById('logoUpload').onchange = function() {
                document.getElementById('fileName').innerHTML = this.files[0].name;
            };
        </script>
        <?php endif; ?>
    </div>
    </div>
</body>
</html>
