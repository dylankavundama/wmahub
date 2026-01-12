<?php 
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint aux artistes
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'artiste') {
    header('Location: ../../auth/login.php');
    exit;
}

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
        .badge { padding: 0.4rem 0.8rem; border-radius: 99px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .badge-pending { background: rgba(255, 165, 0, 0.1); color: #ffa500; border: 1px solid rgba(255, 165, 0, 0.2); }
        .badge-success { background: rgba(34, 197, 94, 0.1); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.2); }
        .badge-info { background: rgba(59, 130, 246, 0.1); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.2); }
        .mobile-header { display: none; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; background: rgba(10, 10, 12, 0.8); backdrop-filter: blur(10px); position: sticky; top: 0; z-index: 80; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .btn-primary { background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%); box-shadow: 0 10px 20px -5px rgba(255, 102, 0, 0.4); transition: all 0.3s ease; text-decoration: none; display: inline-flex; items-center; gap: 0.5rem; color: #fff; font-weight: bold; padding: 0.75rem 1.5rem; border-radius: 0.75rem; }
        .search-bar { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 1rem; padding: 0.6rem 1.25rem; color: #fff; outline: none; transition: all 0.3s ease; }
        .search-bar:focus { border-color: #ff6600; background: rgba(255, 255, 255, 0.06); }
        .custom-select { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 0.75rem; color: #fff; font-size: 0.85rem; padding: 0.5rem 1rem; outline: none; cursor: pointer; }
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
                <a href="#" class="nav-link disabled opacity-50 cursor-not-allowed"><i class="fas fa-chart-line"></i>Stats</a>
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
            <header class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12">
                <div>
                    <h2 class="text-4xl font-black mb-2">Mon <span class="text-orange-500">Catalogue</span></h2>
                    <p class="text-gray-400">Gérez l'ensemble de vos oeuvres musicales.</p>
                </div>
                <div class="flex items-center gap-4">
                    <a href="submit.php" class="btn-primary"><i class="fas fa-rocket"></i>Nouvelle Sortie</a>
                </div>
            </header>

            <!-- Catalogue Controls -->
            <div class="glass-card mb-8 px-8 py-6 flex flex-col lg:flex-row lg:items-center justify-between gap-6 border-white/5">
                <div class="flex flex-col sm:flex-row items-center gap-4 flex-1">
                    <div class="relative w-full max-w-md">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-500"></i>
                        <input type="text" id="catalogueSearch" class="search-bar pl-12 w-full" placeholder="Rechercher un projet..." value="<?= htmlspecialchars($f_search) ?>">
                    </div>
                </div>
                
                <form method="GET" class="flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-2">
                        <label class="text-[10px] font-black uppercase text-gray-500">Filtrer par statut:</label>
                        <select name="f_status" class="custom-select" onchange="this.form.submit()">
                            <option value="all">Tous les projets</option>
                            <option value="en_attente" <?= $f_status === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                            <option value="en_preparation" <?= $f_status === 'en_preparation' ? 'selected' : '' ?>>En préparation</option>
                            <option value="distribue" <?= $f_status === 'distribue' ? 'selected' : '' ?>>Distribué</option>
                        </select>
                    </div>
                    <?php if ($f_status !== 'all' || $f_search !== ''): ?>
                        <a href="catalogue.php" class="text-[10px] font-black uppercase text-orange-500 hover:text-orange-400">
                            <i class="fas fa-times mr-1"></i>Réinitialiser
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Catalogue Grid/Table -->
            <div class="glass-card p-0 overflow-hidden shadow-2xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left" id="catalogueTable">
                        <thead>
                            <tr class="text-[10px] uppercase font-black text-gray-500 border-b border-white/5">
                                <th class="px-8 py-4">Projet & Détails</th>
                                <th class="px-8 py-4">Statut de Distribution</th>
                                <th class="px-8 py-4">Score (Vues)</th>
                                <th class="px-8 py-4">Date de Sortie</th>
                                <th class="px-8 py-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php if (empty($projects)): ?>
                                <tr>
                                    <td colspan="5" class="px-8 py-20 text-center text-gray-500">
                                        <i class="fas fa-music text-4xl mb-4 block opacity-20"></i>
                                        Aucun projet trouvé dans votre catalogue.
                                    </td>
                                </tr>
                            <?php else: foreach ($projects as $project): ?>
                                <tr class="hover:bg-white/[0.02] group transition-colors">
                                    <td class="px-8 py-5">
                                        <div class="flex items-center gap-4">
                                            <?php if ($project['cover_file']): ?>
                                                <img src="uploads/<?= $project['cover_file'] ?>" class="w-12 h-12 rounded-lg object-cover border border-white/10" alt="Cover">
                                            <?php else: ?>
                                                <div class="w-12 h-12 rounded-lg bg-white/5 flex items-center justify-center text-gray-600 border border-white/10">
                                                    <i class="fas fa-music"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <p class="font-bold text-white group-hover:text-orange-500 transition-colors uppercase tracking-tight"><?= htmlspecialchars($project['title']) ?></p>
                                                <p class="text-[10px] text-gray-500 font-bold uppercase"><?= htmlspecialchars($project['artist_name'] ?: $_SESSION['user_name']) ?> • <?= htmlspecialchars($project['type']) ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5">
                                        <?php $s = $project['status']; $b = "badge-" . ($s == 'distribue' ? 'success' : ($s == 'en_preparation' ? 'info' : 'pending')); ?>
                                        <span class="badge <?= $b ?>"><?= getStatusLabel($s) ?></span>
                                    </td>
                                    <td class="px-8 py-5">
                                        <div class="flex items-center gap-2">
                                            <div class="w-32 bg-white/5 h-1.5 rounded-full overflow-hidden">
                                                <?php 
                                                    $max_streams = 1000000; // Arbitrary max for progress
                                                    $percent = min(100, ($project['streams'] / $max_streams) * 100);
                                                ?>
                                                <div class="bg-gradient-to-r from-orange-500 to-amber-500 h-full rounded-full" style="width: <?= $percent ?>%"></div>
                                            </div>
                                            <span class="font-black text-xs"><?= number_format($project['streams'], 0, '.', ' ') ?></span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5">
                                        <p class="text-sm font-medium text-gray-400"><?= date('d/m/Y', strtotime($project['date_sortie'])) ?></p>
                                        <p class="text-[10px] text-gray-600 uppercase font-black">Sortie prévue</p>
                                    </td>
                                    <td class="px-8 py-5">
                                        <div class="flex items-center justify-center gap-3">
                                            <?php if ($project['audio_file']): ?>
                                                <a href="uploads/<?= $project['audio_file'] ?>" target="_blank" class="w-8 h-8 rounded-full bg-white/5 hover:bg-orange-500/20 hover:text-orange-500 flex items-center justify-center transition-all" title="Écouter">
                                                    <i class="fas fa-play text-[10px]"></i>
                                                </a>
                                            <?php endif; ?>
                                            <button class="w-8 h-8 rounded-full bg-white/5 hover:bg-blue-500/20 hover:text-blue-500 flex items-center justify-center transition-all" title="Détails" onclick="alert('Fonctionnalité de détails en cours de développement.')">
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
        const s = document.getElementById('sidebar'), o = document.getElementById('overlay'), t = document.getElementById('sidebarToggle'), g = document.getElementById('glow');
        function ts() { s.classList.toggle('active'); o.classList.toggle('active'); }
        if(t) t.onclick = ts; if(o) o.onclick = ts;
        document.onmousemove = (e) => { g.style.left = (e.clientX - g.offsetWidth / 2) + 'px'; g.style.top = (e.clientY - g.offsetHeight / 2) + 'px'; };

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
    </script>
</body>
</html>
