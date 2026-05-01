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

// Récupérer uniquement les projets distribués du distributeur
$query = "SELECT * FROM projects WHERE user_id = ? AND status = 'distribue' ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute([$userId]);
$projects = $stmt->fetchAll();

$pageTitle = 'Projets Distribués - WMA Hub';
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
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 100% 0%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 1.5rem; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; padding: 2rem 1.5rem; display: flex; flex-direction: column; }
        .main-content { margin-left: 280px; padding: 2rem; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; transform: translateX(5px); }
        .project-row { background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid rgba(255, 255, 255, 0.05); transition: all 0.3s ease; }
        .project-row:hover { background: rgba(255, 255, 255, 0.04); }
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
            <a href="catalogue.php" class="nav-link"><i class="fas fa-compact-disc"></i> Mon Catalogue</a>
            <a href="distributed_projects.php" class="nav-link active"><i class="fas fa-check-circle"></i> Projets Distribués</a>
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
        <header class="mb-12">
            <h2 class="text-4xl font-black tracking-tighter">Projets <span class="text-orange-500">Distribués</span></h2>
            <p class="text-gray-400 mt-2">Liste de vos œuvres déjà disponibles sur les plateformes.</p>
        </header>

        <div class="glass-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-white/5">
                        <tr>
                            <th class="p-6 text-[10px] text-gray-500 font-bold uppercase tracking-widest">Projet</th>
                            <th class="p-6 text-[10px] text-gray-500 font-bold uppercase tracking-widest">Artiste</th>
                            <th class="p-6 text-[10px] text-gray-500 font-bold uppercase tracking-widest">Date de sortie</th>
                            <th class="p-6 text-[10px] text-gray-500 font-bold uppercase tracking-widest text-center">Statut</th>
                            <th class="p-6 text-[10px] text-gray-500 font-bold uppercase tracking-widest">Streams</th>
                            <th class="p-6 text-[10px] text-gray-500 font-bold uppercase tracking-widest">Revenus</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php foreach ($projects as $p): ?>
                            <tr class="project-row">
                                <td class="p-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-lg overflow-hidden bg-gray-800 border border-white/10">
                                            <?php if ($p['cover_file']): ?>
                                                <img src="../artiste/uploads/<?= $p['cover_file'] ?>" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <div class="w-full h-full flex items-center justify-center text-gray-600"><i class="fas fa-music"></i></div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <p class="font-bold text-sm text-white"><?= htmlspecialchars($p['title']) ?></p>
                                            <p class="text-[10px] text-gray-500 uppercase font-black"><?= $p['type'] ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-6">
                                    <p class="text-sm font-medium text-gray-300"><?= htmlspecialchars($p['artist_name']) ?></p>
                                </td>
                                <td class="p-6">
                                    <p class="text-xs text-gray-500"><?= date('d/m/Y', strtotime($p['date_sortie'])) ?></p>
                                </td>
                                <td class="p-6 text-center">
                                    <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest text-green-500 bg-green-500/10">
                                        Distribué
                                    </span>
                                </td>
                                <td class="p-6">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-chart-line text-blue-500 text-xs text-opacity-50"></i>
                                        <p class="text-sm font-bold"><?= number_format($p['streams'], 0, '.', ' ') ?></p>
                                    </div>
                                </td>
                                <td class="p-6">
                                    <p class="text-sm font-black text-orange-500"><?= number_format($p['revenue'], 2, '.', ' ') ?> $</p>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($projects)): ?>
                            <tr>
                                <td colspan="6" class="p-24 text-center text-gray-500 italic">Aucun projet distribué pour le moment.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
