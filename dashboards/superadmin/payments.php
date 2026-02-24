<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint au superadmin uniquement
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = getDBConnection();

// Paramètres de tri et filtrage
$f_status = $_GET['f_status'] ?? 'all';
$f_sort = $_GET['f_sort'] ?? 'date_desc';
$f_search = $_GET['search'] ?? '';

// Construction de la requête
$query = "SELECT p.*, u.name as user_name, u.email as user_email, u.role as user_role 
          FROM payments p 
          JOIN users u ON p.user_id = u.id 
          WHERE 1=1";
$params = [];

if ($f_status !== 'all') {
    $query .= " AND p.status = ?";
    $params[] = $f_status;
}

if ($f_search !== '') {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR p.reference LIKE ? OR p.order_number LIKE ?)";
    $searchParam = "%$f_search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

// Tris
$orderBy = match ($f_sort) {
    'date_asc' => "p.created_at ASC",
    'date_desc' => "p.created_at DESC",
    'amount_asc' => "p.amount ASC",
    'amount_desc' => "p.amount DESC",
    default => "p.created_at DESC"
};

$query .= " ORDER BY " . $orderBy;

$stmt = $db->prepare($query);
$stmt->execute($params);
$payments = $stmt->fetchAll();

$pageTitle = 'Master Control - Paiements';
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
        .glass-card { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 2.5rem; padding: 2.5rem; }
        .super-table { width: 100%; border-collapse: separate; border-spacing: 0 0.75rem; }
        .super-table th { padding: 1rem; color: rgba(255, 255, 255, 0.2); font-size: 0.7rem; text-transform: uppercase; font-weight: 900; letter-spacing: 2px; }
        .super-table tr td { padding: 1.5rem 1rem; background: rgba(255, 255, 255, 0.015); border-top: 1px solid rgba(255, 255, 255, 0.03); border-bottom: 1px solid rgba(255, 255, 255, 0.03); }
        .super-table tr td:first-child { border-left: 1px solid rgba(255, 255, 255, 0.03); border-radius: 1.5rem 0 0 1.5rem; }
        .super-table tr td:last-child { border-right: 1px solid rgba(255, 255, 255, 0.03); border-radius: 0 1.5rem 1.5rem 0; }
        .badge { padding: 0.4rem 0.8rem; border-radius: 1rem; font-size: 0.65rem; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; }
        .input-glass { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 1.25rem; color: #fff; padding: 1rem 1.5rem; outline: none; transition: all 0.3s ease; }
        .input-glass:focus { border-color: #ffd700; background: rgba(255, 255, 255, 0.06); }
        
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
                <p class="text-[9px] text-gray-500 font-bold uppercase tracking-[2px] -mt-1">Master Controls</p>
            </div>
        </div>
        
        <nav class="flex-1">
            <a href="index.php" class="nav-link"><i class="fas fa-th-large"></i> Vue d'ensemble</a>
            <a href="revenues.php" class="nav-link"><i class="fas fa-wallet"></i> Analyse Revenus</a>
            <a href="payments.php" class="nav-link active"><i class="fas fa-history"></i> Historique Paiements</a>
            <a href="payment_logs.php" class="nav-link"><i class="fas fa-file-alt"></i> Logs Paiement</a>
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
        <header class="mb-16 flex flex-col lg:flex-row lg:items-end justify-between gap-8">
            <div>
                <h2 class="text-6xl font-black tracking-tighter leading-none">Global <span class="text-yellow-500">Payments</span></h2>
                <p class="text-gray-500 mt-4 text-xl">Surveillance intégrale des flux financiers système.</p>
            </div>
            
            <form method="GET" class="flex flex-wrap items-center gap-4">
                <input type="text" name="search" class="input-glass !w-64" placeholder="Recherche Master..." value="<?= htmlspecialchars($f_search) ?>">
                
                <select name="f_status" onchange="this.form.submit()" class="input-glass !py-3 !w-40 font-bold uppercase text-[10px] tracking-widest cursor-pointer">
                    <option value="all" <?= $f_status === 'all' ? 'selected' : '' ?>>Tous Statuts</option>
                    <option value="success" <?= $f_status === 'success' ? 'selected' : '' ?>>SUCCÈS</option>
                    <option value="pending" <?= $f_status === 'pending' ? 'selected' : '' ?>>ATTENTE</option>
                    <option value="failed" <?= $f_status === 'failed' ? 'selected' : '' ?>>ÉCHEC</option>
                </select>

                <select name="f_sort" onchange="this.form.submit()" class="input-glass !py-3 !w-48 font-bold uppercase text-[10px] tracking-widest cursor-pointer">
                    <option value="date_desc" <?= $f_sort === 'date_desc' ? 'selected' : '' ?>>Plus Récent</option>
                    <option value="date_asc" <?= $f_sort === 'date_asc' ? 'selected' : '' ?>>Plus Ancien</option>
                    <option value="amount_desc" <?= $f_sort === 'amount_desc' ? 'selected' : '' ?>>Montant Max</option>
                    <option value="amount_asc" <?= $f_sort === 'amount_asc' ? 'selected' : '' ?>>Montant Min</option>
                </select>
            </form>
        </header>

        <div class="glass-card p-0 overflow-hidden">
            <div class="overflow-x-auto p-4">
                <table class="super-table text-left">
                    <thead>
                        <tr>
                            <th class="px-8">Timestamp</th>
                            <th>Master User</th>
                            <th>Entité</th>
                            <th>Référence Système</th>
                            <th>Volume</th>
                            <th>Status Master</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $p): 
                            $statusClass = match($p['status']) {
                                'success' => 'bg-green-500/10 text-green-500',
                                'pending' => 'bg-yellow-500/10 text-yellow-500',
                                'failed' => 'bg-red-500/10 text-red-500',
                                default => 'bg-white/5 text-gray-500'
                            };
$statusLabel = match($p['status']) {
    'success' => 'VÉRIFIÉ',
    'pending' => 'ATTENTE',
    'failed' => 'ÉCHOUÉ',
    default => strtoupper($p['status'])
};
                            $typeLabel = match($p['payment_type']) {
                                'subscription' => 'ABONNEMENT',
                                'certification' => 'BADGE BLUE',
                                default => strtoupper($p['payment_type'] ?: 'TRANSAC')
                            };
                        ?>
                            <tr>
                                <td class="px-8 text-xs font-mono text-gray-600">
                                    <?= date('Y-m-d H:i', strtotime($p['created_at'])) ?>
                                </td>
                                <td>
                                    <div class="font-black text-white tracking-tight"><?= htmlspecialchars($p['user_name']) ?></div>
                                    <div class="text-[9px] text-yellow-500/50 font-bold uppercase"><?= htmlspecialchars($p['user_email']) ?></div>
                                </td>
                                <td class="text-[9px] font-black tracking-widest text-gray-400">
                                    <?= $typeLabel ?>
                                </td>
                                <td class="text-[10px] font-mono text-gray-700">
                                    <?= htmlspecialchars($p['reference']) ?>
                                </td>
                                <td class="font-bold text-white text-lg">
                                    <?= number_format($p['amount'], $p['currency'] === 'USD' ? 2 : 0, '.', ' ') ?> 
                                    <span class="text-yellow-500 text-[10px]"><?= $p['currency'] ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= $statusClass ?>">
                                        <?= $statusLabel ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($payments)): ?>
                            <tr><td colspan="6" class="text-center py-24 text-gray-600 font-bold uppercase tracking-[4px]">No Transactions Logs</td></tr>
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
