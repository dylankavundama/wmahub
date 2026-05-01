<?php
require_once __DIR__ . '/auth_artist.php';

$db = getDBConnection();
$userId = $_SESSION['user_id'];

// Récupérer le total des revenus de l'artiste
$stmt_total = $db->prepare("SELECT SUM(revenue) FROM projects WHERE user_id = ?");
$stmt_total->execute([$userId]);
$total_revenue = $stmt_total->fetchColumn() ?: 0;

// Traitement de la demande de retrait
$withdrawal_error = '';
$withdrawal_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_withdrawal'])) {
    $amount = (float)$_POST['amount'];
    $method = $_POST['method'];
    $details = $_POST['account_details'];

    // Calcul du solde actuel (Total - Retraits validés/en attente)
    $stmt_withdrawn = $db->prepare("SELECT SUM(amount) FROM withdrawals WHERE user_id = ? AND status IN ('pending', 'approved')");
    $stmt_withdrawn->execute([$userId]);
    $withdrawn_amount = $stmt_withdrawn->fetchColumn() ?: 0;
    $available_balance_check = $total_revenue - $withdrawn_amount;

    if ($amount <= 0) {
        $withdrawal_error = "Le montant doit être supérieur à 0.";
    } elseif ($amount > $available_balance_check) {
        $withdrawal_error = "Solde insuffisant.";
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO withdrawals (user_id, amount, method, account_details) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $amount, $method, $details]);
            $withdrawal_success = "Demande de retrait envoyée avec succès !";
        } catch (Exception $e) {
            $withdrawal_error = "Erreur : " . $e->getMessage();
        }
    }
}

// Recalculer les montants retirés et en attente
$stmt_paid = $db->prepare("SELECT SUM(amount) FROM withdrawals WHERE user_id = ? AND status = 'approved'");
$stmt_paid->execute([$userId]);
$total_paid = $stmt_paid->fetchColumn() ?: 0;

$stmt_pending = $db->prepare("SELECT SUM(amount) FROM withdrawals WHERE user_id = ? AND status = 'pending'");
$stmt_pending->execute([$userId]);
$total_pending = $stmt_pending->fetchColumn() ?: 0;

$current_balance = $total_revenue - $total_paid;
$available_balance = $current_balance - $total_pending;

// Récupérer l'historique des retraits
$stmt_history = $db->prepare("SELECT * FROM withdrawals WHERE user_id = ? ORDER BY created_at DESC");
$stmt_history->execute([$userId]);
$withdrawal_history = $stmt_history->fetchAll();

// Calculer si l'utilisateur peut retirer (Restriction de 2 mois / 60 jours)
$can_withdraw = true;
$next_withdrawal_date = null;
$stmt_last_withdrawal = $db->prepare("SELECT processed_at FROM withdrawals WHERE user_id = ? AND status = 'approved' ORDER BY processed_at DESC LIMIT 1");
$stmt_last_withdrawal->execute([$userId]);
$last_w = $stmt_last_withdrawal->fetch();

if ($last_w && $last_w['processed_at']) {
    $last_date = new DateTime($last_w['processed_at']);
    $now = new DateTime();
    $diff = $now->diff($last_date);
    $days = (int)$diff->format('%a');
    
    if ($days < 60) {
        $can_withdraw = false;
        $next_withdrawal_date = (clone $last_date)->add(new DateInterval('P60D'));
    }
}

// Récupérer les revenus par projet
$stmt_projects = $db->prepare("SELECT title, revenue, streams, cover_file, date_sortie, status 
                               FROM projects 
                               WHERE user_id = ? 
                               ORDER BY revenue DESC, date_sortie DESC");
$stmt_projects->execute([$userId]);
$projects = $stmt_projects->fetchAll();

$pageTitle = 'Mes Revenus - WMA Hub';
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
        .sidebar { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .sidebar.active { transform: translateX(0); }
        @media (max-width: 1024px) { .sidebar { transform: translateX(-100%); position: fixed; z-index: 50; height: 100vh; } }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px); border: 1px border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 20px; transition: all 0.3s ease; }
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: #94a3b8; transition: all 0.3s ease; margin-bottom: 4px; }
        .nav-link:hover:not(.active) { background: rgba(255, 255, 255, 0.03); color: #fff; }
        .nav-link.active { background: linear-gradient(135deg, #ff6600 0%, #ff8c00 100%); color: #fff; box-shadow: 0 4px 15px rgba(255, 102, 0, 0.3); }
        .nav-link i { font-size: 1.1rem; }
        #wma-global-loader { position: fixed; inset: 0; background: #0a0a0c; z-index: 9999; display: flex; align-items: center; justify-content: center; transition: opacity 0.5s ease; }
        .loader-spin { width: 50px; height: 50px; border: 3px solid rgba(255, 102, 0, 0.1); border-top-color: #ff6600; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .input-glass { width: 100%; background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 16px; padding: 16px; color: #fff; transition: all 0.3s ease; }
        .input-glass:focus { outline: none; border-color: #ff6600; background: rgba(255, 255, 255, 0.05); box-shadow: 0 0 20px rgba(255, 102, 0, 0.1); }
        .btn-submit { width: 100%; padding: 16px; background: linear-gradient(135deg, #ff6600 0%, #ff8c00 100%); color: #fff; border-radius: 16px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; transition: all 0.3s ease; box-shadow: 0 10px 20px rgba(255, 102, 0, 0.2); }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(255, 102, 0, 0.3); }
    </style>
</head>
<body>
    <div id="wma-global-loader">
        <div class="loader-spin"></div>
    </div>
    <div class="bg-glow"></div>

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
            <header class="flex flex-col md:flex-row md:items-end justify-between gap-8 mb-12">
                <div>
                    <h1 class="text-4xl md:text-6xl font-black text-white tracking-tighter mb-2">Mes <span class="text-orange-500">Revenus</span></h1>
                    <p class="text-gray-500 font-medium tracking-tight">Suivez la performance financière de vos sorties musicales.</p>
                </div>
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="glass-card px-8 py-6 border-orange-500/20">
                        <p class="text-[10px] text-orange-500 font-black uppercase tracking-widest mb-2">Solde Actuel (Net)</p>
                        <p class="text-4xl font-black text-white"><?= number_format($current_balance, 2, '.', ' ') ?> <span class="text-orange-500 text-2xl">$</span></p>
                    </div>
                    <div class="glass-card px-8 py-6 border-green-500/20 bg-green-500/5">
                        <p class="text-[10px] text-green-500 font-black uppercase tracking-widest mb-2">Solde Retirable</p>
                        <p class="text-4xl font-black text-white"><?= number_format($available_balance, 2, '.', ' ') ?> <span class="text-green-500 text-2xl">$</span></p>
                    </div>
                    <?php if ($can_withdraw): ?>
                        <button onclick="document.getElementById('withdrawalModal').classList.remove('hidden')" class="bg-orange-500 hover:bg-orange-600 text-white font-black px-8 py-4 rounded-2xl transition-all shadow-lg shadow-orange-500/20 flex items-center justify-center gap-3">
                            <i class="fas fa-money-bill-wave"></i>
                            RETIRER
                        </button>
                    <?php else: ?>
                        <div class="glass-card px-6 py-4 border-white/5 opacity-80 text-center flex flex-col justify-center">
                            <p class="text-[8px] text-gray-500 font-extrabold uppercase tracking-widest mb-1">Prochain retrait possible</p>
                            <p class="text-orange-500 font-bold"><?= $next_withdrawal_date ? $next_withdrawal_date->format('d/m/Y') : '-' ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </header>

            <?php if ($withdrawal_success): ?>
                <div class="bg-green-500/10 border border-green-500/20 text-green-500 p-4 rounded-xl mb-8 font-bold text-center">
                    <i class="fas fa-check-circle mr-2"></i><?= $withdrawal_success ?>
                </div>
            <?php endif; ?>

            <?php if ($withdrawal_error): ?>
                <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl mb-8 font-bold text-center">
                    <i class="fas fa-exclamation-circle mr-2"></i><?= $withdrawal_error ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 gap-12">
                <section class="glass-card overflow-hidden shadow-2xl">
                    <div class="p-8 border-b border-white/5 flex items-center justify-between bg-white/[0.02]">
                        <h3 class="text-xl font-bold flex items-center gap-3 uppercase tracking-tighter"><i class="fas fa-compact-disc text-orange-500"></i> Détails par Projet</h3>
                        <span class="text-[10px] bg-white/5 px-4 py-2 rounded-full font-black uppercase tracking-widest text-gray-500"><?= count($projects) ?> Sorties</span>
                    </div>
                    <div class="overflow-x-auto text-sm">
                        <table class="w-full text-left">
                            <thead class="bg-white/[0.01]">
                                <tr class="text-[10px] text-gray-500 uppercase tracking-widest">
                                    <th class="px-8 py-5">Projet</th>
                                    <th>Streams</th>
                                    <th class="text-right">Revenu ($)</th>
                                    <th class="px-8 text-center uppercase tracking-widest">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php foreach ($projects as $p): ?>
                                    <tr class="group hover:bg-white/[0.02] transition-colors">
                                        <td class="px-8 py-5">
                                            <div class="flex items-center gap-4">
                                                <div class="w-12 h-12 rounded-xl overflow-hidden bg-gray-800 border border-white/5 flex-shrink-0">
                                                    <?php if ($p['cover_file']): ?>
                                                        <img src="uploads/<?= htmlspecialchars($p['cover_file']) ?>" class="w-full h-full object-cover">
                                                    <?php else: ?>
                                                        <div class="w-full h-full flex items-center justify-center text-gray-600"><i class="fas fa-music"></i></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="overflow-hidden">
                                                    <p class="font-bold text-white leading-tight truncate"><?= htmlspecialchars($p['title']) ?></p>
                                                    <p class="text-[10px] text-gray-500 uppercase font-black mt-1">Sorti le <?= date('d/m/Y', strtotime($p['date_sortie'])) ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-5">
                                            <p class="font-bold text-gray-400"><?= number_format($p['streams'], 0, '.', ' ') ?></p>
                                        </td>
                                        <td class="py-5 text-right font-black text-white text-lg">
                                            <?= number_format($p['revenue'], 2, '.', ' ') ?> $
                                        </td>
                                        <td class="px-8 py-5 text-center">
                                            <span class="text-[8px] font-black uppercase px-3 py-1 rounded-full border border-white/10 bg-white/5 text-gray-500">
                                                <?= htmlspecialchars($p['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($projects)): ?>
                                    <tr>
                                        <td colspan="4" class="px-8 py-20 text-center">
                                            <i class="fas fa-cloud-moon text-4xl text-gray-800 mb-4 block"></i>
                                            <p class="text-gray-500 font-black uppercase tracking-widest text-[10px]">Aucun projet monétisé pour le moment</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="glass-card overflow-hidden shadow-2xl">
                    <div class="p-8 border-b border-white/5 flex items-center justify-between bg-white/[0.02]">
                        <h3 class="text-xl font-bold flex items-center gap-3 uppercase tracking-tighter"><i class="fas fa-history text-orange-500"></i> Historique des Retraits</h3>
                    </div>
                    <div class="overflow-x-auto text-sm">
                        <table class="w-full text-left">
                            <thead class="bg-white/[0.01]">
                                <tr class="text-[10px] text-gray-500 uppercase tracking-widest">
                                    <th class="px-8 py-5">Date</th>
                                    <th>Méthode</th>
                                    <th>Montant ($)</th>
                                    <th class="px-8 text-center uppercase tracking-widest">Statut</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php foreach ($withdrawal_history as $w): ?>
                                    <tr class="hover:bg-white/[0.02] transition-colors">
                                        <td class="px-8 py-5 text-[10px] text-gray-500 font-bold uppercase"><?= date('d/m/Y H:i', strtotime($w['created_at'])) ?></td>
                                        <td class="py-5">
                                            <p class="font-bold text-white"><?= htmlspecialchars($w['method']) ?></p>
                                            <p class="text-[10px] text-gray-500 font-medium truncate max-w-[200px]"><?= htmlspecialchars($w['account_details']) ?></p>
                                        </td>
                                        <td class="py-5 font-black text-white text-lg"><?= number_format($w['amount'], 2, '.', ' ') ?> $</td>
                                        <td class="px-8 py-5 text-center">
                                            <?php
                                            $st = $w['status'];
                                            $stClass = match($st) {
                                                'approved' => 'text-green-500 border-green-500/20 bg-green-500/5',
                                                'rejected' => 'text-red-500 border-red-500/20 bg-red-500/5',
                                                default => 'text-amber-500 border-amber-500/20 bg-amber-500/5'
                                            };
                                            $stLabel = match($st) {
                                                'approved' => 'PAYÉ',
                                                'rejected' => 'REJETÉ',
                                                default => 'EN ATTENTE'
                                            };
                                            ?>
                                            <span class="text-[8px] font-black uppercase px-3 py-1 rounded-full border <?= $stClass ?>">
                                                <?= $stLabel ?>
                                            </span>
                                            <?php if ($w['proof_file']): ?>
                                                <a href="../admin/uploads/payouts/<?= $w['proof_file'] ?>" target="_blank" class="block text-[8px] text-cyan-400 mt-2 underline uppercase font-black tracking-widest"><i class="fas fa-file-invoice-dollar mr-1"></i>Voir Preuve</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($withdrawal_history)): ?>
                                    <tr><td colspan="4" class="px-8 py-10 text-center text-gray-500 font-black uppercase text-[10px] tracking-widest">Aucun retrait effectué</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <!-- Modal Retrait -->
    <div id="withdrawalModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 hidden">
        <div class="fixed inset-0 bg-black/80 backdrop-blur-sm" onclick="document.getElementById('withdrawalModal').classList.add('hidden')"></div>
        <div class="glass-card max-w-lg w-full p-8 shadow-2xl relative z-10">
            <h3 class="text-3xl font-black mb-2 uppercase tracking-tighter">Demander un <span class="text-orange-500">Retrait</span></h3>
            <p class="text-gray-500 font-medium text-sm mb-8">Vos revenus seront transférés après validation par l'équipe WMA.</p>
            
            <form method="POST" class="space-y-6">
                <input type="hidden" name="request_withdrawal" value="1">
                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Montant à retirer ($)</label>
                    <input type="number" step="0.01" name="amount" required max="<?= $available_balance ?>" class="input-glass text-2xl font-black" placeholder="0.00">
                    <p class="text-[10px] text-gray-600 mt-2 font-bold uppercase tracking-widest">Maximum disponible: <span class="text-green-500"><?= number_format($available_balance, 2) ?> $</span></p>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Mode de paiement</label>
                    <select name="method" required class="input-glass font-bold bg-[#0d0d0f]">
                        <option value="M-Pesa">M-Pesa</option>
                        <option value="Airtel Money">Airtel Money</option>
                        <option value="Orange Money">Orange Money</option>
                        <option value="Bank">Virement Bancaire</option>
                        <option value="PayPal">PayPal</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Détails du compte</label>
                    <textarea name="account_details" required class="input-glass h-32 focus:h-48 transition-all" placeholder="Numéro de téléphone ou coordonnées bancaires..."></textarea>
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="button" onclick="document.getElementById('withdrawalModal').classList.add('hidden')" class="flex-1 px-6 py-4 bg-white/5 text-white rounded-2xl font-black uppercase tracking-widest text-[10px] hover:bg-white/10 transition-all border border-white/5">Annuler</button>
                    <button type="submit" class="btn-submit flex-1">Confirmer</button>
                </div>
            </form>
        </div>
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
