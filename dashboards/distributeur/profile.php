<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint aux distributeurs
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'distributeur') {
    header('Location: ../../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../includes/subscription_check.php';
// Vérifier l'abonnement
if (!hasActiveSubscription($_SESSION['user_id'])) {
    header('Location: ../../auth/subscription.php');
    exit;
}

$db = getDBConnection();
$userId = $_SESSION['user_id'];
$success = '';
$error = '';

// Récupérer les infos actuelles
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $dist_name = trim($_POST['distributor_name'] ?? '');
    $dist_city = trim($_POST['distributor_city'] ?? '');
    $dist_logo = $user['distributor_logo'];

    if (empty($name) || empty($dist_name) || empty($dist_city)) {
        $error = "Tous les champs sont obligatoires.";
    } else {
        // Gérer l'upload du logo si nouveau
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $fileExtension = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            $newFileName = 'logo_' . $userId . '_' . time() . '.' . $fileExtension;
            $targetFile = $uploadDir . $newFileName;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetFile)) {
                $dist_logo = $newFileName;
            }
        }

        $stmt = $db->prepare("UPDATE users SET name = ?, distributor_name = ?, distributor_city = ?, distributor_logo = ? WHERE id = ?");
        if ($stmt->execute([$name, $dist_name, $dist_city, $dist_logo, $userId])) {
            $_SESSION['user_name'] = $name;
            $success = "Profil mis à jour avec succès.";
            // Re-fetch user data
            $stmt->execute();
            $user = $db->query("SELECT * FROM users WHERE id = $userId")->fetch();
        } else {
            $error = "Erreur lors de la mise à jour.";
        }
    }
}

$pageTitle = 'Mon Profil - WMA Hub';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/jpeg" href="../../asset/placeholder.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; min-height: 100vh; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 100% 0%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 1.5rem; padding: 2.5rem; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; padding: 2rem 1.5rem; display: flex; flex-direction: column; }
        .main-content { margin-left: 280px; padding: 2rem; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; transform: translateX(5px); }
        .form-input { width: 100%; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 1rem; padding: 1.25rem; color: #fff; outline: none; transition: all 0.3s ease; }
        .form-input:focus { border-color: #ff6600; box-shadow: 0 0 15px rgba(255, 102, 0, 0.1); }
        .btn-orange { background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%); color: #fff; padding: 1.25rem; border-radius: 1.25rem; font-weight: 700; transition: all 0.3s ease; width: 100%; cursor: pointer; text-transform: uppercase; letter-spacing: 1px; }
        .btn-orange:hover { transform: translateY(-2px); box-shadow: 0 10px 20px -5px rgba(255, 102, 0, 0.4); filter: brightness(1.1); }
        .cert-btn { background: rgba(0, 184, 212, 0.1); color: #00e5ff; border: 1px solid rgba(0, 229, 255, 0.2); padding: 0.5rem 1rem; border-radius: 0.75rem; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; transition: all 0.3s ease; }
        .cert-btn:hover { background: #00e5ff; color: #000; box-shadow: 0 0 20px rgba(0, 229, 255, 0.4); }
    </style>
</head>
<body>
    <div class="bg-glow"></div>

    <aside class="sidebar">
        <div class="flex items-center gap-4 mb-12 px-2">
            <img src="../../asset/trans.png" alt="Logo" class="h-10">
            <div>
                <h1 class="text-xl font-black bg-gradient-to-r from-orange-500 to-orange-300 bg-clip-text text-transparent tracking-tighter leading-tight">WMA HUB</h1>
                <p class="text-[8px] text-gray-500 font-bold uppercase tracking-[1px] -mt-1 flex items-center gap-1">
                    Distributeur
                    <?php if ($user['is_certified']): ?>
                        <i class="fas fa-check-decagram text-cyan-400 text-[10px]"></i>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <nav class="flex-1">
            <a href="index.php" class="nav-link"><i class="fas fa-th-large"></i> Vue d'ensemble</a>
            <a href="artists.php" class="nav-link"><i class="fas fa-users"></i> Mes Artistes</a>
            <a href="catalogue.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'catalogue.php' ? 'active' : '' ?>"><i class="fas fa-compact-disc"></i> Mon Catalogue</a>
            <a href="distributed_projects.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'distributed_projects.php' ? 'active' : '' ?>"><i class="fas fa-check-circle"></i> Projets Distribués</a>
            <a href="submit.php" class="nav-link"><i class="fas fa-upload"></i> Distribuer</a>
            <a href="service_card.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'service_card.php' ? 'active' : '' ?>"><i class="fas fa-id-card"></i> Ma Carte Service</a>
            <a href="royalties.php" class="nav-link"><i class="fas fa-wallet"></i> Royalties</a>
            <a href="profile.php" class="nav-link active"><i class="fas fa-user-circle"></i> Mon Profil</a>
        </nav>
        <div class="mt-auto pt-6 border-t border-white/5">
            <a href="../../auth/logout.php" class="nav-link !text-red-500"><i class="fas fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="flex justify-between items-center mb-12">
            <div>
                <div class="flex items-center gap-4 mb-2">
                    <h2 class="text-4xl font-black tracking-tighter leading-none flex items-center gap-3">
                        Mon <span class="text-orange-500">Profil</span>
                        <?php if ($user['is_certified']): ?>
                            <i class="fas fa-check-decagram text-cyan-400" title="Compte Certifié"></i>
                        <?php endif; ?>
                    </h2>
                    <?php if (!$user['is_certified']): ?>
                        <a href="certify.php" class="cert-btn"><i class="fas fa-check-decagram mr-2"></i> Certifier mon compte</a>
                    <?php endif; ?>
                </div>
                <p class="text-gray-400">Gérez les informations de votre organisation et votre identité.</p>
            </div>
        </header>

        <?php if ($success): ?>
            <div class="glass-card !py-4 border-green-500/50 bg-green-500/10 mb-8 flex items-center gap-4 text-green-500">
                <i class="fas fa-check-circle text-xl"></i>
                <p class="text-sm font-bold"><?= $success ?></p>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="glass-card !py-4 border-red-500/50 bg-red-500/10 mb-8 flex items-center gap-4 text-red-500">
                <i class="fas fa-exclamation-circle text-xl"></i>
                <p class="text-sm font-bold"><?= $error ?></p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <form method="POST" enctype="multipart/form-data" class="glass-card space-y-8">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-gray-500 uppercase tracking-widest pl-2">Nom Complet (Propriétaire)</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required class="form-input">
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-gray-500 uppercase tracking-widest pl-2">Nom du Distributeur / Organisation</label>
                            <input type="text" name="distributor_name" value="<?= htmlspecialchars($user['distributor_name']) ?>" required class="form-input">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-widest pl-2">Ville / Localisation</label>
                        <input type="text" name="distributor_city" value="<?= htmlspecialchars($user['distributor_city']) ?>" required class="form-input">
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-widest pl-2">Modifier le Logo</label>
                        <div class="relative group">
                            <input type="file" name="logo" id="logoInput" class="hidden" accept="image/*">
                            <label for="logoInput" class="flex flex-col items-center justify-center border-2 border-dashed border-white/10 rounded-2xl p-10 cursor-pointer hover:border-orange-500/50 hover:bg-white/2 transition-all group">
                                <i class="fas fa-cloud-upload-alt text-3xl text-gray-500 group-hover:text-orange-500 mb-4"></i>
                                <span class="text-sm text-gray-400" id="fileName">Changer le logo de l'organisation</span>
                            </label>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="btn-orange">Enregistrer les modifications</button>
                    </div>
                </form>
            </div>

            <div>
                <div class="glass-card text-center">
                    <div class="w-32 h-32 rounded-3xl bg-gray-900 mx-auto mb-6 p-1 border-2 border-orange-500/20 overflow-hidden">
                        <?php if ($user['distributor_logo']): ?>
                            <img src="uploads/<?= $user['distributor_logo'] ?>" class="w-full h-full object-cover rounded-2xl">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-3xl text-gray-700"><i class="fas fa-university"></i></div>
                        <?php endif; ?>
                    </div>
                    <h3 class="text-2xl font-black flex items-center justify-center gap-2">
                        <?= htmlspecialchars($user['distributor_name']) ?>
                        <?php if ($user['is_certified']): ?>
                            <i class="fas fa-check-decagram text-cyan-400 text-lg"></i>
                        <?php endif; ?>
                    </h3>
                    <p class="text-sm text-gray-500 mb-6"><?= htmlspecialchars($user['distributor_city']) ?></p>
                    
                    <div class="flex flex-col gap-3">
                        <div class="bg-white/5 p-4 rounded-2xl border border-white/10 text-left">
                            <p class="text-[10px] text-gray-600 font-bold uppercase mb-1">Email Connecté</p>
                            <p class="text-sm font-medium"><?= htmlspecialchars($user['email']) ?></p>
                        </div>
                        <div class="bg-white/5 p-4 rounded-2xl border border-white/10 text-left">
                            <p class="text-[10px] text-gray-600 font-bold uppercase mb-1">Rôle Système</p>
                            <p class="text-sm font-medium uppercase tracking-widest text-orange-500">Distributeur Indépendant</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.getElementById('logoInput').onchange = function() {
            if (this.files && this.files[0]) {
                document.getElementById('fileName').innerHTML = '<i class="fas fa-check text-green-500 mr-2"></i> ' + this.files[0].name;
            }
        };
    </script>
</body>
</html>
