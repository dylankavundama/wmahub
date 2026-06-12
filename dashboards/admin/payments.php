<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint à l'admin uniquement
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
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

$pageTitle = 'Historique des Paiements - Admin';
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
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c !important; color: #fff; min-height: 100vh; margin: 0; }
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
        .badge { padding: 0.4rem 0.8rem; border-radius: 99px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        #wma-global-loader { position: fixed; inset: 0; background: #0a0a0c; display: flex; align-items: center; justify-content: center; z-index: 100000; transition: opacity 0.5s ease; }
        .loader-spin { width: 40px; height: 40px; border: 3px solid rgba(255, 102, 0, 0.1); border-top-color: #ff6600; border-radius: 50%; animation: wma-spin 1s linear infinite; }
        @keyframes wma-spin { to { transform: rotate(360deg); } }
        .custom-select { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 0.75rem; padding: 0.5rem 1rem; color: #fff; font-size: 0.85rem; outline: none; transition: all 0.3s; }
        .custom-select:focus { border-color: #ff6600; }
        .search-bar { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 0.75rem; padding: 0.5rem 1rem 0.5rem 2.5rem; color: #fff; font-size: 0.85rem; width: 300px; outline: none; transition: all 0.3s; }
        .search-bar:focus { border-color: #ff6600; }
    </style>
</head>
<body>
    <div id="wma-global-loader"><div class="loader-spin"></div></div>
    <div class="bg-glow"></div>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <header class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12">
            <div>
                <h2 class="text-4xl font-black tracking-tighter text-white">Historique <span class="text-orange-500">Paiements</span></h2>
                <p class="text-gray-400 mt-2">Consultez et suivez toutes les transactions de la plateforme.</p>
            </div>
            
            <form method="GET" class="flex flex-wrap items-center gap-4">
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-xs"></i>
                    <input type="text" name="search" class="search-bar" placeholder="Référence, nom, email..." value="<?= htmlspecialchars($f_search) ?>">
                </div>
                
                <select name="f_status" onchange="this.form.submit()" class="custom-select">
                    <option value="all" <?= $f_status === 'all' ? 'selected' : '' ?>>Tout Statut</option>
                    <option value="success" <?= $f_status === 'success' ? 'selected' : '' ?>>Succès</option>
                    <option value="pending" <?= $f_status === 'pending' ? 'selected' : '' ?>>En attente</option>
                    <option value="failed" <?= $f_status === 'failed' ? 'selected' : '' ?>>Échec</option>
                </select>

                <select name="f_sort" onchange="this.form.submit()" class="custom-select">
                    <option value="date_desc" <?= $f_sort === 'date_desc' ? 'selected' : '' ?>>Récent → Ancien</option>
                    <option value="date_asc" <?= $f_sort === 'date_asc' ? 'selected' : '' ?>>Ancien → Récent</option>
                    <option value="amount_desc" <?= $f_sort === 'amount_desc' ? 'selected' : '' ?>>Montant Décroissant</option>
                    <option value="amount_asc" <?= $f_sort === 'amount_asc' ? 'selected' : '' ?>>Montant Croissant</option>
                </select>
            </form>
        </header>

        <div class="glass-card p-0 overflow-hidden shadow-2xl">
            <div class="overflow-x-auto">
                <table class="admin-table text-left" id="paymentsTable">
                    <thead>
                        <tr>
                            <th class="px-8">Date</th>
                            <th>Utilisateur</th>
                            <th>Type</th>
                            <th>Référence</th>
                            <th>Montant</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $p): 
                            $statusClass = match($p['status']) {
                                'success' => 'bg-green-500/10 text-green-500',
                                'pending' => 'bg-amber-500/10 text-amber-500',
                                'failed' => 'bg-red-500/10 text-red-500',
                                default => 'bg-gray-500/10 text-gray-500'
                            };
$statusLabel = match($p['status']) {
    'success' => 'Succès',
    'pending' => 'En attente',
    'failed' => 'Échec', // Changé de 'BLOQUÉ' implicite si c'était le cas, ou harmonisé avec Superadmin
    default => $p['status']
};
                            $typeLabel = match($p['payment_type']) {
                                'subscription' => 'Abonnement',
                                'certification' => 'Certification',
                                default => $p['payment_type'] ?: 'Paiement'
                            };
                        ?>
                            <tr>
                                <td class="px-8 text-xs font-medium text-gray-400">
                                    <?= date('d/m/Y H:i', strtotime($p['created_at'])) ?>
                                </td>
                                <td>
                                    <div class="font-bold text-white"><?= htmlspecialchars($p['user_name']) ?></div>
                                    <div class="text-[9px] text-gray-500 uppercase tracking-tighter"><?= htmlspecialchars($p['user_email']) ?> • <?= $p['user_role'] ?></div>
                                </td>
                                <td class="text-xs font-bold uppercase tracking-widest text-gray-300">
                                    <?= $typeLabel ?>
                                </td>
                                <td class="text-[10px] font-mono text-gray-500">
                                    <?= htmlspecialchars($p['reference']) ?>
                                </td>
                                <td class="font-black text-white">
                                    <?= number_format($p['amount'], $p['currency'] === 'USD' ? 2 : 0, '.', ' ') ?> 
                                    <span class="text-orange-500 text-[10px]"><?= $p['currency'] ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= $statusClass ?>">
                                        <?= $statusLabel ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($payments)): ?>
                            <tr><td colspan="6" class="text-center py-24 text-gray-500 italic">Aucune transaction trouvée.</td></tr>
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
    </script>
</body>
</html>
