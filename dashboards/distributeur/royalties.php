<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint aux distributeurs
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'distributeur') {
    header('Location: ../../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../includes/subscription_check.php';
// Vérifier l'abonnement
if (!hasActiveSubscription($_SESSION['user_id'])) {
    header('Location: ../../auth/subscription.php');
    exit;
}

$db = getDBConnection();
$userId = $_SESSION['user_id'];

// Récupérer les infos du distributeur
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Récupérer le total des revenus
$stmt_total = $db->prepare("SELECT SUM(revenue) FROM projects WHERE user_id = ?");
$stmt_total->execute([$userId]);
$total_royalties = $stmt_total->fetchColumn() ?: 0;

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
    $available_balance = $total_royalties - $withdrawn_amount;

    if ($amount <= 0) {
        $withdrawal_error = "Le montant doit être supérieur à 0.";
    } elseif ($amount > $available_balance) {
        $withdrawal_error = "Solde insuffisant.";
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO withdrawals (user_id, amount, method, account_details) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $amount, $method, $details]);
            $withdrawal_success = "Demande de retrait envoyée avec succès !";
            // Refresh counts
            $stmt_withdrawn->execute([$userId]);
            $withdrawn_amount = $stmt_withdrawn->fetchColumn() ?: 0;
        } catch (Exception $e) {
            $withdrawal_error = "Erreur : " . $e->getMessage();
        }
    }
}

// Recalculer balance
$stmt_paid = $db->prepare("SELECT SUM(amount) FROM withdrawals WHERE user_id = ? AND status = 'approved'");
$stmt_paid->execute([$userId]);
$total_paid = $stmt_paid->fetchColumn() ?: 0;

$stmt_pending = $db->prepare("SELECT SUM(amount) FROM withdrawals WHERE user_id = ? AND status = 'pending'");
$stmt_pending->execute([$userId]);
$total_pending = $stmt_pending->fetchColumn() ?: 0;

$current_balance = $total_royalties - $total_paid;
$available_balance = $current_balance - $total_pending;

// Historique des retraits
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

// Revenus par artiste
$query_artists = "SELECT artist_name, SUM(revenue) as artist_revenue, COUNT(*) as project_count 
                 FROM projects 
                 WHERE user_id = ? 
                 GROUP BY artist_name 
                 ORDER BY artist_revenue DESC";
$stmt_artists = $db->prepare($query_artists);
$stmt_artists->execute([$userId]);
$artist_royalties = $stmt_artists->fetchAll();

// Revenus par projet (les 10 plus rentables)
$query_top = "SELECT title, artist_name, revenue, streams, cover_file 
              FROM projects 
              WHERE user_id = ? 
              ORDER BY revenue DESC LIMIT 10";
$stmt_top = $db->prepare($query_top);
$stmt_top->execute([$userId]);
$top_projects = $stmt_top->fetchAll();

$pageTitle = 'Gestion des Royalties - WMA Hub';
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
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; min-height: 100vh; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 100% 100%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 1.5rem; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; padding: 2rem 1.5rem; display: flex; flex-direction: column; }
        .main-content { margin-left: 280px; padding: 2rem; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; transform: translateX(5px); }
        .stat-card { border: 1px solid rgba(255, 255, 255, 0.05); padding: 1.5rem; border-radius: 1.5rem; background: rgba(255, 255, 255, 0.02); }
    </style>
</head>
<body>
    <div class="bg-glow"></div>

    <aside class="sidebar">
        <div class="flex items-center gap-4 mb-12 px-2">
            <img src="../../asset/trans.png" alt="Logo" class="h-10">
            <div>
                <h1 class="text-xl font-black bg-gradient-to-r from-orange-500 to-orange-300 bg-clip-text text-transparent tracking-tighter leading-tight">WMA HUB</h1>
                <p class="text-[8px] text-gray-500 font-bold uppercase tracking-[1px] -mt-1 flex items-center gap-1">
                    Distributeur
                    <?php if ($user['is_certified']): ?>
                        <i class="fas fa-check-decagram text-cyan-400 text-[10px]"></i>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <nav class="flex-1">
            <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>"><i class="fas fa-th-large"></i> Vue d'ensemble</a>
            <a href="artists.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'artists.php' ? 'active' : '' ?>"><i class="fas fa-users"></i> Mes Artistes</a>
            <a href="catalogue.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'catalogue.php' ? 'active' : '' ?>"><i class="fas fa-compact-disc"></i> Mon Catalogue</a>
            <a href="distributed_projects.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'distributed_projects.php' ? 'active' : '' ?>"><i class="fas fa-check-circle"></i> Projets Distribués</a>
            <a href="submit.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'submit.php' ? 'active' : '' ?>"><i class="fas fa-upload"></i> Distribuer</a>
            <a href="service_card.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'service_card.php' ? 'active' : '' ?>"><i class="fas fa-id-card"></i> Ma Carte Service</a>
            <a href="royalties.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'royalties.php' ? 'active' : '' ?>"><i class="fas fa-wallet"></i> Royalties</a>
            <a href="profile.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>"><i class="fas fa-user-circle"></i> Mon Profil</a>
        </nav>
        <div class="mt-auto pt-6 border-t border-white/5">
            <a href="../../auth/logout.php" class="nav-link !text-red-500"><i class="fas fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="flex justify-between items-end mb-12">
            <div>
                <h2 class="text-4xl font-black tracking-tighter">Gestion <span class="text-orange-500">Royalties</span></h2>
                <p class="text-gray-400 mt-2">Analysez les revenus générés par vos artistes et projets.</p>
            </div>
            <div class="flex flex-col md:flex-row gap-4 items-end">
                <div class="glass-card px-8 py-4 border-orange-500/10">
                    <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest mb-1">Solde Actuel (Net)</p>
                    <p class="text-3xl font-black"><?= number_format($current_balance, 2, '.', ' ') ?> <span class="text-xs text-orange-500">$</span></p>
                </div>
                <div class="glass-card px-8 py-4 border-green-500/20 bg-green-500/5">
                    <p class="text-[10px] text-green-500 font-bold uppercase tracking-widest mb-1">Solde Retirable</p>
                    <p class="text-3xl font-black"><?= number_format($available_balance, 2, '.', ' ') ?> <span class="text-xs text-green-500">$</span></p>
                </div>
                <?php if ($can_withdraw): ?>
                    <button onclick="document.getElementById('withdrawalModal').classList.remove('hidden')" class="bg-orange-500 hover:bg-orange-600 text-white font-black px-8 py-4 rounded-2xl transition-all shadow-lg shadow-orange-500/20 flex items-center justify-center gap-3 h-[68px]">
                        <i class="fas fa-money-bill-wave"></i>
                        RETIRER
                    </button>
                <?php else: ?>
                    <div class="glass-card px-6 py-4 border-white/5 opacity-80 text-center flex flex-col justify-center h-[68px]">
                        <p class="text-[8px] text-gray-500 font-extrabold uppercase tracking-widest mb-1">Prochain retrait possible</p>
                        <p class="text-orange-500 font-bold"><?= $next_withdrawal_date ? $next_withdrawal_date->format('d/m/Y') : '-' ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Breakdown by Artist -->
            <div class="lg:col-span-1 space-y-6">
                <h3 class="text-xl font-bold flex items-center gap-3"><i class="fas fa-user-friends text-orange-500"></i> Par Artiste</h3>
                <div class="space-y-4">
                    <?php foreach ($artist_royalties as $a): ?>
                        <div class="glass-card p-6 flex justify-between items-center hover:border-white/10 transition-all">
                            <div>
                                <h4 class="font-bold text-white"><?= htmlspecialchars($a['artist_name']) ?></h4>
                                <p class="text-[10px] text-gray-500 uppercase"><?= $a['project_count'] ?> Projets</p>
                            </div>
                            <div class="text-right">
                                <p class="font-black text-orange-500"><?= number_format($a['artist_revenue'], 2, '.', ' ') ?> $</p>
                                <p class="text-[8px] text-gray-600 font-bold uppercase">Solde Partagé</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($artist_royalties)): ?>
                        <div class="glass-card p-12 text-center text-gray-500 italic">Aucune donnée disponible.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top Performing Projects -->
            <div class="lg:col-span-2 space-y-6">
                <h3 class="text-xl font-bold flex items-center gap-3"><i class="fas fa-trophy text-orange-500"></i> Top Projets (Revenus)</h3>
                <div class="glass-card overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-white/5">
                            <tr>
                                <th class="p-4 text-[10px] text-gray-500 font-bold uppercase">Projet</th>
                                <th class="p-4 text-[10px] text-gray-500 font-bold uppercase">Streams</th>
                                <th class="p-4 text-[10px] text-gray-500 font-bold uppercase text-right">Revenus</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php foreach ($top_projects as $p): ?>
                                <tr class="hover:bg-white/2 transition-colors">
                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg overflow-hidden bg-gray-800 border border-white/5">
                                                <?php if ($p['cover_file']): ?>
                                                    <img src="../artiste/uploads/<?= $p['cover_file'] ?>" class="w-full h-full object-cover">
                                                <?php else: ?>
                                                    <div class="w-full h-full flex items-center justify-center text-gray-600"><i class="fas fa-music text-xs"></i></div>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-white leading-tight"><?= htmlspecialchars($p['title']) ?></p>
                                                <p class="text-[10px] text-gray-500 font-medium"><?= htmlspecialchars($p['artist_name']) ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <p class="text-sm font-medium text-gray-400"><?= number_format($p['streams'], 0, '.', ' ') ?></p>
                                    </td>
                                    <td class="p-4 text-right">
                                        <p class="text-sm font-black text-white"><?= number_format($p['revenue'], 2, '.', ' ') ?> $</p>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Withdrawal History -->
            <div class="lg:col-span-3 space-y-6">
                 <?php if ($withdrawal_error): ?>
                    <div class="p-6 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-500 flex items-center gap-4">
                        <i class="fas fa-exclamation-circle text-2xl"></i>
                        <p class="font-bold"><?= $withdrawal_error ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($withdrawal_success): ?>
                    <div class="p-6 rounded-2xl bg-green-500/10 border border-green-500/20 text-green-500 flex items-center gap-4">
                        <i class="fas fa-check-circle text-2xl"></i>
                        <p class="font-bold"><?= $withdrawal_success ?></p>
                    </div>
                <?php endif; ?>

                <h3 class="text-xl font-bold flex items-center gap-3"><i class="fas fa-history text-orange-500"></i> Historique des Retraits</h3>
                <div class="glass-card overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-white/5">
                            <tr class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">
                                <th class="p-6">Date</th>
                                <th class="p-6">Méthode</th>
                                <th class="p-6">Montant ($)</th>
                                <th class="p-6 text-center">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php foreach ($withdrawal_history as $w): ?>
                                <tr class="hover:bg-white/[0.02] transition-colors">
                                    <td class="p-6 text-xs text-gray-400"><?= date('d/m/Y H:i', strtotime($w['created_at'])) ?></td>
                                    <td class="p-6">
                                        <p class="font-bold text-white"><?= htmlspecialchars($w['method']) ?></p>
                                        <p class="text-[10px] text-gray-500 truncate max-w-[200px]"><?= htmlspecialchars($w['account_details']) ?></p>
                                    </td>
                                    <td class="p-6 font-black text-white"><?= number_format($w['amount'], 2, '.', ' ') ?> $</td>
                                    <td class="p-6 text-center">
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
                                            <a href="../admin/uploads/payouts/<?= $w['proof_file'] ?>" target="_blank" class="block text-[8px] text-cyan-400 mt-2 underline uppercase font-bold tracking-widest"><i class="fas fa-file-invoice-dollar mr-1"></i>Voir Preuve</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($withdrawal_history)): ?>
                                <tr><td colspan="4" class="p-12 text-center text-gray-600 font-bold uppercase text-[10px]">Aucun retrait enregistré</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Retrait -->
    <div id="withdrawalModal" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-md" onclick="document.getElementById('withdrawalModal').classList.add('hidden')"></div>
        <div class="glass-card max-w-md w-full relative z-[210] p-10 border-orange-500/20">
            <h3 class="text-2xl font-black mb-2">Demander un <span class="text-orange-500">Retrait</span></h3>
            <p class="text-gray-400 text-sm mb-8">Vos revenus seront transférés après validation de l'admin.</p>
            
            <form method="POST" class="space-y-6">
                <input type="hidden" name="request_withdrawal" value="1">
                <div>
                    <label class="text-[10px] font-black uppercase text-gray-500 block mb-2">Montant à retirer ($)</label>
                    <input type="number" step="0.01" name="amount" required max="<?= $available_balance ?>" class="w-full bg-white/5 border border-white/10 rounded-xl px-5 py-3 outline-none focus:border-orange-500 transition-all text-xl font-black" placeholder="0.00">
                    <p class="text-[10px] text-gray-600 mt-2">Maximum disponible: <span class="text-green-500"><?= number_format($available_balance, 2) ?> $</span></p>
                </div>

                <div>
                    <label class="text-[10px] font-black uppercase text-gray-500 block mb-2">Mode de paiement</label>
                    <select name="method" required class="w-full bg-white/5 border border-white/10 rounded-xl px-5 py-3 outline-none focus:border-orange-500 transition-all">
                        <option value="M-Pesa">M-Pesa</option>
                        <option value="Airtel Money">Airtel Money</option>
                        <option value="Orange Money">Orange Money</option>
                        <option value="Bank">Virement Bancaire</option>
                        <option value="PayPal">PayPal</option>
                    </select>
                </div>

                <div>
                    <label class="text-[10px] font-black uppercase text-gray-500 block mb-2">Détails du compte (Numéro de compte, n° Mobile Money, etc.)</label>
                    <textarea name="account_details" required class="w-full bg-white/5 border border-white/10 rounded-xl px-5 py-3 outline-none focus:border-orange-500 transition-all h-24" placeholder="Entrez ici votre numéro de téléphone ou RIB bancaire..."></textarea>
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="button" onclick="document.getElementById('withdrawalModal').classList.add('hidden')" class="flex-1 bg-white/5 hover:bg-white/10 text-white font-bold py-4 rounded-xl transition-all">Annuler</button>
                    <button type="submit" class="flex-1 bg-orange-500 hover:bg-orange-600 text-white font-bold py-4 rounded-xl transition-all shadow-lg shadow-orange-500/20">Confirmer</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
