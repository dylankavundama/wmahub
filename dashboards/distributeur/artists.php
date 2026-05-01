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

// Récupérer les infos du distributeur
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$success = '';
$error = '';

// Ajout d'un artiste
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_artist'])) {
    $name = trim($_POST['artist_name'] ?? '');
    if ($name) {
        $stmt = $db->prepare("INSERT INTO distributor_artists (distributor_id, name) VALUES (?, ?)");
        if ($stmt->execute([$userId, $name])) {
            $success = "Artiste ajouté avec succès.";
        } else {
            $error = "Erreur lors de l'ajout.";
        }
    } else {
        $error = "Le nom de l'artiste est requis.";
    }
}

// Suppression d'un artiste
if (isset($_GET['delete'])) {
    $artistId = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM distributor_artists WHERE id = ? AND distributor_id = ?");
    if ($stmt->execute([$artistId, $userId])) {
        $success = "Artiste supprimé.";
    }
}

// Récupérer les artistes avec leur nombre de projets
$query = "SELECT a.*, (SELECT COUNT(*) FROM projects p WHERE p.artist_name = a.name AND p.user_id = a.distributor_id) as project_count 
          FROM distributor_artists a 
          WHERE a.distributor_id = ? 
          ORDER BY a.name ASC";
$stmt = $db->prepare($query);
$stmt->execute([$userId]);
$artists = $stmt->fetchAll();

$pageTitle = 'Mes Artistes - WMA Hub';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="/asset/icon.png"><link rel="apple-touch-icon" href="/asset/icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; min-height: 100vh; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 1.5rem; padding: 2rem; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; padding: 2rem 1.5rem; display: flex; flex-direction: column; }
        .main-content { margin-left: 280px; padding: 2rem; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; transform: translateX(5px); }
        .form-input { width: 100%; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 1rem; padding: 1rem; color: #fff; outline: none; }
        .btn-orange { background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%); color: #fff; padding: 1rem 2rem; border-radius: 1rem; font-weight: 700; transition: all 0.3s ease; }
        .btn-orange:hover { transform: scale(1.02); filter: brightness(1.1); }
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
            <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>"><i class="fas fa-th-large"></i> Vue d'ensemble</a>
            <a href="artists.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'artists.php' ? 'active' : '' ?>"><i class="fas fa-users"></i> Mes Artistes</a>
            <a href="catalogue.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'catalogue.php' ? 'active' : '' ?>"><i class="fas fa-compact-disc"></i> Mon Catalogue</a>
            <a href="distributed_projects.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'distributed_projects.php' ? 'active' : '' ?>"><i class="fas fa-check-circle"></i> Projets Distribués</a>
            <a href="submit.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'submit.php' ? 'active' : '' ?>"><i class="fas fa-upload"></i> Distribuer</a>
            <a href="service_card.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'service_card.php' ? 'active' : '' ?>"><i class="fas fa-id-card"></i> Ma Carte Service</a>
            <a href="royalties.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'royalties.php' ? 'active' : '' ?>"><i class="fas fa-wallet"></i> Royalties</a>
            <a href="profile.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>"><i class="fas fa-user-circle"></i> Mon Profil</a>
        </nav>
        <div class="mt-auto pt-6 border-t border-white/5">
            <a href="../../auth/logout.php" class="nav-link !text-red-500"><i class="fas fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="flex justify-between items-center mb-12">
            <div>
                <h2 class="text-4xl font-black tracking-tighter">Mes <span class="text-orange-500">Artistes</span></h2>
                <p class="text-gray-400 mt-2">Gérez et suivez le catalogue de vos talents.</p>
            </div>
            <button onclick="document.getElementById('addForm').scrollIntoView({behavior: 'smooth'})" class="btn-orange text-sm">
                <i class="fas fa-plus mr-2"></i> Ajouter un Artiste
            </button>
        </header>

        <?php if ($success): ?>
            <div class="glass-card border-green-500/50 bg-green-500/10 mb-8 p-4 rounded-xl flex items-center gap-4 text-green-500">
                <i class="fas fa-check-circle"></i> <?= $success ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
            <?php foreach ($artists as $artist): ?>
                <div class="glass-card hover:border-orange-500/30 transition-all group relative">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl bg-orange-500/10 flex items-center justify-center text-orange-500 text-2xl font-black border border-orange-500/20">
                            <?= strtoupper(substr($artist['name'], 0, 1)) ?>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold"><?= htmlspecialchars($artist['name']) ?></h3>
                            <p class="text-xs text-gray-500"><?= $artist['project_count'] ?> projet(s) distribué(s)</p>
                        </div>
                    </div>
                    <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
                        <a href="artists.php?delete=<?= $artist['id'] ?>" onclick="return confirm('Supprimer cet artiste ?')" class="text-gray-600 hover:text-red-500 transition-colors">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($artists)): ?>
                <div class="col-span-full glass-card py-24 text-center">
                    <div class="w-20 h-20 rounded-full bg-white/5 mx-auto flex items-center justify-center mb-6 text-gray-600">
                        <i class="fas fa-users text-4xl"></i>
                    </div>
                    <p class="text-gray-500 italic">Vous n'avez pas encore d'artistes dans votre roster.</p>
                </div>
            <?php endif; ?>
        </div>

        <section id="addForm" class="glass-card max-w-xl">
            <h3 class="text-xl font-bold mb-6">Ajouter un nouvel artiste</h3>
            <form method="POST" class="space-y-6">
                <div class="space-y-2">
                    <label class="text-xs font-bold text-gray-500 uppercase tracking-widest pl-2">Nom de l'artiste</label>
                    <input type="text" name="artist_name" required class="form-input" placeholder="Ex: King Dully">
                </div>
                <button type="submit" name="add_artist" class="btn-orange w-full">
                    Confirmer l'ajout
                </button>
            </form>
        </section>
    </main>
</body>
</html>
