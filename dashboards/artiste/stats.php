<?php 
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint aux artistes
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'artiste') {
    header('Location: ../../auth/login.php');
    exit;
}

$pageTitle = 'Mes Statistiques - WMA Hub';

$db = getDBConnection();

// Récupérer les projets de l'utilisateur avec leurs streams
$stmt = $db->prepare("SELECT title, artist_name, streams, genre, type, date_sortie FROM projects WHERE user_id = ? ORDER BY streams DESC");
$stmt->execute([$_SESSION['user_id']]);
$projects = $stmt->fetchAll();

$total_streams = array_sum(array_column($projects, 'streams'));
$max_streams_single = !empty($projects) ? $projects[0]['streams'] : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="icon" type="image/png" href="../../asset/icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
        .mobile-header { display: none; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; background: rgba(10, 10, 12, 0.8); backdrop-filter: blur(10px); position: sticky; top: 0; z-index: 80; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .stat-bar-bg { background: rgba(255, 255, 255, 0.05); height: 12px; border-radius: 6px; overflow: hidden; position: relative; }
        .stat-bar-fill { background: linear-gradient(90deg, #ff6600, #ffb380); height: 100%; border-radius: 6px; transition: width 1s cubic-bezier(0.17, 0.67, 0.83, 0.67); }
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
            <div class="flex items-center gap-4 mb-10 px-2">
                <img src="../../asset/trans.png" alt="Logo" class="h-10">
                <div>
                    <h1 class="text-xl font-bold bg-gradient-to-r from-orange-500 to-orange-300 bg-clip-text text-transparent leading-none">WMA HUB</h1>
                    <p class="text-[8px] text-gray-500 font-bold uppercase tracking-[1px] mt-1">We Farm Your Talent</p>
                </div>
            </div>
            <nav class="flex-1">
                <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>"><i class="fas fa-th-large"></i>Tableau de bord</a>
                <a href="submit.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'submit.php' ? 'active' : '' ?>"><i class="fas fa-plus-circle"></i>Soumettre</a>
                <a href="services.php" class="nav-link <?= strpos(basename($_SERVER['PHP_SELF']), 'services') !== false ? 'active' : '' ?>"><i class="fas fa-magic"></i>Services</a>
                <a href="notifications.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'notifications.php' ? 'active' : '' ?>"><i class="fas fa-bell"></i>Notifications</a>
                <a href="catalogue.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'catalogue.php' ? 'active' : '' ?>"><i class="fas fa-music"></i>Catalogue</a>
                <a href="stats.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'stats.php' ? 'active' : '' ?>"><i class="fas fa-chart-line"></i>Stats</a>
                <a href="#" class="nav-link disabled opacity-50 cursor-not-allowed"><i class="fas fa-wallet"></i>Revenus</a>
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
            <header class="mb-12">
                <h2 class="text-4xl font-black mb-2">Performances & <span class="text-orange-500">Statistiques</span></h2>
                <p class="text-gray-400">Analysez l'impact de votre musique sur les plateformes.</p>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                <div class="glass-card">
                    <p class="text-xs text-gray-500 font-bold uppercase mb-1">Streams Totaux</p>
                    <p class="text-4xl font-black text-orange-500"><?= number_format($total_streams, 0, '.', ' ') ?></p>
                </div>
                <div class="glass-card">
                    <p class="text-xs text-gray-500 font-bold uppercase mb-1">Meilleur Titre</p>
                    <p class="text-xl font-bold truncate"><?= !empty($projects) ? htmlspecialchars($projects[0]['title']) : 'Aucun' ?></p>
                </div>
                <div class="glass-card">
                    <p class="text-xs text-gray-500 font-bold uppercase mb-1">Projets Actifs</p>
                    <p class="text-4xl font-black text-white"><?= count($projects) ?></p>
                </div>
            </div>

            <div class="glass-card p-8 shadow-2xl">
                <h3 class="text-xl font-bold mb-8 flex items-center gap-3">
                    <i class="fas fa-chart-bar text-orange-500"></i>
                    Répartition des Streams par Projet
                </h3>

                <?php if (empty($projects)): ?>
                    <div class="py-20 text-center text-gray-500 uppercase font-black tracking-widest text-xs">
                        Aucune donnée statistique disponible.
                    </div>
                <?php else: foreach ($projects as $project): 
                    $width = $max_streams_single > 0 ? ($project['streams'] / $max_streams_single) * 100 : 0;
                ?>
                    <div class="mb-8 last:mb-0">
                        <div class="flex justify-between items-end mb-2">
                            <div>
                                <h4 class="font-bold text-white uppercase tracking-tight"><?= htmlspecialchars($project['title']) ?></h4>
                                <p class="text-[10px] text-gray-500 uppercase font-black"><?= htmlspecialchars($project['genre']) ?> • <?= htmlspecialchars($project['type']) ?></p>
                            </div>
                            <div class="text-right">
                                <span class="text-lg font-black text-orange-400"><?= number_format($project['streams'], 0, '.', ' ') ?></span>
                                <span class="text-[10px] text-gray-600 font-black uppercase ml-1">Vues Score</span>
                            </div>
                        </div>
                        <div class="stat-bar-bg">
                            <div class="stat-bar-fill" style="width: 0%;" data-width="<?= $width ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </main>
    </div>

    <script>
        const s = document.getElementById('sidebar'), o = document.getElementById('overlay'), t = document.getElementById('sidebarToggle'), g = document.getElementById('glow');
        function ts() { s.classList.toggle('active'); o.classList.toggle('active'); }
        if(t) t.onclick = ts; if(o) o.onclick = ts;
        document.onmousemove = (e) => { g.style.left = (e.clientX - g.offsetWidth / 2) + 'px'; g.style.top = (e.clientY - g.offsetHeight / 2) + 'px'; };

        // Animate progress bars on load
        window.addEventListener('scroll', () => {
             document.querySelectorAll('.stat-bar-fill').forEach(bar => {
                const rect = bar.getBoundingClientRect();
                if(rect.top < window.innerHeight && rect.bottom >= 0) {
                    bar.style.width = bar.getAttribute('data-width');
                }
            });
        }, { passive: true });
        
        // Initial trigger
        setTimeout(() => {
            document.querySelectorAll('.stat-bar-fill').forEach(bar => {
                bar.style.width = bar.getAttribute('data-width');
            });
        }, 300);
    </script>
</body>
</html>
