<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint au superadmin uniquement
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = getDBConnection();
$userId = $_SESSION['user_id'];

// Logique d'approbation/rejet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['emp_action'])) {
    $target_uid = (int)$_POST['target_user_id'];
    if ($_POST['emp_action'] === 'approve') {
        $db->prepare("UPDATE users SET is_active = 1 WHERE id = ?")->execute([$target_uid]);
    } elseif ($_POST['emp_action'] === 'reject') {
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$target_uid]);
    }
    header('Location: index.php');
    exit;
}

// Récupérer les informations de l'utilisateur
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Statistiques globales
$total_users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_artists = $db->query("SELECT COUNT(*) FROM users WHERE role = 'artiste'")->fetchColumn();
$total_distributors = $db->query("SELECT COUNT(*) FROM users WHERE role = 'distributeur'")->fetchColumn();
$total_admins = $db->query("SELECT COUNT(*) FROM users WHERE role IN ('admin', 'superadmin')")->fetchColumn();

// Revenus totaux (estimation simple)
$total_revenue_usd = $db->query("SELECT SUM(amount) FROM payments WHERE status = 'success' AND currency = 'USD'")->fetchColumn() ?: 0;
$total_revenue_cdf = $db->query("SELECT SUM(amount) FROM payments WHERE status = 'success' AND currency = 'CDF'")->fetchColumn() ?: 0;

// Dernières activités (logs de connexion)
$recent_logs = $db->query("SELECT l.*, u.name, u.role FROM login_logs l JOIN users u ON l.user_id = u.id ORDER BY l.login_date DESC LIMIT 10")->fetchAll();

// Employés en attente d'activation
$pending_employees = $db->query("SELECT * FROM users WHERE role = 'employe' AND is_active = 0 ORDER BY created_at DESC")->fetchAll();

$pageTitle = 'Superadmin Dashboard - WMA HUB';
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
        body { font-family: 'Poppins', sans-serif; background: #050507; color: #fff; min-height: 100vh; overflow-x: hidden; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 50% 50%, #10101a 0%, #050507 100%); z-index: -1; }
        .sidebar { width: 300px; background: rgba(255, 255, 255, 0.01); backdrop-filter: blur(30px); border-right: 1px solid rgba(255, 255, 255, 0.03); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; padding: 2.5rem 2rem; display: flex; flex-direction: column; }
        .main-content { margin-left: 300px; padding: 4rem; }
        .nav-link { display: flex; align-items: center; gap: 1.25rem; padding: 1.15rem 1.5rem; color: rgba(255, 255, 255, 0.3); border-radius: 1.25rem; font-weight: 500; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); margin-bottom: 0.75rem; text-decoration: none; font-size: 0.95rem; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 215, 0, 0.05); color: #ffd700; transform: translateX(8px); }
        .nav-link.active { border-right: 3px solid #ffd700; border-radius: 1.25rem 0 0 1.25rem; }
        .glass-card { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 2.5rem; padding: 3rem; transition: all 0.4s ease; }
        .glass-card:hover { border-color: rgba(255, 215, 0, 0.1); background: rgba(255, 255, 255, 0.03); }
        .stat-icon { width: 60px; height: 60px; border-radius: 1.5rem; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 2rem; }
        .gold-glow { box-shadow: 0 0 50px -10px rgba(255, 215, 0, 0.15); }
        
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
                <p class="text-[9px] text-gray-500 font-bold uppercase tracking-[2px] -mt-1">Control Center</p>
            </div>
        </div>
        
        <nav class="flex-1">
            <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>"><i class="fas fa-th-large"></i> Vue d'ensemble</a>
            <a href="revenues.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'revenues.php' ? 'active' : '' ?>"><i class="fas fa-wallet"></i> Analyse Revenus</a>
            <a href="payments.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'payments.php' ? 'active' : '' ?>"><i class="fas fa-history"></i> Historique Paiements</a>
            <a href="artists.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'artists.php' ? 'active' : '' ?>"><i class="fas fa-microphone-alt"></i> Artistes</a>
            <a href="distributors.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'distributors.php' ? 'active' : '' ?>"><i class="fas fa-truck-loading"></i> Distributeurs</a>
            <a href="admins.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'admins.php' ? 'active' : '' ?>"><i class="fas fa-user-shield"></i> Gestion des Admins</a>
            <a href="../admin/hero_slider.php" class="nav-link"><i class="fas fa-images"></i> Gestion Slider</a>
            <a href="settings.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : '' ?>"><i class="fas fa-cogs"></i> Paramètres</a>
            <a href="../admin/index.php" class="nav-link"><i class="fas fa-arrow-left"></i> Retour au Panel Admin</a>
        </nav>

        <div class="mt-auto pt-8 border-t border-white/5">
            <div class="flex items-center gap-4 mb-8 px-2">
                <div class="w-12 h-12 rounded-2xl bg-yellow-500/10 border border-yellow-500/20 flex items-center justify-center text-yellow-500"><i class="fas fa-crown"></i></div>
                <div>
                    <p class="text-sm font-bold text-white"><?= htmlspecialchars($user['name']) ?></p>
                    <p class="text-[9px] text-yellow-500/50 uppercase font-black tracking-widest">Master Admin</p>
                </div>
            </div>
            <a href="../../auth/logout.php" class="nav-link !text-red-500 hover:!bg-red-500/5"><i class="fas fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="mb-16">
            <h2 class="text-6xl font-black tracking-tighter leading-none">Console de <span class="text-yellow-500">Contrôle</span></h2>
            <p class="text-gray-500 mt-4 text-xl">Bienvenue, Maître Administrateur. Le système est opérationnel.</p>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-16">
            <div class="glass-card gold-glow">
                <div class="stat-icon bg-yellow-500/10 text-yellow-500"><i class="fas fa-users text-3xl"></i></div>
                <p class="text-[11px] text-gray-500 font-black uppercase tracking-widest mb-2">Total Utilisateurs</p>
                <p class="text-5xl font-black"><?= $total_users ?></p>
                <div class="mt-4 flex gap-4 text-[10px] font-bold">
                    <span class="text-blue-500"><?= $total_artists ?> ART</span>
                    <span class="text-purple-500"><?= $total_distributors ?> DIST</span>
                </div>
            </div>

            <div class="glass-card">
                <div class="stat-icon bg-green-500/10 text-green-500"><i class="fas fa-dollar-sign text-3xl"></i></div>
                <p class="text-[11px] text-gray-500 font-black uppercase tracking-widest mb-2">Revenus USD</p>
                <p class="text-5xl font-black"><?= number_format($total_revenue_usd, 0, '.', ' ') ?>$</p>
                <p class="mt-4 text-[10px] text-gray-500 font-bold uppercase tracking-widest">Global success</p>
            </div>

            <div class="glass-card">
                <div class="stat-icon bg-blue-500/10 text-blue-500"><i class="fas fa-coins text-3xl"></i></div>
                <p class="text-[11px] text-gray-500 font-black uppercase tracking-widest mb-2">Revenus CDF</p>
                <p class="text-5xl font-black"><?= number_format($total_revenue_cdf, 0, '.', ' ') ?></p>
                <p class="mt-4 text-[10px] text-gray-500 font-bold uppercase tracking-widest">Congolese Francs</p>
            </div>

            <div class="glass-card">
                <div class="stat-icon bg-purple-500/10 text-purple-500"><i class="fas fa-user-shield text-3xl"></i></div>
                <p class="text-[11px] text-gray-500 font-black uppercase tracking-widest mb-2">Équipe Admin</p>
                <p class="text-5xl font-black"><?= $total_admins ?></p>
                <p class="mt-4 text-[10px] text-gray-500 font-bold uppercase tracking-widest">Active Staff</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Employés en attente -->
            <div class="lg:col-span-3 glass-card">
                <h3 class="text-2xl font-bold mb-8 flex items-center justify-between">
                    <span class="flex items-center gap-3"><i class="fas fa-user-clock text-yellow-500"></i> Comptes en Attente Activation (Employés)</span>
                    <span class="bg-yellow-500/10 text-yellow-500 text-xs px-4 py-1.5 rounded-full border border-yellow-500/20 font-black uppercase tracking-widest"><?= count($pending_employees) ?> EN ATTENTE</span>
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] text-gray-500 uppercase tracking-widest border-b border-white/5">
                                <th class="pb-4">Utilisateur</th>
                                <th class="pb-4">Email</th>
                                <th class="pb-4">Inscrit le</th>
                                <th class="pb-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php if (empty($pending_employees)): ?>
                                <tr>
                                    <td colspan="4" class="py-12 text-center text-gray-500 uppercase font-black tracking-widest text-xs">Aucun employé en attente ✓</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pending_employees as $emp): ?>
                                    <tr class="border-b border-white/5 hover:bg-white/[0.02] transition-all">
                                        <td class="py-5 font-bold"><?= htmlspecialchars($emp['name']) ?></td>
                                        <td class="py-5 text-gray-400"><?= htmlspecialchars($emp['email']) ?></td>
                                        <td class="py-5 text-gray-500"><?= date('d/m/Y H:i', strtotime($emp['created_at'])) ?></td>
                                        <td class="py-5 text-right">
                                            <div class="flex justify-end gap-3">
                                                <form method="POST" onsubmit="return confirm('Activer ce compte employé ?');">
                                                    <input type="hidden" name="target_user_id" value="<?= $emp['id'] ?>">
                                                    <button type="submit" name="emp_action" value="approve" class="px-4 py-2 rounded-xl bg-green-500/10 text-green-500 border border-green-500/20 text-[10px] font-black uppercase tracking-widest hover:bg-green-500 hover:text-white transition-all shadow-lg hover:shadow-green-500/20">
                                                        <i class="fas fa-check mr-2"></i> Activer
                                                    </button>
                                                </form>
                                                <form method="POST" onsubmit="return confirm('DÉFINITIF : Supprimer cette demande ?');">
                                                    <input type="hidden" name="target_user_id" value="<?= $emp['id'] ?>">
                                                    <button type="submit" name="emp_action" value="reject" class="px-4 py-2 rounded-xl bg-red-500/10 text-red-500 border border-red-500/20 text-[10px] font-black uppercase tracking-widest hover:bg-red-500 hover:text-white transition-all shadow-lg hover:shadow-red-500/20">
                                                        <i class="fas fa-times mr-2"></i> Rejeter
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="lg:col-span-2 glass-card">
                <h3 class="text-2xl font-bold mb-8 flex items-center gap-3"><i class="fas fa-history text-yellow-500"></i> Dernières Connexions</h3>
                <div class="overflow-hidden">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] text-gray-500 uppercase tracking-widest border-b border-white/5">
                                <th class="pb-4">Utilisateur</th>
                                <th class="pb-4">Rôle</th>
                                <th class="pb-4">Date & Heure</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php foreach ($recent_logs as $log): ?>
                                <tr class="border-b border-white/5 hover:bg-white/5 transition-all">
                                    <td class="py-4 font-bold"><?= htmlspecialchars($log['name']) ?></td>
                                    <td class="py-4">
                                        <span class="px-2 py-1 rounded-md text-[9px] font-black uppercase tracking-tighter <?= $log['role'] === 'superadmin' ? 'bg-yellow-500/20 text-yellow-500' : 'bg-blue-500/20 text-blue-500' ?>">
                                            <?= $log['role'] ?>
                                        </span>
                                    </td>
                                    <td class="py-4 text-gray-400"><?= date('d/m/Y H:i', strtotime($log['login_date'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="glass-card flex flex-col justify-between">
                <div>
                    <h3 class="text-2xl font-bold mb-8 flex items-center gap-3"><i class="fas fa-rocket text-yellow-500"></i> Actions Rapides</h3>
                    <div class="space-y-4">
                        <a href="settings.php" class="block p-4 rounded-2xl bg-white/5 border border-white/10 hover:border-yellow-500/50 hover:bg-yellow-500/5 transition-all text-sm font-bold">
                            <i class="fas fa-edit mr-3 text-yellow-500"></i> Modifier les Tarifs
                        </a>
                        <a href="admins.php" class="block p-4 rounded-2xl bg-white/5 border border-white/10 hover:border-yellow-500/50 hover:bg-yellow-500/5 transition-all text-sm font-bold">
                            <i class="fas fa-user-plus mr-3 text-yellow-500"></i> Gérer le Staff
                        </a>
                        <a href="../admin/index.php" class="block p-4 rounded-2xl bg-white/5 border border-white/10 hover:border-yellow-500/50 hover:bg-yellow-500/5 transition-all text-sm font-bold">
                            <i class="fas fa-tasks mr-3 text-yellow-500"></i> Voir les Projets
                        </a>
                    </div>
                </div>
                <div class="mt-12 p-6 rounded-3xl bg-yellow-500/5 border border-yellow-500/10">
                    <p class="text-[10px] text-yellow-500 font-black uppercase tracking-widest mb-2 text-center">Estimation Totale</p>
                    <p class="text-3xl font-black text-center"><?= number_format($total_revenue_usd + ($total_revenue_cdf / 2500), 2) ?> <span class="text-xs">USD*</span></p>
                    <p class="text-[8px] text-gray-600 mt-2 text-center">*Basé sur un taux de 1 USD = 2500 CDF</p>
                </div>
            </div>
        </div>
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
    <?php include_once __DIR__ . '/../../includes/language_selector.php'; ?>
</body>
</html>
