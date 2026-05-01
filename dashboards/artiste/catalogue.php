<?php
require_once __DIR__ . '/auth_artist.php';

$pageTitle = 'Mon Catalogue - WMA Hub';

$db = getDBConnection();

// Récupérer les filtres
$f_status = $_GET['f_status'] ?? 'all';
$f_search = $_GET['search'] ?? '';

// Construire la requête
$query = "SELECT * FROM projects WHERE user_id = ? ";
$params = [$_SESSION['user_id']];

if ($f_status !== 'all') {
    $query .= " AND status = ?";
    $params[] = $f_status;
}

if ($f_search !== '') {
    $query .= " AND (title LIKE ? OR artist_name LIKE ?)";
    $params[] = "%$f_search%";
    $params[] = "%$f_search%";
}

$query .= " ORDER BY created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
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
    <link rel="icon" type="image/png" href="/asset/icon.png"><link rel="apple-touch-icon" href="/asset/icon.png">
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
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px); border: 1px border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 20px; transition: all 0.3s ease; }
        .glass-card:hover { background: rgba(255, 255, 255, 0.05); border-color: rgba(255, 102, 0, 0.2); transform: translateY(-5px); }
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: #94a3b8; transition: all 0.3s ease; margin-bottom: 4px; }
        .nav-link:hover:not(.active) { background: rgba(255, 255, 255, 0.03); color: #fff; }
        .nav-link.active { background: linear-gradient(135deg, #ff6600 0%, #ff8c00 100%); color: #fff; box-shadow: 0 4px 15px rgba(255, 102, 0, 0.3); }
        .nav-link i { font-size: 1.1rem; }
        .avatar-md { width: 44px; height: 44px; border-radius: 12px; object-fit: cover; }
        .stat-bar-bg { background: rgba(255, 255, 255, 0.05); height: 6px; border-radius: 3px; overflow: hidden; }
        .stat-bar-fill { background: linear-gradient(90deg, #ff6600, #ffae00); height: 100%; border-radius: 3px; }
        #wma-global-loader { position: fixed; inset: 0; background: #0a0a0c; z-index: 9999; display: flex; align-items: center; justify-content: center; transition: opacity 0.5s ease; }
        .loader-spin { width: 50px; height: 50px; border: 3px solid rgba(255, 102, 0, 0.1); border-top-color: #ff6600; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        #catalogueTable th { padding: 16px 32px; font-size: 10px; text-transform: uppercase; font-weight: 900; color: #6b7280; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        #catalogueTable td { padding: 20px 32px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        #catalogueTable tr:hover { background: rgba(255, 255, 255, 0.02); }
        .search-bar { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 12px; padding: 12px 16px; color: #fff; transition: all 0.3s ease; }
        .search-bar:focus { outline: none; border-color: #ff6600; background: rgba(255, 255, 255, 0.05); box-shadow: 0 0 15px rgba(255, 102, 0, 0.1); }
        .custom-select { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 8px; padding: 4px 12px; color: #fff; font-size: 12px; font-weight: bold; cursor: pointer; }
        .custom-select:focus { outline: none; border-color: #ff6600; }
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

        <main class="flex-1 p-6 lg:p-12">
            <header class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12">
                <div>
                    <h2 class="text-4xl font-black tracking-tighter mb-2">Mon <span class="text-orange-500">Catalogue</span></h2>
                    <p class="text-gray-500 font-medium tracking-tight">Gérez l'ensemble de vos oeuvres musicales.</p>
                </div>
                <a href="submit.php" class="px-6 py-3 bg-white text-black rounded-full font-bold hover:bg-orange-500 hover:text-white transition-all duration-300 flex items-center gap-2 shadow-xl shadow-white/5">
                    <i class="fas fa-rocket"></i>
                    Nouvelle Sortie
                </a>
            </header>

            <!-- Catalogue Controls -->
            <div class="glass-card mb-8 p-6 flex flex-col md:flex-row justify-between items-center gap-6">
                <div class="flex items-center gap-4 flex-1 w-full">
                    <div class="relative flex-1 max-w-md w-full">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-500"></i>
                        <input type="text" id="catalogueSearch" class="search-bar pl-12 w-full" placeholder="Rechercher un projet..." value="<?= htmlspecialchars($f_search) ?>">
                    </div>
                </div>
                
                <form method="GET" class="flex items-center gap-4 w-full md:w-auto">
                    <div class="flex items-center gap-2 whitespace-nowrap">
                        <label class="text-[10px] font-black uppercase text-gray-500">Filtrer par statut:</label>
                        <select name="f_status" class="custom-select" onchange="this.form.submit()">
                            <option value="all">Tous les statuts</option>
                            <option value="en_attente" <?= $f_status === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                            <option value="en_preparation" <?= $f_status === 'en_preparation' ? 'selected' : '' ?>>En préparation</option>
                            <option value="distribue" <?= $f_status === 'distribue' ? 'selected' : '' ?>>Distribué</option>
                        </select>
                    </div>
                    <?php if ($f_status !== 'all' || $f_search !== ''): ?>
                        <a href="catalogue.php" class="text-[10px] font-black uppercase text-orange-500 hover:text-orange-400 whitespace-nowrap">
                            <i class="fas fa-times mr-1"></i>Réinitialiser
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Catalogue Grid/Table -->
            <div class="glass-card p-0 overflow-hidden shadow-2xl">
                <div class="overflow-x-auto">
                    <table id="catalogueTable" class="w-full text-left">
                        <thead>
                            <tr>
                                <th>Projet & Détails</th>
                                <th>Statut</th>
                                <th>Score (Vues)</th>
                                <th>Date de Sortie</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php if (empty($projects)): ?>
                                <tr>
                                    <td colspan="5" class="py-20 text-center text-gray-500">
                                        <i class="fas fa-music text-4xl mb-4 block opacity-20"></i>
                                        <p class="uppercase font-black tracking-widest text-[10px]">Aucun projet trouvé dans votre catalogue.</p>
                                    </td>
                                </tr>
                            <?php else: foreach ($projects as $project): ?>
                                <tr class="group">
                                    <td>
                                        <div class="flex items-center gap-4">
                                            <?php if ($project['cover_file']): ?>
                                                <img src="uploads/<?= $project['cover_file'] ?>" class="avatar-md shadow-lg group-hover:scale-105 transition-transform" alt="Cover">
                                            <?php else: ?>
                                                <div class="avatar-md flex items-center justify-center text-gray-600 bg-white/5">
                                                    <i class="fas fa-music"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <p class="font-bold text-white uppercase group-hover:text-orange-500 transition-colors"><?= htmlspecialchars($project['title']) ?></p>
                                                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest"><?= htmlspecialchars($project['artist_name'] ?: $_SESSION['user_name']) ?> • <?= htmlspecialchars($project['type']) ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
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
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <div class="stat-bar-bg flex-1 min-w-[100px]">
                                                <?php 
                                                    $max_streams = 1000000;
                                                    $percent = min(100, ($project['streams'] / $max_streams) * 100);
                                                ?>
                                                <div class="stat-bar-fill" style="width: <?= $percent ?>%"></div>
                                            </div>
                                            <span class="font-black text-[10px] tracking-widest"><?= number_format($project['streams'], 0, '.', ' ') ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-gray-400"><?= date('d/m/Y', strtotime($project['date_sortie'])) ?></span>
                                            <span class="text-[8px] text-gray-600 uppercase font-black tracking-widest">Sortie prévue</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center justify-center gap-4">
                                            <?php if ($project['audio_file']): ?>
                                                <a href="uploads/<?= $project['audio_file'] ?>" target="_blank" class="w-8 h-8 rounded-full border border-white/5 flex items-center justify-center text-gray-400 hover:text-orange-500 hover:border-orange-500/50 transition-all" title="Écouter">
                                                    <i class="fas fa-play text-[10px] ml-0.5"></i>
                                                </a>
                                            <?php endif; ?>
                                            <button class="w-8 h-8 rounded-full border border-white/5 flex items-center justify-center text-gray-400 hover:text-blue-500 hover:border-blue-500/50 transition-all" title="Détails" onclick="alert('Fonctionnalité de détails en cours de développement.')">
                                                <i class="fas fa-eye text-[10px]"></i>
                                            </button>
                                        </div>
                                    </td>
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
                glow.style.left = (e.clientX - (glow.offsetWidth || 0) / 2) + 'px';
                glow.style.top = (e.clientY - (glow.offsetHeight || 0) / 2) + 'px';
            }
        };

        // Search logic
        const searchInput = document.getElementById('catalogueSearch');
        searchInput.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                const url = new URL(window.location.href);
                url.searchParams.set('search', this.value);
                window.location.href = url.href;
                return;
            }
            const value = this.value.toLowerCase();
            document.querySelectorAll('#catalogueTable tbody tr').forEach(row => {
                if(row.cells.length > 1) {
                    row.style.display = row.innerText.toLowerCase().indexOf(value) > -1 ? '' : 'none';
                }
            });
        });

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
