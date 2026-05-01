<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint au superadmin uniquement
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = getDBConnection();

// --- 1. Revenus des Abonnements (Artistes) ---
$stmt_subs = $db->prepare("SELECT SUM(amount) as total, currency, plan_type FROM payments WHERE status = 'success' AND payment_type = 'subscription' GROUP BY currency, plan_type");
$stmt_subs->execute();
$subs_raw = $stmt_subs->fetchAll();
$subs_total = ['USD' => 0, 'CDF' => 0];
$subs_by_plan = ['monthly' => ['USD' => 0, 'CDF' => 0], 'annual' => ['USD' => 0, 'CDF' => 0]];
foreach ($subs_raw as $row) {
    if (isset($subs_total[$row['currency']])) {
        $subs_total[$row['currency']] += $row['total'];
    }
    if (isset($row['plan_type']) && isset($subs_by_plan[$row['plan_type']][$row['currency']])) {
        $subs_by_plan[$row['plan_type']][$row['currency']] += $row['total'];
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

$pageTitle = 'Master Revenue Analysis - WMA Hub';
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
        body { font-family: 'Poppins', sans-serif; background: #050507; color: #fff; min-height: 100vh; overflow-x: hidden; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 50% 50%, #10101a 0%, #050507 100%); z-index: -1; }
        .sidebar { width: 300px; background: rgba(255, 255, 255, 0.01); backdrop-filter: blur(30px); border-right: 1px solid rgba(255, 255, 255, 0.03); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; padding: 2.5rem 2rem; display: flex; flex-direction: column; }
        .main-content { margin-left: 300px; padding: 4rem; }
        .nav-link { display: flex; align-items: center; gap: 1.25rem; padding: 1.15rem 1.5rem; color: rgba(255, 255, 255, 0.3); border-radius: 1.25rem; font-weight: 500; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); margin-bottom: 0.75rem; text-decoration: none; font-size: 0.95rem; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 215, 0, 0.05); color: #ffd700; transform: translateX(8px); }
        .nav-link.active { border-right: 3px solid #ffd700; border-radius: 1.25rem 0 0 1.25rem; }
        .glass-card { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 2.5rem; padding: 2.5rem; }
        .stat-card { background: linear-gradient(135deg, rgba(255, 255, 255, 0.03) 0%, rgba(255, 255, 255, 0.01) 100%); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 2rem; padding: 2.5rem; }
        
        #wma-global-loader { position: fixed; inset: 0; background: #050507; display: flex; align-items: center; justify-content: center; z-index: 100000; transition: opacity 0.6s ease; }
        .loader-spin { width: 50px; height: 50px; border: 3px solid rgba(255, 215, 0, 0.05); border-top-color: #ffd700; border-radius: 50%; animation: wma-spin 1s cubic-bezier(0.4, 0, 0.2, 1) infinite; }
        @keyframes wma-spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div id="wma-global-loader"><div class="loader-spin"></div></div>
    <div class="bg-glow"></div>

    <aside class="sidebar">
        <div class="flex items-center gap-4 mb-20 px-2">
            <img src="../../asset/trans.png" alt="Logo" class="h-12">
            <div>
                <h1 class="text-2xl font-black bg-gradient-to-r from-yellow-400 to-yellow-200 bg-clip-text text-transparent tracking-tighter leading-tight">SUPERADMIN</h1>
                <p class="text-[9px] text-gray-500 font-bold uppercase tracking-[2px] -mt-1">Master Control</p>
            </div>
        </div>
        
        <nav class="flex-1">
            <a href="index.php" class="nav-link"><i class="fas fa-th-large"></i> Vue d'ensemble</a>
            <a href="revenues.php" class="nav-link active"><i class="fas fa-wallet"></i> Analyse Revenus</a>
            <a href="payments.php" class="nav-link"><i class="fas fa-history"></i> Historique Paiements</a>
            <a href="payment_logs.php" class="nav-link"><i class="fas fa-file-alt"></i> Logs Paiement</a>
            <a href="artists.php" class="nav-link"><i class="fas fa-microphone-alt"></i> Artistes</a>
            <a href="distributors.php" class="nav-link"><i class="fas fa-truck-loading"></i> Distributeurs</a>
            <a href="admins.php" class="nav-link"><i class="fas fa-user-shield"></i> Gestion des Admins</a>
            <a href="settings.php" class="nav-link"><i class="fas fa-cogs"></i> Paramètres</a>
            <a href="../admin/index.php" class="nav-link"><i class="fas fa-arrow-left"></i> Retour au Panel Admin</a>
        </nav>

        <div class="mt-auto pt-8 border-t border-white/5">
            <a href="../../auth/logout.php" class="nav-link !text-red-500 hover:!bg-red-500/5"><i class="fas fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="mb-16">
            <h2 class="text-6xl font-black tracking-tighter leading-none">Master <span class="text-yellow-500">Revenue</span></h2>
            <p class="text-gray-500 mt-4 text-xl">Analyse globale et consolidée des flux financiers entrants.</p>
        </header>

        <!-- Total Général -->
        <div class="stat-card mb-12 border-yellow-500/20 bg-yellow-500/5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-black text-yellow-500 uppercase tracking-widest mb-3">Chiffre d'Affaires Total (Estimation)</p>
                    <h3 class="text-7xl font-black tracking-tighter"><?= number_format($total_general_usd, 2) ?> <span class="text-3xl text-yellow-500">USD</span></h3>
                    <p class="text-[10px] text-gray-600 mt-2 font-bold uppercase">Taux de conversion appliqué: 1 USD = <?= number_format($exchange_rate, 0, '.', ' ') ?> FC</p>
                </div>
                <div class="w-24 h-24 bg-yellow-500/10 rounded-full flex items-center justify-center text-yellow-500 text-5xl">
                    <i class="fas fa-coins"></i>
                </div>
            </div>
        </div>

        <!-- Breakdown Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
            <!-- Abonnements Artistes -->
            <div class="stat-card">
                <div class="w-14 h-14 bg-blue-500/10 rounded-2xl flex items-center justify-center text-blue-500 text-2xl mb-8">
                    <i class="fas fa-user-check"></i>
                </div>
                <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest mb-3">Abonnements Artistes</p>
                <div class="space-y-2">
                    <p class="text-4xl font-black"><?= number_format($subs_total['USD'], 2) ?> <span class="text-lg text-blue-500">$</span></p>
                    <p class="text-2xl font-bold text-gray-400"><?= number_format($subs_total['CDF'], 0, '.', ' ') ?> <span class="text-sm">FC</span></p>
                </div>
                <div class="mt-8 pt-6 border-t border-white/5 grid grid-cols-2 gap-4 text-[10px] font-bold uppercase tracking-tighter">
                    <div>
                        <p class="text-gray-600 mb-1">Mensuel</p>
                        <p class="text-blue-500"><?= number_format($subs_by_plan['monthly']['USD'], 2) ?>$ / <?= number_format($subs_by_plan['monthly']['CDF'], 0, '.', ' ') ?> FC</p>
                    </div>
                    <div>
                        <p class="text-gray-600 mb-1">Annuel</p>
                        <p class="text-green-500"><?= number_format($subs_by_plan['annual']['USD'], 2) ?>$ / <?= number_format($subs_by_plan['annual']['CDF'], 0, '.', ' ') ?> FC</p>
                    </div>
                </div>
            </div>

            <!-- Packs Promotionnels -->
            <div class="stat-card">
                <div class="w-14 h-14 bg-purple-500/10 rounded-2xl flex items-center justify-center text-purple-500 text-2xl mb-8">
                    <i class="fas fa-rocket"></i>
                </div>
                <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest mb-3">Packs Promotionnels</p>
                <div class="space-y-2">
                    <p class="text-4xl font-black"><?= number_format($packs_total_usd, 2) ?> <span class="text-lg text-purple-500">$</span></p>
                </div>
                <div class="mt-8 pt-6 border-t border-white/5 space-y-3">
                    <div class="flex justify-between items-center text-[10px] font-bold uppercase">
                        <span class="text-gray-500">Starter (<?= $prices['Starter'] ?>$)</span>
                        <span class="px-3 py-1 bg-white/5 rounded-full"><?= $packs_breakdown['Starter'] ?> ventes</span>
                    </div>
                    <div class="flex justify-between items-center text-[10px] font-bold uppercase">
                        <span class="text-gray-500">Pro (<?= $prices['Pro'] ?>$)</span>
                        <span class="px-3 py-1 bg-white/5 rounded-full"><?= $packs_breakdown['Pro'] ?> ventes</span>
                    </div>
                    <div class="flex justify-between items-center text-[10px] font-bold uppercase">
                        <span class="text-gray-500">Premium (<?= $prices['Premium'] ?>$)</span>
                        <span class="px-3 py-1 bg-white/5 rounded-full"><?= $packs_breakdown['Premium'] ?> ventes</span>
                    </div>
                </div>
            </div>

            <!-- Certifications -->
            <div class="stat-card">
                <div class="w-14 h-14 bg-cyan-500/10 rounded-2xl flex items-center justify-center text-cyan-500 text-2xl mb-8">
                    <i class="fas fa-check-circle"></i>
                </div>
                <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest mb-3">Certifications Distributeurs</p>
                <div class="space-y-2">
                    <p class="text-4xl font-black"><?= number_format($certs_total['USD'], 2) ?> <span class="text-lg text-cyan-500">$</span></p>
                    <p class="text-2xl font-bold text-gray-400"><?= number_format($certs_total['CDF'], 0, '.', ' ') ?> <span class="text-sm">FC</span></p>
                </div>
                <div class="mt-8 pt-6 border-t border-white/5">
                    <p class="text-[9px] text-gray-600 uppercase font-bold tracking-tight">Badge de confiance mensuel</p>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <section class="glass-card">
            <h3 class="text-2xl font-bold mb-8 flex items-center gap-4">
                <i class="fas fa-clock text-yellow-500"></i>
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
                        $recent = $db->query("SELECT p.*, u.name as user_name FROM payments p JOIN users u ON p.user_id = u.id WHERE p.status = 'success' ORDER BY p.created_at DESC LIMIT 15")->fetchAll();
                        foreach ($recent as $r):
                        ?>
                            <tr class="group hover:bg-white/5 transition-all">
                                <td class="py-5 text-xs text-gray-500 font-mono"><?= date('Y-m-d H:i', strtotime($r['created_at'])) ?></td>
                                <td class="py-5">
                                    <span class="text-[9px] font-black uppercase px-3 py-1 rounded-full bg-white/5 border border-white/10">
                                        <?= htmlspecialchars($r['payment_type']) ?>
                                    </span>
                                </td>
                                <td class="py-5 font-bold"><?= htmlspecialchars($r['user_name']) ?></td>
                                <td class="py-5 font-black text-yellow-500"><?= number_format($r['amount'], 2) ?> <?= $r['currency'] ?></td>
                                <td class="py-5 text-[10px] text-gray-700 font-mono"><?= $r['reference'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        window.addEventListener('load', () => {
            const loader = document.getElementById('wma-global-loader');
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(() => loader.style.display = 'none', 600);
            }
        });
    </script>
</body>
</html>
