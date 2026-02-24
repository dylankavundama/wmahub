<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint à l'admin uniquement
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = getDBConnection();

// Traitement du toggle d'activation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $userId = (int)$_POST['user_id'];
    $newStatus = (int)$_POST['new_status'];
    
    $stmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role = 'artiste'");
    $stmt->execute([$newStatus, $userId]);
    
    header('Location: artists.php');
    exit;
}

// Récupérer les artistes avec statistiques
$query = "SELECT u.*, 
                 (SELECT COUNT(*) FROM projects WHERE user_id = u.id) as project_count,
                 s.status as sub_status, 
                 s.end_date as sub_end_date
          FROM users u 
          LEFT JOIN (
              SELECT user_id, status, end_date
              FROM subscriptions
              WHERE id IN (SELECT MAX(id) FROM subscriptions GROUP BY user_id)
          ) s ON u.id = s.user_id
          WHERE u.role = 'artiste'
          ORDER BY u.created_at DESC";

$artists = $db->query($query)->fetchAll();

$pageTitle = 'Gestion des Artistes - Admin';
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
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c !important; color: #fff; min-height: 100vh; margin: 0; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; padding: 2rem 1.5rem; display: flex; flex-direction: column; }
        .main-content { margin-left: 280px; padding: 2rem; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; transform: translateX(5px); }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 1.5rem; padding: 2rem; }
        .admin-table { width: 100%; border-collapse: separate; border-spacing: 0 0.5rem; }
        .admin-table th { padding: 1rem; color: rgba(255, 255, 255, 0.4); font-size: 0.75rem; text-transform: uppercase; font-weight: 800; letter-spacing: 1px; }
        .admin-table tr td { padding: 1.25rem 1rem; background: rgba(255, 255, 255, 0.02); border-top: 1px solid rgba(255, 255, 255, 0.05); border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .admin-table tr td:first-child { border-left: 1px solid rgba(255, 255, 255, 0.05); border-radius: 1rem 0 0 1rem; }
        .admin-table tr td:last-child { border-right: 1px solid rgba(255, 255, 255, 0.05); border-radius: 0 1rem 1rem 0; }
        .status-badge { padding: 0.35rem 0.75rem; border-radius: 99px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .btn-toggle { padding: 0.5rem 1rem; border-radius: 0.75rem; font-size: 0.75rem; font-weight: 700; transition: all 0.3s; }
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
                <p class="text-[8px] text-gray-500 font-bold uppercase tracking-[1px] -mt-1">Admin Panel</p>
            </div>
        </div>
        <nav class="flex-1">
            <a href="index.php" class="nav-link"><i class="fas fa-layer-group"></i> Projets</a>
            <a href="subscriptions.php" class="nav-link"><i class="fas fa-crown"></i> Abonnements</a>
            <a href="payments.php" class="nav-link"><i class="fas fa-history"></i> Paiements</a>
            <a href="artists.php" class="nav-link active"><i class="fas fa-microphone-alt"></i> Artistes</a>
            <a href="distributors.php" class="nav-link"><i class="fas fa-truck-loading"></i> Distributeurs</a>
            <a href="employees.php" class="nav-link"><i class="fas fa-users-cog"></i> Équipe</a>
            <a href="users.php" class="nav-link"><i class="fas fa-user-friends"></i> Utilisateurs</a>
        </nav>
        <div class="mt-auto pt-6 border-t border-white/5">
            <a href="../../auth/logout.php" class="nav-link !text-red-500"><i class="fas fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="mb-12">
            <h2 class="text-4xl font-black tracking-tighter">Gestion <span class="text-orange-500">Artistes</span></h2>
            <p class="text-gray-400 mt-2">Suivez les activités, abonnements et accès des artistes de la plateforme.</p>
        </header>

        <div class="glass-card p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="admin-table text-left">
                    <thead>
                        <tr>
                            <th class="px-8">Artiste</th>
                            <th>Projets</th>
                            <th>Abonnement</th>
                            <th>Expiration</th>
                            <th>Accès</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($artists as $a): 
                            $isActive = (bool)$a['is_active'];
                            $subStatus = $a['sub_status'] ?? 'aucun';
                            $subColor = match($subStatus) {
                                'active' => 'bg-green-500/10 text-green-500',
                                'expired' => 'bg-red-500/10 text-red-500',
                                default => 'bg-gray-500/10 text-gray-500'
                            };
                        ?>
                            <tr>
                                <td class="px-8">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-full bg-orange-500/10 flex items-center justify-center border border-orange-500/20 text-orange-500">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <div class="font-bold text-white"><?= htmlspecialchars($a['name']) ?></div>
                                            <div class="text-[9px] text-gray-500 uppercase tracking-tighter"><?= htmlspecialchars($a['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <span class="text-white font-black"><?= $a['project_count'] ?></span>
                                        <span class="text-[10px] text-gray-500 uppercase font-bold">Sorties</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?= $subColor ?>">
                                        <?= $subStatus === 'active' ? 'ACTIF' : ($subStatus === 'expired' ? 'EXPIRÉ' : 'AUCUN') ?>
                                    </span>
                                </td>
                                <td class="text-xs text-gray-400">
                                    <?= $a['sub_end_date'] ? date('d/m/Y', strtotime($a['sub_end_date'])) : '-' ?>
                                </td>
                                <td>
                                    <span class="status-badge <?= $isActive ? 'bg-blue-500/10 text-blue-400' : 'bg-red-500/10 text-red-400' ?>">
                                        <?= $isActive ? 'ACTIF' : 'SUSPENDU' ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Voulez-vous vraiment changer l\'état de cet artiste ?')">
                                        <input type="hidden" name="user_id" value="<?= $a['id'] ?>">
                                        <input type="hidden" name="new_status" value="<?= $isActive ? 0 : 1 ?>">
                                        <button type="submit" name="toggle_status" class="btn-toggle <?= $isActive ? 'bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white' : 'bg-green-500/10 text-green-500 hover:bg-green-500 hover:text-white' ?>">
                                            <i class="fas <?= $isActive ? 'fa-user-slash' : 'fa-user-check' ?> mr-2"></i>
                                            <?= $isActive ? 'Suspendre' : 'Rétablir' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($artists)): ?>
                            <tr><td colspan="6" class="text-center py-20 text-gray-500 italic">Aucun artiste enregistré.</td></tr>
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
