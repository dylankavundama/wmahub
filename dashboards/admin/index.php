<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint à l'admin uniquement
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../includes/performance_functions.php';
$db = getDBConnection();
// Traitement automatique des récompenses mensuelles le 28
processMonthlyAwards();

// Traitement des mises à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_project_status'])) {
        // Fetch project details for email before updating
        $stmt_info = $db->prepare("SELECT title, user_id FROM projects WHERE id = ?");
        $stmt_info->execute([$_POST['project_id']]);
        $project_info = $stmt_info->fetch();

        $stmt = $db->prepare("UPDATE projects SET status = ? WHERE id = ?");
        $stmt->execute([$_POST['status'], $_POST['project_id']]);
        
        // Notify Artist
        if ($project_info) {
            $status_labels = [
                'en_attente' => 'EN ATTENTE',
                'en_preparation' => 'EN PRÉPARATION',
                'distribue' => 'DISTRIBUÉ'
            ];
            $lbl = $status_labels[$_POST['status']] ?? $_POST['status'];
            createNotification($project_info['user_id'], 'project_update', "Le statut de votre projet '" . $project_info['title'] . "' est passé à : $lbl", $_POST['project_id']);

            // --- ENVOI D'EMAIL VIA SMTP ---
            require_once __DIR__ . '/../../includes/mailer.php';
            
            // Récupérer l'email et le nom de l'artiste
            $stmt_artist = $db->prepare("SELECT email, name FROM users WHERE id = ?");
            $stmt_artist->execute([$project_info['user_id']]);
            $artist = $stmt_artist->fetch();
            
            if ($artist) {
                // Email à l'artiste
                notifyStatusChange(
                    $artist['email'],
                    $artist['name'],
                    $project_info['title'],
                    $_POST['status']
                );
            }
            // ---------------------------------------
        }
    }
    if (isset($_POST['update_payment_status'])) {
        $stmt = $db->prepare("UPDATE projects SET payment_status = ? WHERE id = ?");
        $stmt->execute([$_POST['payment_status'], $_POST['project_id']]);

        // Notify Artist if paid
        if ($_POST['payment_status'] === 'paye') {
            $stmt_p = $db->prepare("SELECT user_id, title FROM projects WHERE id = ?");
            $stmt_p->execute([$_POST['project_id']]);
            $proj = $stmt_p->fetch();
            if ($proj) {
                createNotification($proj['user_id'], 'payment_received', "Paiement confirmé pour votre projet : " . $proj['title'], $_POST['project_id']);
            }
        }
    }
    if (isset($_POST['update_stats'])) {
        $stmt = $db->prepare("UPDATE projects SET streams = ?, revenue = ? WHERE id = ?");
        $stmt->execute([$_POST['streams'], $_POST['revenue'], $_POST['project_id']]);
    }
    header('Location: index.php');
    exit;
}

// Récupérer les filtres
$f_status = $_GET['f_status'] ?? 'all';
$f_month = $_GET['f_month'] ?? 'all';
$f_search = $_GET['search'] ?? '';

// Construire la requête
$query = "SELECT p.*, u.name as user_name, u.email as user_email FROM projects p JOIN users u ON p.user_id = u.id WHERE 1=1";
$params = [];

if ($f_status !== 'all') {
    $query .= " AND p.status = ?";
    $params[] = $f_status;
}

if ($f_month !== 'all') {
    $query .= " AND DATE_FORMAT(p.created_at, '%Y-%m') = ?";
    $params[] = $f_month;
}

if ($f_search !== '') {
    $query .= " AND (p.title LIKE ? OR u.name LIKE ? OR p.artist_name LIKE ?)";
    $params[] = "%$f_search%";
    $params[] = "%$f_search%";
    $params[] = "%$f_search%";
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$projects = $stmt->fetchAll();

// Récupérer les mois disponibles pour le filtre
$months = $db->query("SELECT DISTINCT DATE_FORMAT(created_at, '%Y-%m') as m FROM projects ORDER BY m DESC")->fetchAll();

// Stats calculation
$total_projects = count($projects);
$distributed_count = count(array_filter($projects, fn($p) => $p['status'] === 'distribue'));
$pending_payment = count(array_filter($projects, fn($p) => $p['payment_status'] !== 'paye'));

// Récupérer les revenus des packs promotionnels
$total_packs_revenue = 0;
// Récupérer le total des revenus générés par les projets (colonne revenue)
$total_projects_revenue = 0;

$prices = [
    'Starter' => (float)getSetting('pack_starter_usd', 15),
    'Pro' => (float)getSetting('pack_pro_usd', 35),
    'Premium' => (float)getSetting('pack_premium_usd', 75)
];

foreach($projects as $p) {
    if($p['payment_status'] === 'paye') {
        $price = $prices[$p['promo_pack']] ?? 0;
        $total_packs_revenue += $price;
    }
    $total_projects_revenue += (float)$p['revenue'];
}

// Déduire les retraits approuvés du total des revenus projets
$stmt_paid_out = $db->query("SELECT SUM(amount) FROM withdrawals WHERE status = 'approved'");
$total_paid_out = $stmt_paid_out->fetchColumn() ?: 0;
$total_projects_revenue -= $total_paid_out;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMA HUB - Administration</title>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8882238368661853"
     crossorigin="anonymous"></script>
    <!-- Scripts et CSS Prioritaires -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/jpeg" href="../../asset/placeholder.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin-shared.css">
    
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
            background: #0a0a0c !important; 
            color: #fff; 
            min-height: 100vh; 
            margin: 0;
            overflow-x: hidden;
        }

        /* Loader haute priorité */
        #wma-global-loader {
            position: fixed;
            inset: 0;
            background: #0a0a0c;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100000;
            transition: opacity 0.5s ease;
        }

        .loader-spin {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(255, 102, 0, 0.1);
            border-top-color: #ff6600;
            border-radius: 50%;
            animation: wma-spin 1s linear infinite;
        }

        @keyframes wma-spin { to { transform: rotate(360deg); } }
        
        .bg-glow { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .glow-spot { position: fixed; width: 40vw; height: 40vw; background: radial-gradient(circle, rgba(255, 102, 0, 0.05) 0%, transparent 70%); border-radius: 50%; z-index: -1; filter: blur(80px); pointer-events: none; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; display: flex; flex-direction: column; padding: 2rem 1.5rem; transition: all 0.3s ease; }
        .sidebar nav { overflow-y: auto; overflow-x: hidden; padding-right: 0.5rem; }
        .sidebar nav::-webkit-scrollbar { width: 4px; }
        .sidebar nav::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.02); border-radius: 10px; }
        .sidebar nav::-webkit-scrollbar-thumb { background: rgba(255, 102, 0, 0.3); border-radius: 10px; }
        .sidebar nav::-webkit-scrollbar-thumb:hover { background: rgba(255, 102, 0, 0.5); }
        .main-content { margin-left: 280px; padding: 2rem; min-height: 100vh; transition: all 0.3s ease; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; font-size: 0.9rem; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; transform: translateX(5px); }
        
        @media (max-width: 1024px) { 
            .sidebar { transform: translateX(-100%); } 
            .sidebar.active { transform: translateX(0); width: 280px; padding: 2rem 1.5rem; }
            .sidebar-overlay.active { display: block; }
            .main-content { margin-left: 0; padding: 1.5rem; } 
            .mobile-header { display: flex; }
        }
    </style>
</head>
<body>
    <div id="wma-global-loader">
        <div class="loader-spin"></div>
    </div>
    <div class="bg-glow"></div>
    <div id="glow" class="glow-spot"></div>

    <div class="mobile-header">
        <div class="flex items-center gap-3">
            <img src="../../asset/trans.png" alt="Logo" class="h-8">
            <span class="font-bold tracking-tighter">WMA ADMIN</span>
        </div>
        <button id="sidebarToggle" class="text-white text-2xl p-2"><i class="fas fa-bars"></i></button>
    </div>

    <div class="sidebar-overlay" id="overlay"></div>

    <aside class="sidebar" id="sidebar">
        <div class="flex items-center gap-4 mb-12 px-2">
            <img src="../../asset/trans.png" alt="Logo" class="h-10">
            <div>
                <h1 class="text-xl font-black bg-gradient-to-r from-orange-500 to-orange-300 bg-clip-text text-transparent tracking-tighter leading-tight">WMA HUB</h1>
                <p class="text-[8px] text-gray-500 font-bold uppercase tracking-[1px] -mt-1">We move, WMAFam</p>
            </div>
        </div>
        <nav class="flex-1">
            <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>"><i class="fas fa-layer-group"></i> Gestion Projets</a>
            <a href="subscriptions.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'subscriptions.php' ? 'active' : '' ?>"><i class="fas fa-crown"></i> Abonnements</a>
            <a href="payments.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'payments.php' ? 'active' : '' ?>"><i class="fas fa-history"></i> Paiements</a>
            <a href="artists.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'artists.php' ? 'active' : '' ?>"><i class="fas fa-microphone-alt"></i> Artistes</a>
            <a href="distributors.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'distributors.php' ? 'active' : '' ?>"><i class="fas fa-truck-loading"></i> Distributeurs</a>
            <a href="revenues.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'revenues.php' ? 'active' : '' ?>"><i class="fas fa-wallet"></i> Revenus Gérés</a>
            <a href="payouts.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'payouts.php' ? 'active' : '' ?>"><i class="fas fa-money-bill-transfer"></i> Retraits & Payouts</a>
            <a href="employees.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'employees.php' ? 'active' : '' ?>"><i class="fas fa-users-cog"></i> Équipe & Staff</a>
            <a href="tasks.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'tasks.php' ? 'active' : '' ?>"><i class="fas fa-tasks"></i> Gestion Tâches</a>
            <a href="salaries.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'salaries.php' ? 'active' : '' ?>"><i class="fas fa-money-check-alt"></i> Gestion Salaires</a>
            <a href="project_files.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'project_files.php' ? 'active' : '' ?>"><i class="fas fa-folder-open"></i> Fichier Projet</a>
            <a href="service_cards.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'service_cards.php' ? 'active' : '' ?>"><i class="fas fa-id-card"></i> Cartes de Service</a>
            <a href="notifications.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'notifications.php' ? 'active' : '' ?>"><i class="fas fa-bell"></i> Notifications</a>
            <a href="finance.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'finance.php' ? 'active' : '' ?>"><i class="fas fa-chart-pie"></i> Rapports Financiers</a>
            <a href="site_stats.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'site_stats.php' ? 'active' : '' ?>"><i class="fas fa-chart-line"></i> Statistiques Site</a>
            <a href="hero_slider.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'hero_slider.php' ? 'active' : '' ?>"><i class="fas fa-images"></i> Gestion Slider</a>
            <a href="users.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>"><i class="fas fa-user-friends"></i> Utilisateurs</a>
            <?php if ($_SESSION['role'] === 'superadmin'): ?>
                <div class="mt-4 pt-4 border-t border-white/5">
                    <p class="text-[9px] text-yellow-500/50 font-black uppercase tracking-widest px-4 mb-2">Master Controls</p>
                    <a href="../superadmin/index.php" class="nav-link !text-yellow-500 hover:!bg-yellow-500/10"><i class="fas fa-crown"></i> Console Superadmin</a>
                </div>
            <?php endif; ?>
        </nav>
        <div class="mt-auto pt-6 border-t border-white/5">
            <div class="flex items-center gap-4 mb-8 px-2">
                <div class="w-10 h-10 rounded-full bg-orange-500/10 border border-orange-500/20 flex items-center justify-center text-orange-500"><i class="fas fa-user-shield"></i></div>
                <div>
                    <p class="text-sm font-bold text-white"><?= explode(' ', $_SESSION['user_name'])[0] ?></p>
                    <p class="text-[10px] text-gray-500 uppercase font-black tracking-widest"><?= $_SESSION['role'] ?></p>
                </div>
            </div>
            <a href="../../auth/logout.php" class="nav-link !text-red-500 hover:!bg-red-500/10"><i class="fas fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="flex flex-col md:flex-row md:items-center justify-between gap-8 mb-12">
            <div>
                <h2 class="text-4xl font-black tracking-tighter text-white">Tableau de <span class="text-orange-500">Bord</span></h2>
                <p class="text-gray-400 mt-2">Gérez les demandes de distribution et le catalogue.</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="relative hidden md:block">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-600"></i>
                    <input type="text" id="tableSearch" name="search" class="search-bar pl-12" placeholder="Rechercher..." value="<?= htmlspecialchars($f_search) ?>">
                </div>
                <button onclick="window.location.reload()" class="bg-white/5 hover:bg-white/10 text-white p-3 rounded-xl transition-all border border-white/10"><i class="fas fa-sync-alt"></i></button>
                <?php include '../../includes/header_notifications.php'; ?>
            </div>
        </header>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <div class="glass-card">
                <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest mb-2">Total Projets</p>
                <p class="text-4xl font-black text-white"><?= $total_projects ?></p>
            </div>
            <div class="glass-card">
                <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest mb-2">Distributions</p>
                <p class="text-4xl font-black text-white"><?= $distributed_count ?></p>
            </div>
            <div class="glass-card">
                <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest mb-2">Paiements Attente</p>
                <p class="text-4xl font-black text-amber-500"><?= $pending_payment ?></p>
            </div>
            <a href="finance.php" class="glass-card border-green-500/20 hover:border-green-500/40 block">
                <p class="text-[10px] text-green-500 font-black uppercase tracking-widest mb-2">Ventes Packs</p>
                <p class="text-4xl font-black text-white"><?= number_format($total_packs_revenue, 0, '.', ' ') ?>$</p>
            </a>
            <div class="glass-card border-blue-500/20">
                <p class="text-[10px] text-blue-500 font-black uppercase tracking-widest mb-2">Total Revenus Projets (Net)</p>
                <p class="text-4xl font-black text-white"><?= number_format($total_projects_revenue, 2, '.', ' ') ?>$</p>
            </div>
        </div>

        <!-- Table Container -->
        <div class="glass-card p-0 overflow-hidden shadow-2xl border-white/5">
            <div class="px-8 py-6 border-b border-white/5 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <h3 class="text-lg font-bold flex items-center gap-3"><i class="fas fa-compact-disc text-orange-500"></i> Dernières demandes</h3>
                
                <form method="GET" class="flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-2">
                        <label class="text-[10px] font-black uppercase text-gray-500">Statut:</label>
                        <select name="f_status" class="custom-select" onchange="this.form.submit()">
                            <option value="all">Tous</option>
                            <option value="en_attente" <?= $f_status === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                            <option value="en_preparation" <?= $f_status === 'en_preparation' ? 'selected' : '' ?>>Préparation</option>
                            <option value="distribue" <?= $f_status === 'distribue' ? 'selected' : '' ?>>Distribué</option>
                        </select>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <label class="text-[10px] font-black uppercase text-gray-500">Mois:</label>
                        <select name="f_month" class="custom-select" onchange="this.form.submit()">
                            <option value="all">Tous les mois</option>
                            <?php foreach ($months as $m): ?>
                                <?php 
                                    $date = DateTime::createFromFormat('Y-m', $m['m']);
                                    $label = ucfirst($date->format('F Y')); // Note: Month names will be in English unless locale is set
                                ?>
                                <option value="<?= $m['m'] ?>" <?= $f_month === $m['m'] ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if ($f_status !== 'all' || $f_month !== 'all' || $f_search !== ''): ?>
                        <a href="index.php" class="text-[10px] font-black uppercase text-orange-500 hover:text-orange-400">
                            <i class="fas fa-times mr-1"></i>Reset
                        </a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="admin-table text-left" id="projectsTable">
                    <thead><tr><th class="px-8">Projet & Artiste</th><th>Contact</th><th>Fichier Audio</th><th>Assets & Pack</th><th>Statut</th><th>Paiement</th><th>Streaming & Revenus ($)</th></tr></thead>
                    <tbody>
                        <?php foreach ($projects as $project): ?>
                            <tr>
                                <td class="px-8">
                                    <div class="font-bold text-white"><?= htmlspecialchars($project['title']) ?></div>
                                    <div class="text-[10px] text-orange-500 font-black uppercase"><?= htmlspecialchars($project['artist_name'] ?: $project['user_name']) ?></div>
                                </td>
                                 <td>
                                    <div class="text-xs font-bold text-white"><?= htmlspecialchars($project['full_name'] ?? 'N/A') ?></div>
                                    <div class="text-xs text-gray-400"><?= htmlspecialchars($project['email'] ?? $project['user_email'] ?? 'N/A') ?></div>
                                    <div class="text-xs text-orange-500"><?= htmlspecialchars($project['phone']) ?></div>
                                    <div class="text-[10px] text-gray-500"><?= htmlspecialchars($project['city']) ?></div>
                                 </td>
                                 <td>
                                    <div class="flex items-center gap-2">
                                        <?php if ($project['audio_file']): ?>
                                            <button onclick="playAudio('<?= $project['id'] ?>', '../artiste/uploads/<?= $project['audio_file'] ?>')" 
                                                    class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-blue-500/20 text-blue-400 hover:bg-blue-500 hover:text-white transition-all border border-blue-500/30 text-[10px] font-bold" 
                                                    title="Écouter" id="play-btn-<?= $project['id'] ?>">
                                                <i class="fas fa-play"></i> LIRE
                                            </button>
                                            <a href="../artiste/uploads/<?= $project['audio_file'] ?>" download 
                                               class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-green-500/20 text-green-400 hover:bg-green-500 hover:text-white transition-all border border-green-500/30 text-[10px] font-bold" 
                                               title="Télécharger">
                                                <i class="fas fa-download"></i> SAVE
                                            </a>
                                        <?php else: ?>
                                            <button disabled class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/5 text-gray-600 border border-white/5 opacity-50 cursor-not-allowed text-[10px] font-bold">
                                                <i class="fas fa-play"></i> LIRE
                                            </button>
                                            <button disabled class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/5 text-gray-600 border border-white/5 opacity-50 cursor-not-allowed text-[10px] font-bold">
                                                <i class="fas fa-download"></i> SAVE
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <?php if ($project['cover_file']): ?><a href="../artiste/uploads/<?= $project['cover_file'] ?>" target="_blank" class="w-8 h-8 rounded-lg bg-purple-500/10 flex items-center justify-center text-purple-500"><i class="fas fa-image text-[10px]"></i></a><?php endif; ?>
                                        <div class="text-[10px] font-black text-orange-500 uppercase bg-orange-500/10 px-2 py-1 rounded">Pack <?= htmlspecialchars($project['promo_pack']) ?></div>
                                    </div>
                                </td>
                                <td><form method="POST"><input type="hidden" name="project_id" value="<?= $project['id'] ?>"><select name="status" onchange="this.form.submit()" class="custom-select"><option value="en_attente" <?= $project['status'] === 'en_attente' ? 'selected' : '' ?>>En attente</option><option value="en_preparation" <?= $project['status'] === 'en_preparation' ? 'selected' : '' ?>>Préparation</option><option value="distribue" <?= $project['status'] === 'distribue' ? 'selected' : '' ?>>Distribué</option></select><input type="hidden" name="update_project_status" value="1"></form></td>
                                <td><form method="POST"><input type="hidden" name="project_id" value="<?= $project['id'] ?>"><select name="payment_status" onchange="this.form.submit()" class="custom-select <?= $project['payment_status'] === 'paye' ? '!text-green-500' : '!text-amber-500' ?>"><option value="en_attente" <?= $project['payment_status'] === 'en_attente' ? 'selected' : '' ?>>Non payé</option><option value="paye" <?= $project['payment_status'] === 'paye' ? 'selected' : '' ?>>Payé ✓</option></select><input type="hidden" name="update_payment_status" value="1"></form></td>
                                <td>
                                    <form method="POST" class="flex flex-col gap-2">
                                        <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                                        <div class="flex items-center gap-2">
                                            <div class="relative flex-1">
                                                <input type="number" name="streams" value="<?= $project['streams'] ?>" class="custom-select !w-full !pr-8" min="0" placeholder="Vues">
                                                <i class="fas fa-chart-line absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 text-[10px]"></i>
                                            </div>
                                            <div class="relative flex-1">
                                                <input type="number" step="0.01" name="revenue" value="<?= $project['revenue'] ?>" class="custom-select !w-full !pr-8" min="0" placeholder="Revenu $">
                                                <i class="fas fa-dollar-sign absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 text-[10px]"></i>
                                            </div>
                                            <button type="submit" name="update_stats" class="p-2 bg-orange-500/10 text-orange-500 rounded-lg hover:bg-orange-500 hover:text-white transition-all">
                                                <i class="fas fa-save"></i>
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const toggle = document.getElementById('sidebarToggle');

        function toggleSidebar() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        if (toggle) toggle.addEventListener('click', toggleSidebar);
        if (overlay) overlay.addEventListener('click', toggleSidebar);

        const glow = document.getElementById('glow');
        document.addEventListener('mousemove', (e) => { 
            if (glow) {
                glow.style.left = (e.clientX - 200) + 'px'; 
                glow.style.top = (e.clientY - 200) + 'px'; 
            }
        });
        
        // Handle search input for both instant feedback and form submission
        const searchInput = document.getElementById('tableSearch');
        if (searchInput) {
            searchInput.addEventListener('keyup', function(e) {
                if (e.key === 'Enter') {
                    const url = new URL(window.location.href);
                    url.searchParams.set('search', this.value);
                    window.location.href = url.href;
                    return;
                }
                const value = this.value.toLowerCase();
                document.querySelectorAll('#projectsTable tbody tr').forEach(row => { row.style.display = row.innerText.toLowerCase().indexOf(value) > -1 ? '' : 'none'; });
            });
        }
        window.addEventListener('load', () => {
             const loader = document.getElementById('wma-global-loader');
             if (loader) {
                loader.style.opacity = '0';
                setTimeout(() => loader.style.display = 'none', 500);
             }
        });

        let currentAudio = null;
        let currentPlayingId = null;

        function playAudio(id, url) {
            const btn = document.getElementById(`play-btn-${id}`);
            const icon = btn.querySelector('i');

            if (currentPlayingId === id && currentAudio) {
                if (currentAudio.paused) {
                    currentAudio.play();
                    icon.className = 'fas fa-pause';
                } else {
                    currentAudio.pause();
                    icon.className = 'fas fa-play';
                }
                return;
            }

            if (currentAudio) {
                currentAudio.pause();
                if (currentPlayingId) {
                    const prevBtn = document.getElementById(`play-btn-${currentPlayingId}`);
                    if (prevBtn) prevBtn.querySelector('i').className = 'fas fa-play';
                }
            }

            currentAudio = new Audio(url);
            currentPlayingId = id;
            
            currentAudio.play();
            icon.className = 'fas fa-pause';

            currentAudio.onended = () => {
                icon.className = 'fas fa-play';
                currentAudio = null;
                currentPlayingId = null;
            };
        }
    </script>
    <?php include_once __DIR__ . '/../../includes/language_selector.php'; ?>
</body>
</html>
