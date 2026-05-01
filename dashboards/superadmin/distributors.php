<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint au superadmin uniquement
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = getDBConnection();

// Traitement du toggle d'activation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $userId = (int)$_POST['user_id'];
    $newStatus = (int)$_POST['new_status'];
    
    $stmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role = 'distributeur'");
    $stmt->execute([$newStatus, $userId]);
    
    header('Location: distributors.php');
    exit;
}

// Traitement de l'activation manuelle d'abonnement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['activate_sub'])) {
    $userId = (int)$_POST['user_id'];
    
    // Créer ou mette à jour un abonnement annuel pro gratuit (offert par admin)
    $stmt = $db->prepare("INSERT INTO subscriptions (user_id, plan_type, amount, currency, start_date, end_date, status) 
                          VALUES (?, 'annual', 0, 'USD', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 'active')
                          ON DUPLICATE KEY UPDATE status='active', start_date=CURDATE(), end_date=DATE_ADD(CURDATE(), INTERVAL 1 YEAR)");
    $stmt->execute([$userId]);
    
    header('Location: distributors.php');
    exit;
}

// Logique de tri
$sortField = $_GET['sort'] ?? 'created_at';
$sortOrder = $_GET['order'] ?? 'DESC';

$allowedFields = ['distributor_name', 'distributor_city', 'created_at', 'sub_status'];
if (!in_array($sortField, $allowedFields)) $sortField = 'created_at';
if (!in_array($sortOrder, ['ASC', 'DESC'])) $sortOrder = 'DESC';

$orderBy = match($sortField) {
    'distributor_name' => "u.distributor_name $sortOrder, u.name $sortOrder",
    'distributor_city' => "u.distributor_city $sortOrder",
    'sub_status' => "sub_status $sortOrder, sub_end_date $sortOrder",
    default => "u.created_at $sortOrder"
};

// Récupérer les distributeurs avec leur dernier abonnement et tri
$query = "SELECT u.*, 
                 s.status as sub_status, 
                 s.end_date as sub_end_date,
                 s.plan_type as sub_plan
          FROM users u 
          LEFT JOIN (
              SELECT user_id, status, end_date, plan_type
              FROM subscriptions
              WHERE id IN (SELECT MAX(id) FROM subscriptions GROUP BY user_id)
          ) s ON u.id = s.user_id
          WHERE u.role = 'distributeur'
          ORDER BY $orderBy";

$distributors = $db->query($query)->fetchAll();

function getSortURL($field, $currentSort, $currentOrder) {
    $newOrder = ($currentSort === $field && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    return "distributors.php?sort=$field&order=$newOrder";
}

$pageTitle = 'Master Control - Distributeurs';
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
        .super-table { width: 100%; border-collapse: separate; border-spacing: 0 0.75rem; }
        .super-table th { padding: 1rem; color: rgba(255, 255, 255, 0.2); font-size: 0.7rem; text-transform: uppercase; font-weight: 900; letter-spacing: 2px; }
        .super-table tr td { padding: 1.5rem 1rem; background: rgba(255, 255, 255, 0.015); border-top: 1px solid rgba(255, 255, 255, 0.03); border-bottom: 1px solid rgba(255, 255, 255, 0.03); }
        .super-table tr td:first-child { border-left: 1px solid rgba(255, 255, 255, 0.03); border-radius: 1.5rem 0 0 1.5rem; }
        .super-table tr td:last-child { border-right: 1px solid rgba(255, 255, 255, 0.03); border-radius: 0 1.5rem 1.5rem 0; }
        .badge { padding: 0.4rem 0.8rem; border-radius: 1rem; font-size: 0.65rem; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; }
        .btn-action { padding: 0.6rem 1.2rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 800; transition: all 0.4s; text-transform: uppercase; letter-spacing: 1px; }
        
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
            <a href="index.php" class="nav-link"><i class="fas fa-th-large"></i> Vue d'ensemble</a>
            <a href="revenues.php" class="nav-link"><i class="fas fa-wallet"></i> Analyse Revenus</a>
            <a href="payments.php" class="nav-link"><i class="fas fa-history"></i> Historique Paiements</a>
            <a href="payment_logs.php" class="nav-link"><i class="fas fa-file-alt"></i> Logs Paiement</a>
            <a href="distributors.php" class="nav-link active"><i class="fas fa-truck-loading"></i> Distributeurs</a>
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
            <h2 class="text-6xl font-black tracking-tighter leading-none">Master <span class="text-yellow-500">Distributeurs</span></h2>
            <p class="text-gray-500 mt-4 text-xl">Surveillance globale et contrôle des accès partenaires haut niveau.</p>
        </header>

        <div class="glass-card p-0 overflow-hidden">
            <div class="overflow-x-auto p-4">
                <table class="super-table text-left">
                    <thead>
                        <tr>
                            <th class="px-8 py-4">
                                <a href="<?= getSortURL('distributor_name', $sortField, $sortOrder) ?>" class="hover:text-yellow-500 transition-colors flex items-center gap-2">
                                    Partenaire
                                    <i class="fas fa-sort<?= $sortField === 'distributor_name' ? ($sortOrder === 'ASC' ? '-up' : '-down') : '' ?> opacity-50 text-[10px]"></i>
                                </a>
                            </th>
                            <th class="py-4">
                                <a href="<?= getSortURL('distributor_city', $sortField, $sortOrder) ?>" class="hover:text-yellow-500 transition-colors flex items-center gap-2">
                                    Localisation
                                    <i class="fas fa-sort<?= $sortField === 'distributor_city' ? ($sortOrder === 'ASC' ? '-up' : '-down') : '' ?> opacity-50 text-[10px]"></i>
                                </a>
                            </th>
                            <th class="py-4">
                                <a href="<?= getSortURL('sub_status', $sortField, $sortOrder) ?>" class="hover:text-yellow-500 transition-colors flex items-center gap-2">
                                    Abonnement
                                    <i class="fas fa-sort<?= $sortField === 'sub_status' ? ($sortOrder === 'ASC' ? '-up' : '-down') : '' ?> opacity-50 text-[10px]"></i>
                                </a>
                            </th>
                            <th class="py-4">Statut Accès</th>
                            <th class="py-4">
                                <a href="<?= getSortURL('created_at', $sortField, $sortOrder) ?>" class="hover:text-yellow-500 transition-colors flex items-center gap-2">
                                    Date Inscription
                                    <i class="fas fa-sort<?= $sortField === 'created_at' ? ($sortOrder === 'ASC' ? '-up' : '-down') : '' ?> opacity-50 text-[10px]"></i>
                                </a>
                            </th>
                            <th class="py-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($distributors as $d): 
                            $isActive = (bool)$d['is_active'];
                            $subStatus = $d['sub_status'] ?? 'aucun';
                            $subClass = match($subStatus) {
                                'active' => 'bg-green-500/10 text-green-500',
                                'expired' => 'bg-red-500/10 text-red-500',
                                default => 'bg-white/5 text-gray-500'
                            };
                        ?>
                            <tr>
                                <td class="px-8">
                                    <div class="flex items-center gap-5">
                                        <div class="w-12 h-12 rounded-2xl bg-yellow-500/10 flex items-center justify-center border border-yellow-500/20 overflow-hidden">
                                            <?php if ($d['distributor_logo']): ?>
                                                <img src="../distributeur/uploads/<?= $d['distributor_logo'] ?>" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <i class="fas fa-university text-yellow-500/50 text-xl"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="font-black text-white tracking-tight"><?= htmlspecialchars($d['distributor_name'] ?: $d['name']) ?></div>
                                            <div class="text-[10px] text-gray-500 font-bold uppercase tracking-widest"><?= htmlspecialchars($d['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-sm font-medium text-gray-300"><?= htmlspecialchars($d['distributor_city'] ?: 'Inconnu') ?></td>
                                <td>
                                    <span class="badge <?= $subClass ?>">
                                        <?= $subStatus === 'active' ? 'Full Access' : ($subStatus === 'expired' ? 'Expiré' : 'Inactif') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full <?= $isActive ? 'bg-blue-500 shadow-[0_0_10px_rgba(59,130,246,0.5)]' : 'bg-red-500 shadow-[0_0_10px_rgba(239,68,68,0.5)]' ?>"></div>
                                        <span class="text-[10px] font-black uppercase tracking-tighter <?= $isActive ? 'text-blue-400' : 'text-red-400' ?>">
                                            <?= $isActive ? 'OPÉRATIONNEL' : 'SUSPENDU' ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="text-xs text-gray-500 font-mono">
                                    <?= $d['sub_end_date'] ? date('d/m/Y', strtotime($d['sub_end_date'])) : '---' ?>
                                </td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Veuillez confirmer la modification de l\'état d\'accès.')">
                                        <input type="hidden" name="user_id" value="<?= $d['id'] ?>">
                                        <input type="hidden" name="new_status" value="<?= $isActive ? 0 : 1 ?>">
                                        <button type="submit" name="toggle_status" class="btn-action <?= $isActive ? 'bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white' : 'bg-yellow-500/10 text-yellow-500 hover:bg-yellow-500 hover:text-black' ?>">
                                            <?= $isActive ? 'Couper Accès' : 'Rétablir Accès' ?>
                                        </button>
                                    </form>
                                    <form method="POST" onsubmit="return confirm('Offrir 1 an d\'accès Full à ce partenaire ?')" class="mt-2">
                                        <input type="hidden" name="user_id" value="<?= $d['id'] ?>">
                                        <button type="submit" name="activate_sub" class="w-full btn-action bg-green-500/10 text-green-500 hover:bg-green-500 hover:text-white">
                                            <i class="fas fa-crown mr-1"></i> Activer PRO
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($distributors)): ?>
                            <tr><td colspan="6" class="text-center py-24 text-gray-600 font-bold uppercase tracking-[4px]">No Partners Found</td></tr>
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
                setTimeout(() => loader.style.display = 'none', 600);
            }
        });
    </script>
</body>
</html>
