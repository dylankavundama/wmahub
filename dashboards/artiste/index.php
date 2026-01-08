<?php 
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint aux artistes
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'artiste') {
    header('Location: ../../auth/login.php');
    exit;
}

$pageTitle = 'Dashboard Artiste - WMA Hub';
include __DIR__ . '/../../includes/header.php';

$db = getDBConnection();

// Récupérer les projets de l'utilisateur
$stmt = $db->prepare("SELECT * FROM projects WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
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
    <link rel="icon" type="image/png" href="../../asset/icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; margin: 0; min-height: 100vh; overflow-x: hidden; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .glow-spot { position: fixed; width: 40vw; height: 40vw; background: radial-gradient(circle, rgba(255, 102, 0, 0.05) 0%, transparent 70%); border-radius: 50%; z-index: -1; filter: blur(80px); pointer-events: none; }
        .dashboard-wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; transition: all 0.3s ease; display: flex; flex-direction: column; padding: 2rem 1.5rem; }
        .main-content { flex: 1; margin-left: 280px; padding: 2rem; transition: all 0.3s ease; }
        @media (max-width: 1024px) { .sidebar { transform: translateX(-100%); } .sidebar.active { transform: translateX(0); } .main-content { margin-left: 0; padding: 1.5rem; } .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 90; backdrop-filter: blur(4px); } .sidebar-overlay.active { display: block; } .mobile-header { display: flex; } }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.5); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; transform: translateX(5px); }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 1.5rem; padding: 1.5rem; transition: all 0.4s ease; }
        .badge { padding: 0.4rem 0.8rem; border-radius: 99px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .badge-pending { background: rgba(255, 165, 0, 0.1); color: #ffa500; border: 1px solid rgba(255, 165, 0, 0.2); }
        .badge-success { background: rgba(34, 197, 94, 0.1); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.2); }
        .badge-info { background: rgba(59, 130, 246, 0.1); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.2); }
        .mobile-header { display: none; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; background: rgba(10, 10, 12, 0.8); backdrop-filter: blur(10px); position: sticky; top: 0; z-index: 80; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .btn-primary { background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%); box-shadow: 0 10px 20px -5px rgba(255, 102, 0, 0.4); transition: all 0.3s ease; text-decoration: none; display: inline-flex; items-center; gap: 0.5rem; color: #fff; font-weight: bold; padding: 1rem 2rem; border-radius: 1rem; }
    </style>
</head>
<body>
    <div class="bg-glow"></div>
    <div class="glow-spot" id="glow"></div>
    <div class="mobile-header">
        <div class="flex items-center gap-3"><img src="../../asset/trans.png" alt="Logo" class="h-8"><span class="font-bold">WMA HUB</span></div>
        <button id="sidebarToggle" class="text-white text-2xl"><i class="fas fa-bars"></i></button>
    </div>
    <div class="sidebar-overlay" id="overlay"></div>
    <div class="dashboard-wrapper">
        <aside class="sidebar" id="sidebar">
            <div class="flex items-center gap-4 mb-10 px-2"><img src="../../asset/trans.png" alt="Logo" class="h-10"><h1 class="text-xl font-bold bg-gradient-to-r from-orange-500 to-orange-300 bg-clip-text text-transparent">WMA HUB</h1></div>
            <nav class="flex-1">
                <a href="index.php" class="nav-link active"><i class="fas fa-th-large"></i>Tableau de bord</a>
                <a href="submit.php" class="nav-link"><i class="fas fa-plus-circle"></i>Soumettre</a>
                <a href="#" class="nav-link"><i class="fas fa-music"></i>Catalogue</a>
                <a href="#" class="nav-link"><i class="fas fa-chart-line"></i>Stats</a>
                <a href="#" class="nav-link"><i class="fas fa-wallet"></i>Revenus</a>
            </nav>
            <div class="mt-auto pt-6 border-t border-white/5">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-full bg-orange-500/20 flex items-center justify-center text-orange-500 border border-orange-500/20"><i class="fas fa-user"></i></div>
                    <div class="overflow-hidden"><p class="text-sm font-bold truncate"><?= htmlspecialchars($_SESSION['user_name']) ?></p></div>
                </div>
                <a href="../../auth/logout.php" class="nav-link !text-red-500 hover:!bg-red-500/10"><i class="fas fa-power-off"></i>Déconnexion</a>
            </div>
        </aside>
        <main class="main-content">
            <header class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12">
                <div><h2 class="text-4xl font-black mb-2">Bonjour, <span class="text-orange-500"><?= explode(' ', $_SESSION['user_name'])[0] ?></span>.</h2><p class="text-gray-400">Bienvenue dans votre centre créatif.</p></div>
                <a href="submit.php" class="btn-primary"><i class="fas fa-rocket"></i>Nouvelle Sortie</a>
            </header>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                <div class="glass-card"><p class="text-xs text-gray-500 font-bold uppercase mb-1">Projets</p><p class="text-3xl font-black"><?= count($projects) ?></p></div>
                <div class="glass-card"><p class="text-xs text-gray-500 font-bold uppercase mb-1">Vues Totales</p><p class="text-3xl font-black text-orange-500"><?= number_format(array_sum(array_column($projects, 'streams')), 0, '.', ' ') ?></p></div>
                <div class="glass-card"><p class="text-xs text-gray-500 font-bold uppercase mb-1">Distribués</p><p class="text-3xl font-black text-green-500"><?= count(array_filter($projects, fn($p) => $p['status'] === 'distribue')) ?></p></div>
                <div class="glass-card"><p class="text-xs text-gray-500 font-bold uppercase mb-1">Revenus</p><p class="text-3xl font-black">0.00 $</p></div>
            </div>
            <div class="glass-card p-0 overflow-hidden shadow-2xl">
                <div class="px-8 py-6 border-b border-white/5 flex items-center justify-between"><h3 class="text-lg font-bold">Sorties récentes</h3><a href="#" class="text-xs font-bold text-orange-500">Voir tout</a></div>
                <table class="w-full text-left">
                    <thead><tr class="text-[10px] uppercase font-black text-gray-500 border-b border-white/5"><th class="px-8 py-4">Titre</th><th class="px-8 py-4">Statut</th><th class="px-8 py-4">Vues Score</th><th class="px-8 py-4">Sortie</th></tr></thead>
                    <tbody class="divide-y divide-white/5">
                        <?php if (empty($projects)): ?><tr><td colspan="3" class="px-8 py-20 text-center text-gray-500">Aucun projet trouvé.</td></tr>
                        <?php else: foreach ($projects as $project): ?>
                            <tr class="hover:bg-white/[0.02] group">
                                <td class="px-8 py-5">
                                    <p class="font-bold group-hover:text-orange-500 transition-colors"><?= htmlspecialchars($project['title']) ?></p>
                                    <p class="text-xs text-gray-500"><?= htmlspecialchars($project['artist_name']) ?></p>
                                </td>
                                <td class="px-8 py-5">
                                    <?php $s = $project['status']; $b = "badge-" . ($s == 'distribue' ? 'success' : ($s == 'en_preparation' ? 'info' : 'pending')); ?>
                                    <span class="badge <?= $b ?>"><?= getStatusLabel($s) ?></span>
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
        </main>
    </div>
    <script>
        const s = document.getElementById('sidebar'), o = document.getElementById('overlay'), t = document.getElementById('sidebarToggle'), g = document.getElementById('glow');
        function ts() { s.classList.toggle('active'); o.classList.toggle('active'); }
        t.onclick = ts; o.onclick = ts;
        document.onmousemove = (e) => { g.style.left = (e.clientX - g.offsetWidth / 2) + 'px'; g.style.top = (e.clientY - g.offsetHeight / 2) + 'px'; };
    </script>
</body>
</html>
