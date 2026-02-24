<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint à l'admin uniquement
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = getDBConnection();

// Filtres
$f_status = $_GET['f_status'] ?? 'active';
$f_plan = $_GET['f_plan'] ?? 'all';
$f_duration = $_GET['f_duration'] ?? 'all';
$f_sort = $_GET['f_sort'] ?? 'exp_soon';
$f_search = $_GET['search'] ?? '';

// Requête pour les abonnements
$query = "SELECT s.*, u.name as artist_name, u.email as artist_email 
          FROM subscriptions s 
          JOIN users u ON s.user_id = u.id 
          WHERE 1=1";
$params = [];

if ($f_status !== 'all') {
    $query .= " AND s.status = ?";
    $params[] = $f_status;
}

if ($f_plan !== 'all') {
    $query .= " AND s.plan_type = ?";
    $params[] = $f_plan;
}

if ($f_duration !== 'all') {
    if ($f_duration === '7d') {
        $query .= " AND s.end_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND s.end_date >= CURDATE()";
    } elseif ($f_duration === '30d') {
        $query .= " AND s.end_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND s.end_date >= CURDATE()";
    }
}

if ($f_search !== '') {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$f_search%";
    $params[] = "%$f_search%";
}

// Tris
$orderBy = match ($f_sort) {
    'name' => "u.name ASC",
    'newest' => "s.start_date DESC",
    'exp_soon' => "s.end_date ASC",
    default => "s.end_date ASC"
};

$query .= " ORDER BY " . $orderBy;

$stmt = $db->prepare($query);
$stmt->execute($params);
$subs = $stmt->fetchAll();

// Statistiques de revenus
$stats_query = "SELECT currency, SUM(amount) as total FROM subscriptions WHERE status = 'active' GROUP BY currency";
$stats_result = $db->query($stats_query)->fetchAll();

$rev_usd = 0;
$rev_cdf = 0;
foreach ($stats_result as $row) {
    if ($row['currency'] === 'USD') $rev_usd = $row['total'];
    if ($row['currency'] === 'CDF') $rev_cdf = $row['total'];
}

// Taux de change placeholder (à ajuster si nécessaire)
$exchange_rate = 2800; 
$total_in_usd = $rev_usd + ($rev_cdf / $exchange_rate);
$total_in_cdf = $rev_cdf + ($rev_usd * $exchange_rate);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMA HUB - Gestion Abonnements</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/jpeg" href="../../asset/placeholder.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin-shared.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c !important; color: #fff; min-height: 100vh; margin: 0; overflow-x: hidden; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; display: flex; flex-direction: column; padding: 2rem 1.5rem; }
        .main-content { margin-left: 280px; padding: 2rem; min-height: 100vh; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; font-size: 0.9rem; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; transform: translateX(5px); }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 1.5rem; padding: 2rem; }
        .admin-table { width: 100%; border-collapse: separate; border-spacing: 0 0.5rem; }
        .admin-table th { padding: 1rem; color: rgba(255, 255, 255, 0.4); font-size: 0.75rem; text-transform: uppercase; font-weight: 800; letter-spacing: 1px; }
        .admin-table tr td { padding: 1.25rem 1rem; background: rgba(255, 255, 255, 0.02); border-top: 1px solid rgba(255, 255, 255, 0.05); border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .admin-table tr td:first-child { border-left: 1px solid rgba(255, 255, 255, 0.05); border-radius: 1rem 0 0 1rem; }
        .admin-table tr td:last-child { border-right: 1px solid rgba(255, 255, 255, 0.05); border-radius: 0 1rem 1rem 0; }
        #wma-global-loader { position: fixed; inset: 0; background: #0a0a0c; display: flex; align-items: center; justify-content: center; z-index: 100000; transition: opacity 0.5s ease; }
        .loader-spin { width: 40px; height: 40px; border: 3px solid rgba(255, 102, 0, 0.1); border-top-color: #ff6600; border-radius: 50%; animation: wma-spin 1s linear infinite; }
        @keyframes wma-spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div id="wma-global-loader"><div class="loader-spin"></div></div>
    <div class="bg-glow"></div>

    <aside class="sidebar">
        <div class="flex items-center gap-4 mb-12 px-2">
            <img src="../../asset/trans.png" alt="Logo" class="h-10">
            <div>
                <h1 class="text-xl font-black bg-gradient-to-r from-orange-500 to-orange-300 bg-clip-text text-transparent tracking-tighter leading-tight">WMA HUB</h1>
                <p class="text-[8px] text-gray-500 font-bold uppercase tracking-[1px] -mt-1">We move, WMAFam</p>
            </div>
        </div>
        <nav class="flex-1">
            <a href="index.php" class="nav-link"><i class="fas fa-layer-group"></i> Gestion Projets</a>
            <a href="subscriptions.php" class="nav-link active"><i class="fas fa-crown"></i> Abonnements</a>
            <a href="revenues.php" class="nav-link"><i class="fas fa-wallet"></i> Revenus Gérés</a>
            <a href="employees.php" class="nav-link"><i class="fas fa-users-cog"></i> Équipe & Staff</a>
            <a href="tasks.php" class="nav-link"><i class="fas fa-tasks"></i> Gestion Tâches</a>
            <a href="salaries.php" class="nav-link"><i class="fas fa-money-check-alt"></i> Gestion Salaires</a>
            <a href="finance.php" class="nav-link"><i class="fas fa-chart-pie"></i> Rapports Financiers</a>
            <a href="users.php" class="nav-link"><i class="fas fa-user-friends"></i> Utilisateurs</a>
        </nav>
        <div class="mt-auto pt-6 border-t border-white/5">
            <a href="../../auth/logout.php" class="nav-link !text-red-500"><i class="fas fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="flex justify-between items-center mb-12">
            <div>
                <h2 class="text-4xl font-black tracking-tighter text-white">Gestion <span class="text-orange-500">Abonnements</span></h2>
                <p class="text-gray-400 mt-2">Suivez les revenus et le statut des artistes.</p>
            </div>
            <div class="flex gap-4">
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-600"></i>
                    <input type="text" id="subSearch" placeholder="Rechercher un artiste..." class="bg-white/5 border border-white/10 rounded-xl py-3 pl-12 pr-6 text-sm focus:border-orange-500 outline-none transition-all" value="<?= htmlspecialchars($f_search) ?>">
                </div>
            </div>
        </header>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <div class="glass-card">
                <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest mb-2">Total récolté (USD)</p>
                <p class="text-3xl font-black text-white"><?= number_format($rev_usd, 0, '.', ' ') ?> <span class="text-orange-500">$</span></p>
            </div>
            <div class="glass-card">
                <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest mb-2">Total récolté (CDF)</p>
                <p class="text-3xl font-black text-white"><?= number_format($rev_cdf, 0, '.', ' ') ?> <span class="text-orange-500">FC</span></p>
            </div>
            <div class="glass-card border-orange-500/20">
                <p class="text-[10px] text-orange-500 font-black uppercase tracking-widest mb-2 text-center">Estimation Totale (USD)</p>
                <p class="text-3xl font-black text-white text-center"><?= number_format($total_in_usd, 2, '.', ' ') ?> $</p>
                <p class="text-[8px] text-gray-600 text-center mt-2 italic">Taux: 1$ = <?= $exchange_rate ?> FC</p>
            </div>
            <div class="glass-card border-orange-500/20">
                <p class="text-[10px] text-orange-500 font-black uppercase tracking-widest mb-2 text-center">Estimation Totale (CDF)</p>
                <p class="text-3xl font-black text-white text-center"><?= number_format($total_in_cdf, 0, '.', ' ') ?> FC</p>
                <p class="text-[8px] text-gray-600 text-center mt-2 italic">Taux: 1$ = <?= $exchange_rate ?> FC</p>
            </div>
        </div>

        <!-- Table -->
        <div class="glass-card p-0 overflow-hidden">
            <div class="px-8 py-6 border-b border-white/5 flex justify-between items-center">
                <h3 class="text-lg font-bold flex items-center gap-3"><i class="fas fa-list text-orange-500"></i> Liste des abonnements</h3>
                <form method="GET" class="flex flex-wrap items-center gap-4">
                    <?php if ($f_search): ?>
                        <input type="hidden" name="search" value="<?= htmlspecialchars($f_search) ?>">
                    <?php endif; ?>
                    
                    <div class="flex flex-col gap-1">
                        <label class="text-[9px] uppercase font-black text-gray-600 ml-1">Statut</label>
                        <select name="f_status" onchange="this.form.submit()" class="bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-[10px] font-bold outline-none focus:border-orange-500 transition-all">
                            <option value="all" <?= $f_status === 'all' ? 'selected' : '' ?>>Tous</option>
                            <option value="active" <?= $f_status === 'active' ? 'selected' : '' ?>>Actifs</option>
                            <option value="expired" <?= $f_status === 'expired' ? 'selected' : '' ?>>Expirés</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-[9px] uppercase font-black text-gray-600 ml-1">Type</label>
                        <select name="f_plan" onchange="this.form.submit()" class="bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-[10px] font-bold outline-none focus:border-orange-500 transition-all">
                            <option value="all" <?= $f_plan === 'all' ? 'selected' : '' ?>>Tous les plans</option>
                            <option value="monthly" <?= $f_plan === 'monthly' ? 'selected' : '' ?>>Mensuel</option>
                            <option value="annual" <?= $f_plan === 'annual' ? 'selected' : '' ?>>Annuel</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-[9px] uppercase font-black text-gray-600 ml-1">Durée restante</label>
                        <select name="f_duration" onchange="this.form.submit()" class="bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-[10px] font-bold outline-none focus:border-orange-500 transition-all">
                            <option value="all" <?= $f_duration === 'all' ? 'selected' : '' ?>>Peu importe</option>
                            <option value="7d" <?= $f_duration === '7d' ? 'selected' : '' ?>>Moins de 7 jours</option>
                            <option value="30d" <?= $f_duration === '30d' ? 'selected' : '' ?>>Moins de 30 jours</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-[9px] uppercase font-black text-gray-600 ml-1">Trier par</label>
                        <select name="f_sort" onchange="this.form.submit()" class="bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-[10px] font-bold outline-none focus:border-orange-500 transition-all">
                            <option value="exp_soon" <?= $f_sort === 'exp_soon' ? 'selected' : '' ?>>Expiration Proche</option>
                            <option value="newest" <?= $f_sort === 'newest' ? 'selected' : '' ?>>Plus récents</option>
                            <option value="name" <?= $f_sort === 'name' ? 'selected' : '' ?>>Nom (A-Z)</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="admin-table text-left" id="subsTable">
                    <thead>
                        <tr>
                            <th class="px-8">Artiste</th>
                            <th>Plan</th>
                            <th>Prix Payé</th>
                            <th>Date Fin</th>
                            <th>Jours Restants</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subs as $sub): 
                            $end_date = new DateTime($sub['end_date']);
                            $now = new DateTime();
                            $diff = $now->diff($end_date);
                            $days_left = $end_date > $now ? $diff->days : 0;
                            $status_color = $sub['status'] === 'active' ? 'text-green-500' : 'text-red-500';
                        ?>
                            <tr>
                                <td class="px-8">
                                    <div class="font-bold text-white"><?= htmlspecialchars($sub['artist_name']) ?></div>
                                    <div class="text-[10px] text-gray-500"><?= htmlspecialchars($sub['artist_email']) ?></div>
                                </td>
                                <td class="uppercase text-xs font-black"><?= $sub['plan_type'] === 'monthly' ? 'Mensuel' : 'Annuel' ?></td>
                                <td class="font-black text-white"><?= number_format($sub['amount'], 0) ?> <span class="text-orange-500"><?= $sub['currency'] ?></span></td>
                                <td class="text-xs"><?= date('d/m/Y', strtotime($sub['end_date'])) ?></td>
                                <td>
                                    <?php if ($days_left > 0): ?>
                                        <div class="flex items-center gap-2">
                                            <div class="w-24 h-1.5 bg-white/5 rounded-full overflow-hidden">
                                                <div class="h-full bg-orange-500" style="width: <?= min(100, ($days_left / ($sub['plan_type'] === 'monthly' ? 30 : 365)) * 100) ?>%"></div>
                                            </div>
                                            <span class="text-xs font-bold"><?= $days_left ?>j</span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-xs text-red-500 font-bold">Terminé</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="text-[10px] font-black tracking-widest uppercase <?= $status_color ?>">
                                        <i class="fas fa-circle text-[6px] mr-2"></i><?= $sub['status'] === 'active' ? 'Actif' : 'Expiré' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($subs)): ?>
                            <tr><td colspan="6" class="text-center py-12 text-gray-500 italic">Aucun abonnement trouvé.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        window.addEventListener('load', () => {
            const loader = document.getElementById('wma-global-loader');
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(() => loader.style.display = 'none', 500);
            }
        });

        const searchInput = document.getElementById('subSearch');
        if (searchInput) {
            searchInput.addEventListener('keyup', function(e) {
                if (e.key === 'Enter') {
                    const url = new URL(window.location.href);
                    url.searchParams.set('search', this.value);
                    window.location.href = url.href;
                    return;
                }
                const value = this.value.toLowerCase();
                document.querySelectorAll('#subsTable tbody tr').forEach(row => {
                    row.style.display = row.innerText.toLowerCase().indexOf(value) > -1 ? '' : 'none';
                });
            });
        }
    </script>
</body>
</html>
