<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint à l'admin uniquement
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = getDBConnection();

// --- 1. Revenus des Abonnements (Artistes) ---
$stmt_subs = $db->prepare("SELECT SUM(amount) as total, currency FROM payments WHERE status = 'success' AND payment_type = 'subscription' GROUP BY currency");
$stmt_subs->execute();
$subs_raw = $stmt_subs->fetchAll();
$subs_total = ['USD' => 0, 'CDF' => 0];
foreach ($subs_raw as $row) {
    if (isset($subs_total[$row['currency']])) {
        $subs_total[$row['currency']] = $row['total'];
    }
}

// --- 2. Revenus des Certifications (Distributeurs) ---
$stmt_certs = $db->prepare("SELECT SUM(amount) as total, currency FROM payments WHERE status = 'success' AND payment_type = 'certification' GROUP BY currency");
$stmt_certs->execute();
$certs_raw = $stmt_certs->fetchAll();
$certs_total = ['USD' => 0, 'CDF' => 0];
foreach ($certs_raw as $row) {
    if (isset($certs_total[$row['currency']])) {
        $certs_total[$row['currency']] = $row['total'];
    }
}

// --- 3. Revenus des Packs Promo (Projets) ---
$stmt_packs = $db->query("SELECT promo_pack, COUNT(*) as count FROM projects WHERE payment_status = 'paye' AND promo_pack IN ('Starter', 'Pro', 'Premium') GROUP BY promo_pack");
$packs_raw = $stmt_packs->fetchAll();
$packs_total_usd = 0;
$packs_breakdown = ['Starter' => 0, 'Pro' => 0, 'Premium' => 0];

$prices = [
    'Starter' => (float)getSetting('pack_starter_usd', 15),
    'Pro' => (float)getSetting('pack_pro_usd', 35),
    'Premium' => (float)getSetting('pack_premium_usd', 75)
];

foreach ($packs_raw as $row) {
    $price = $prices[$row['promo_pack']] ?? 0;
    $packs_total_usd += ($price * $row['count']);
    $packs_breakdown[$row['promo_pack']] = $row['count'];
}

// --- 4. Calcul du Total Général ---
$exchange_rate = (float)getSetting('exchange_rate', 2800);
$total_general_usd = $subs_total['USD'] + $certs_total['USD'] + $packs_total_usd + ($subs_total['CDF'] / $exchange_rate) + ($certs_total['CDF'] / $exchange_rate);

$pageTitle = 'Tableau des Revenus - WMA Hub';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/jpeg" href="../../asset/placeholder.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin-shared.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; min-height: 100vh; margin: 0; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; padding: 2rem 1.5rem; display: flex; flex-direction: column; }
        .main-content { margin-left: 280px; padding: 2rem; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; transform: translateX(5px); }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 2rem; padding: 2rem; }
        .stat-card { background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.01) 100%); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 1.5rem; padding: 2rem; box-shadow: 0 20px 50px -15px rgba(0, 0, 0, 0.5); }
    </style>
</head>
<body>
    <div class="bg-glow"></div>

    <aside class="sidebar">
        <div class="flex items-center gap-4 mb-12 px-2">
            <img src="../../asset/trans.png" alt="Logo" class="h-10">
            <div>
                <h1 class="text-xl font-black bg-gradient-to-r from-orange-500 to-orange-300 bg-clip-text text-transparent tracking-tighter leading-tight">WMA HUB</h1>
                <p class="text-[8px] text-gray-500 font-bold uppercase tracking-[1px] -mt-1">Administration</p>
            </div>
        </div>
        <nav class="flex-1">
            <a href="index.php" class="nav-link"><i class="fas fa-layer-group"></i> Gestion Projets</a>
            <a href="subscriptions.php" class="nav-link"><i class="fas fa-crown"></i> Abonnements</a>
            <a href="revenues.php" class="nav-link active"><i class="fas fa-wallet"></i> Revenus Gérés</a>
            <a href="employees.php" class="nav-link"><i class="fas fa-users-cog"></i> Équipe & Staff</a>
            <a href="tasks.php" class="nav-link"><i class="fas fa-tasks"></i> Gestion Tâches</a>
            <a href="salaries.php" class="nav-link"><i class="fas fa-money-check-alt"></i> Gestion Salaires</a>
            <a href="finance.php" class="nav-link"><i class="fas fa-chart-pie"></i> Rapports Financiers</a>
        </nav>
        <div class="mt-auto pt-6 border-t border-white/5">
            <a href="../../auth/logout.php" class="nav-link !text-red-500"><i class="fas fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="mb-12">
            <h2 class="text-4xl font-black tracking-tighter">Analyse des <span class="text-orange-500">Revenus</span></h2>
            <p class="text-gray-400 mt-2">Vue d'ensemble consolidée des flux monétaires du système.</p>
        </header>

        <!-- General Total Card -->
        <div class="stat-card mb-8 border-orange-500/30 bg-orange-500/5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-orange-500 uppercase tracking-widest mb-2">Total Général Consolidé (Est.)</p>
                    <h3 class="text-6xl font-black tracking-tighter"><?= number_format($total_general_usd, 2) ?> <span class="text-2xl text-orange-500">$</span></h3>
                </div>
                <div class="w-20 h-20 bg-orange-500/10 rounded-full flex items-center justify-center text-orange-500 text-4xl">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            <!-- Artist Subscriptions -->
            <div class="stat-card">
                <div class="w-12 h-12 bg-blue-500/10 rounded-xl flex items-center justify-center text-blue-500 text-xl mb-6">
                    <i class="fas fa-user-check"></i>
                </div>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mb-2">Abonnements Artistes</p>
                <div class="space-y-1">
                    <p class="text-3xl font-black"><?= number_format($subs_total['USD'], 2) ?> <span class="text-sm text-blue-500">$</span></p>
                    <p class="text-xl font-bold text-gray-400"><?= number_format($subs_total['CDF'], 0, '.', ' ') ?> <span class="text-xs">FC</span></p>
                </div>
                <div class="mt-6 pt-6 border-t border-white/5">
                    <p class="text-[9px] text-gray-600 uppercase font-bold tracking-tight">Part de l'accès direct</p>
                </div>
            </div>

            <!-- Promo Packs -->
            <div class="stat-card">
                <div class="w-12 h-12 bg-purple-500/10 rounded-xl flex items-center justify-center text-purple-500 text-xl mb-6">
                    <i class="fas fa-rocket"></i>
                </div>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mb-2">Packs Promotionnels</p>
                <div class="space-y-1">
                    <p class="text-3xl font-black"><?= number_format($packs_total_usd, 2) ?> <span class="text-sm text-purple-500">$</span></p>
                    <div class="flex gap-4 text-[10px] text-gray-400 font-bold uppercase mt-2">
                        <span>Starter: <?= $packs_breakdown['Starter'] ?></span>
                        <span>Pro: <?= $packs_breakdown['Pro'] ?></span>
                        <span>Premium: <?= $packs_breakdown['Premium'] ?></span>
                    </div>
                </div>
                <div class="mt-4 pt-6 border-t border-white/5">
                    <p class="text-[9px] text-gray-600 uppercase font-bold tracking-tight">Inclus dans la soumission de projets</p>
                </div>
            </div>

            <!-- Certifications -->
            <div class="stat-card">
                <div class="w-12 h-12 bg-cyan-500/10 rounded-xl flex items-center justify-center text-cyan-500 text-xl mb-6">
                    <i class="fas fa-check-decagram"></i>
                </div>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mb-2">Certifications</p>
                <div class="space-y-1">
                    <p class="text-3xl font-black"><?= number_format($certs_total['USD'], 2) ?> <span class="text-sm text-cyan-500">$</span></p>
                    <p class="text-xl font-bold text-gray-400"><?= number_format($certs_total['CDF'], 0, '.', ' ') ?> <span class="text-xs">FC</span></p>
                </div>
                <div class="mt-6 pt-6 border-t border-white/5">
                    <p class="text-[9px] text-gray-600 uppercase font-bold tracking-tight">Validation des distributeurs</p>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <section class="glass-card">
            <h3 class="text-xl font-bold mb-6 flex items-center gap-3">
                <i class="fas fa-history text-orange-500"></i>
                Dernières Transactions Réussies
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[10px] text-gray-500 uppercase tracking-widest border-b border-white/5">
                            <th class="pb-4 font-black">Date</th>
                            <th class="pb-4 font-black">Type</th>
                            <th class="pb-4 font-black">Utilisateur</th>
                            <th class="pb-4 font-black">Montant</th>
                            <th class="pb-4 font-black">Référence</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php
                        $recent = $db->query("SELECT p.*, u.name as user_name FROM payments p JOIN users u ON p.user_id = u.id WHERE p.status = 'success' ORDER BY p.created_at DESC LIMIT 10")->fetchAll();
                        foreach ($recent as $r):
                        ?>
                            <tr class="group hover:bg-white/5 transition-all">
                                <td class="py-4 text-xs text-gray-400"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                                <td class="py-4">
                                    <span class="text-[10px] font-black uppercase px-3 py-1 rounded-full bg-white/5 border border-white/10">
                                        <?= htmlspecialchars($r['payment_type']) ?>
                                    </span>
                                </td>
                                <td class="py-4 text-sm font-bold"><?= htmlspecialchars($r['user_name']) ?></td>
                                <td class="py-4 font-black text-green-500"><?= number_format($r['amount'], 2) ?> <?= $r['currency'] ?></td>
                                <td class="py-4 text-[10px] text-gray-600 font-mono"><?= $r['reference'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
