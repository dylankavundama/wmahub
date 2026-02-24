<?php
require_once __DIR__ . '/auth_artist.php';

$pageTitle = 'Dashboard Artiste - WMA Hub';

$db = getDBConnection();

// Récupérer les projets de l'utilisateur
$stmt = $db->prepare("SELECT * FROM projects WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id'] ?? 0]);
$projects = $stmt->fetchAll();

function getStatusLabel($status) {
    switch ($status) {
        case 'en_attente': return 'En attente';
        case 'en_preparation': return 'En préparation';
        case 'distribue': return 'Distribué';
        default: return str_replace('_', ' ', ucfirst($status));
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="icon" type="image/jpeg" href="../../asset/placeholder.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8882238368661853"
     crossorigin="anonymous"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; margin: 0; min-height: 100vh; overflow-x: hidden; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .glow-spot { position: fixed; width: 600px; height: 600px; background: radial-gradient(circle, rgba(255,102,0,0.05) 0%, transparent 70%); border-radius: 50%; pointer-events: none; z-index: -1; transition: all 0.3s ease; }
        .sidebar { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .sidebar.active { transform: translateX(0); }
        @media (max-width: 1024px) { .sidebar { transform: translateX(-100%); position: fixed; z-index: 50; height: 100vh; } }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px); border: 1px border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 20px; transition: all 0.3s ease; }
        .glass-card:hover { background: rgba(255, 255, 255, 0.05); border-color: rgba(255, 102, 0, 0.2); transform: translateY(-5px); }
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: #94a3b8; transition: all 0.3s ease; margin-bottom: 4px; }
        .nav-link:hover:not(.active) { background: rgba(255, 255, 255, 0.03); color: #fff; }
        .nav-link.active { background: linear-gradient(135deg, #ff6600 0%, #ff8c00 100%); color: #fff; box-shadow: 0 4px 15px rgba(255, 102, 0, 0.3); }
        .nav-link i { font-size: 1.1rem; }
    </style>
</head>
<body>
    <div class="bg-glow"></div>
    <div class="glow-spot" id="glow"></div>

    <!-- Mobile Header -->
    <div class="lg:hidden flex items-center justify-between p-4 bg-[#0a0a0c] border-b border-white/5 sticky top-0 z-40">
        <div class="flex items-center gap-3">
            <img src="../../asset/trans.png" alt="Logo" class="w-8 h-8 object-contain">
            <span class="text-lg font-bold tracking-tighter">WMA ARTISTE</span>
        </div>
        <button id="sidebarToggle" class="text-white text-2xl p-2"><i class="fas fa-bars"></i></button>
    </div>

    <!-- Sidebar Overlay -->
    <div id="overlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 hidden lg:hidden"></div>

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar w-72 bg-[#0d0d0f] border-r border-white/5 flex flex-col p-6 overflow-y-auto">
            <div class="flex items-center gap-4 mb-10 px-2">
                <img src="../../asset/trans.png" alt="Logo" class="w-10 h-10 object-contain">
                <div>
                    <h1 class="text-xl font-bold tracking-tighter bg-gradient-to-r from-white to-white/60 bg-clip-text text-transparent">WMA HUB</h1>
                    <p class="text-[10px] text-orange-500 font-bold uppercase tracking-widest">We move, WMAFam</p>
                </div>
            </div>

            <nav class="flex-1">
                <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
                    <i class="fas fa-th-large"></i>
                    <span class="font-medium">Tableau de bord</span>
                </a>
                <a href="submit.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'submit.php' ? 'active' : '' ?>">
                    <i class="fas fa-plus-circle"></i>
                    <span class="font-medium">Soumettre</span>
                </a>
                <a href="services.php" class="nav-link <?= strpos(basename($_SERVER['PHP_SELF']), 'services') !== false ? 'active' : '' ?>">
                    <i class="fas fa-magic"></i>
                    <span class="font-medium">Services</span>
                </a>
                <a href="notifications.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'notifications.php' ? 'active' : '' ?>">
                    <i class="fas fa-bell"></i>
                    <span class="font-medium">Notifications</span>
                </a>
                <a href="catalogue.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'catalogue.php' ? 'active' : '' ?>">
                    <i class="fas fa-music"></i>
                    <span class="font-medium">Catalogue</span>
                </a>
                <a href="stats.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'stats.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-line"></i>
                    <span class="font-medium">Stats</span>
                </a>
                <a href="reviews.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'reviews.php' ? 'active' : '' ?>">
                    <i class="fas fa-star"></i>
                    <span class="font-medium">Laisser un avis</span>
                </a>
                <a href="revenues.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'revenues.php' ? 'active' : '' ?>">
                    <i class="fas fa-wallet"></i>
                    <span class="font-medium">Revenus</span>
                </a>
                <a href="contrat.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'contrat.php' ? 'active' : '' ?>">
                    <i class="fas fa-file-contract"></i>
                    <span class="font-medium">Contrat</span>
                </a>
            </nav>

            <div class="mt-auto pt-6 border-t border-white/5">
                <div class="flex items-center gap-3 p-3 rounded-2xl bg-white/[0.03] border border-white/5 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-orange-500 flex items-center justify-center text-white shadow-lg shadow-orange-500/20">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="flex-1 overflow-hidden">
                        <p class="text-sm font-bold truncate"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Artiste') ?></p>
                        <p class="text-[10px] text-gray-500 uppercase font-black">Artiste</p>
                    </div>
                </div>
                <a href="../../auth/logout.php" class="nav-link text-red-500 hover:bg-red-500/10 mb-0">
                    <i class="fas fa-power-off"></i>
                    <span class="font-medium">Déconnexion</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-6 lg:p-12">
            <header class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12">
                <div>
                    <h2 class="text-3xl lg:text-4xl font-black tracking-tighter mb-2">Bonjour, <span class="text-orange-500"><?= explode(' ', $_SESSION['user_name'] ?? 'Artiste')[0] ?></span>.</h2>
                    <p class="text-gray-500 font-medium">Bienvenue dans votre centre créatif.</p>
                </div>
                <div class="flex items-center gap-4 mt-4 md:mt-0">
                    <a href="submit.php" class="px-6 py-3 bg-white text-black rounded-full font-bold hover:bg-orange-500 hover:text-white transition-all duration-300 flex items-center gap-2 shadow-xl shadow-white/5">
                        <i class="fas fa-rocket"></i>
                        Nouvelle Sortie
                    </a>
                    <?php include '../../includes/header_notifications.php'; ?>
                </div>
            </header>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                <div class="glass-card p-6">
                    <p class="text-xs text-gray-500 font-bold uppercase mb-1">Projets</p>
                    <p class="text-3xl font-black"><?= count($projects) ?></p>
                </div>
                <div class="glass-card p-6">
                    <p class="text-xs text-gray-500 font-bold uppercase mb-1">Vues Totales</p>
                    <p class="text-3xl font-black text-orange-500"><?= number_format(array_sum(array_column($projects, 'streams')), 0, '.', ' ') ?></p>
                </div>
                <div class="glass-card p-6">
                    <p class="text-xs text-gray-500 font-bold uppercase mb-1">Distribués</p>
                    <p class="text-3xl font-black text-green-500"><?= count(array_filter($projects, fn($p) => $p['status'] === 'distribue')) ?></p>
                </div>
                <div class="glass-card p-6">
                    <p class="text-xs text-gray-500 font-bold uppercase mb-1">Revenus</p>
                    <p class="text-3xl font-black text-blue-500"><?= number_format(array_sum(array_column($projects, 'revenue')), 2, '.', ' ') ?> $</p>
                </div>
            </div>

            <div class="glass-card p-0 overflow-hidden shadow-2xl">
                <div class="px-8 py-6 border-b border-white/5 flex items-center justify-between">
                    <h3 class="text-lg font-bold">Sorties récentes</h3>
                    <a href="#" class="text-xs font-bold text-orange-500">Voir tout</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] uppercase font-black text-gray-500 border-b border-white/5">
                                <th class="px-8 py-4">Titre</th>
                                <th class="px-8 py-4">Statut</th>
                                <th class="px-8 py-4">Vues Score</th>
                                <th class="px-8 py-4">Sortie</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php if (empty($projects)): ?>
                                <tr>
                                    <td colspan="4" class="px-8 py-20 text-center text-gray-500">Aucun projet trouvé.</td>
                                </tr>
                            <?php else: foreach ($projects as $project): ?>
                                <tr class="hover:bg-white/[0.02] group">
                                    <td class="px-8 py-5">
                                        <p class="font-bold group-hover:text-orange-500 transition-colors"><?= htmlspecialchars($project['title']) ?></p>
                                        <p class="text-xs text-gray-500"><?= htmlspecialchars($project['artist_name']) ?></p>
                                    </td>
                                    <td class="px-8 py-5">
                                        <?php 
                                        $s = $project['status'];
                                        $badgeClass = "bg-orange-500/10 text-orange-500";
                                        if ($s == 'distribue') $badgeClass = "bg-green-500/10 text-green-500";
                                        if ($s == 'en_preparation') $badgeClass = "bg-blue-500/10 text-blue-500";
                                        ?>
                                        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider <?= $badgeClass ?>">
                                            <?= getStatusLabel($s) ?>
                                        </span>
                                    </td>
                                    <td class="px-8 py-5">
                                        <div class="flex items-center gap-2">
                                            <i class="fas fa-chart-bar text-orange-500/50"></i>
                                            <span class="font-bold"><?= number_format($project['streams'], 0, '.', ' ') ?></span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5 text-sm text-gray-400"><?= date('d/m/Y', strtotime($project['date_sortie'])) ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const toggle = document.getElementById('sidebarToggle');

        function toggleSidebar() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('hidden');
        }

        if (toggle) toggle.addEventListener('click', toggleSidebar);
        if (overlay) overlay.addEventListener('click', toggleSidebar);

        const glow = document.getElementById('glow');
        document.onmousemove = (e) => {
            if (glow) {
                glow.style.left = (e.clientX - glow.offsetWidth / 2) + 'px';
                glow.style.top = (e.clientY - glow.offsetHeight / 2) + 'px';
            }
        };
    </script>
</body>
</html>
