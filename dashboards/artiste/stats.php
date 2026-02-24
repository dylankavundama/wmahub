<?php
require_once __DIR__ . '/auth_artist.php';

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
    <link rel="icon" type="image/jpeg" href="../../asset/placeholder.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; margin: 0; min-height: 100vh; overflow-x: hidden; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .glow-spot { position: fixed; width: 600px; height: 600px; background: radial-gradient(circle, rgba(255,102,0,0.05) 0%, transparent 70%); border-radius: 50%; pointer-events: none; z-index: -1; transition: all 0.3s ease; }
        .sidebar { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .sidebar.active { transform: translateX(0); }
        @media (max-width: 1024px) { .sidebar { transform: translateX(-100%); position: fixed; z-index: 50; height: 100vh; } }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px); border: 1px border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 20px; transition: all 0.3s ease; padding: 24px; }
        .glass-card:hover { background: rgba(255, 255, 255, 0.05); border-color: rgba(255, 102, 0, 0.2); transform: translateY(-5px); }
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: #94a3b8; transition: all 0.3s ease; margin-bottom: 4px; }
        .nav-link:hover:not(.active) { background: rgba(255, 255, 255, 0.03); color: #fff; }
        .nav-link.active { background: linear-gradient(135deg, #ff6600 0%, #ff8c00 100%); color: #fff; box-shadow: 0 4px 15px rgba(255, 102, 0, 0.3); }
        .nav-link i { font-size: 1.1rem; }
        .stat-bar-bg { background: rgba(255, 255, 255, 0.05); height: 8px; border-radius: 4px; overflow: hidden; }
        .stat-bar-fill { background: linear-gradient(90deg, #ff6600, #ffae00); height: 100%; border-radius: 4px; transition: width 1.5s cubic-bezier(0.19, 1, 0.22, 1); width: 0%; }
        #wma-global-loader { position: fixed; inset: 0; background: #0a0a0c; z-index: 9999; display: flex; align-items: center; justify-content: center; transition: opacity 0.5s ease; }
        .loader-spin { width: 50px; height: 50px; border: 3px solid rgba(255, 102, 0, 0.1); border-top-color: #ff6600; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div id="wma-global-loader">
        <div class="loader-spin"></div>
    </div>
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
                        <p class="text-sm font-bold truncate"><?= htmlspecialchars($_SESSION['user_name']) ?></p>
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
            <header class="mb-12">
                <h2 class="text-4xl font-black tracking-tighter mb-2">Performances & <span class="text-orange-500">Statistiques</span></h2>
                <p class="text-gray-500 font-medium tracking-tight">Analysez l'impact de votre musique sur les plateformes.</p>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                <div class="glass-card">
                    <p class="text-[10px] text-gray-500 font-bold uppercase mb-1">Streams Totaux</p>
                    <p class="text-4xl font-black text-orange-500"><?= number_format($total_streams, 0, '.', ' ') ?></p>
                </div>
                <div class="glass-card">
                    <p class="text-[10px] text-gray-500 font-bold uppercase mb-1">Meilleur Titre</p>
                    <p class="text-xl font-bold truncate"><?= !empty($projects) ? htmlspecialchars($projects[0]['title']) : 'Aucun' ?></p>
                </div>
                <div class="glass-card">
                    <p class="text-[10px] text-gray-500 font-bold uppercase mb-1">Projets Actifs</p>
                    <p class="text-4xl font-black text-white"><?= count($projects) ?></p>
                </div>
            </div>

            <div class="glass-card">
                <h3 class="text-xl font-bold mb-8 flex items-center gap-3">
                    <i class="fas fa-chart-bar text-orange-500"></i>
                    Répartition des Streams par Projet
                </h3>

                <?php if (empty($projects)): ?>
                    <div class="py-20 text-center text-gray-500 uppercase font-black tracking-widest text-[10px]">
                        Aucune donnée statistique disponible.
                    </div>
                <?php else: foreach ($projects as $project): 
                    $width = $max_streams_single > 0 ? ($project['streams'] / $max_streams_single) * 100 : 0;
                ?>
                    <div class="mb-8 last:mb-0">
                        <div class="flex justify-between items-center mb-2">
                            <div>
                                <h4 class="font-bold text-white uppercase"><?= htmlspecialchars($project['title']) ?></h4>
                                <p class="text-[10px] text-gray-500 uppercase font-black tracking-widest"><?= htmlspecialchars($project['genre']) ?> • <?= htmlspecialchars($project['type']) ?></p>
                            </div>
                            <div class="text-right">
                                <span class="text-orange-500 font-black tracking-tighter"><?= number_format($project['streams'], 0, '.', ' ') ?></span>
                                <span class="text-[8px] text-gray-600 font-black uppercase ml-1 tracking-widest">Vues Score</span>
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
                glow.style.left = (e.clientX - (glow.offsetWidth || 0) / 2) + 'px';
                glow.style.top = (e.clientY - (glow.offsetHeight || 0) / 2) + 'px';
            }
        };

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

        window.addEventListener('load', () => {
            const loader = document.getElementById('wma-global-loader');
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(() => loader.style.display = 'none', 500);
            }
        });
    </script>
</body>
</html>
