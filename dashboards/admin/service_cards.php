<?php
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = getDBConnection();

// Fetch all service card requests
$stmt = $db->query("SELECT s.*, u.email as user_email FROM service_cards s JOIN users u ON s.user_id = u.id ORDER BY s.updated_at DESC");
$requests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMA ADMIN - Gestion des Cartes</title>
    <link rel="icon" type="image/png" href="../../asset/icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; min-height: 100vh; overflow-x: hidden; }
        .bg-glow { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .glow-spot { position: fixed; width: 40vw; height: 40vw; background: radial-gradient(circle, rgba(255, 102, 0, 0.05) 0%, transparent 70%); border-radius: 50%; z-index: -1; filter: blur(80px); pointer-events: none; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; display: flex; flex-direction: column; padding: 2rem 1.5rem; }
        .main-content { margin-left: 280px; padding: 2rem; min-height: 100vh; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; font-size: 0.9rem; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; transform: translateX(5px); }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 1.5rem; padding: 1.5rem; }
        .admin-table { width: 100%; border-collapse: separate; border-spacing: 0 0.75rem; }
        .admin-table tr { background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.05); }
        .admin-table td, .admin-table th { padding: 1.25rem 1.5rem; vertical-align: middle; }
        .admin-table th { text-transform: uppercase; font-size: 0.65rem; font-weight: 800; letter-spacing: 1.5px; color: rgba(255, 255, 255, 0.3); }
        @media (max-width: 1024px) { .sidebar { transform: translateX(-100%); width: 0; padding: 0; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <div class="bg-glow"></div>
    <div id="glow" class="glow-spot"></div>

    <aside class="sidebar">
        <div class="flex items-center gap-4 mb-12 px-2">
            <img src="../../asset/trans.png" alt="Logo" class="h-10">
            <h1 class="text-xl font-black bg-gradient-to-r from-orange-500 to-orange-300 bg-clip-text text-transparent tracking-tighter">WMA ADMIN</h1>
        </div>
        <nav class="flex-1">
            <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>"><i class="fas fa-layer-group"></i> Gestion Projets</a>
            <a href="employees.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'employees.php' ? 'active' : '' ?>"><i class="fas fa-users-cog"></i> Équipe & Staff</a>
            <a href="tasks.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'tasks.php' ? 'active' : '' ?>"><i class="fas fa-tasks"></i> Gestion Tâches</a>
            <a href="salaries.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'salaries.php' ? 'active' : '' ?>"><i class="fas fa-money-check-alt"></i> Gestion Salaires</a>
            <a href="chat.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'chat.php' ? 'active' : '' ?>"><i class="fas fa-comments"></i> Chat Équipe</a>
            <a href="service_cards.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'service_cards.php' ? 'active' : '' ?>"><i class="fas fa-id-card"></i> Cartes de Service</a>
            <a href="notifications.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'notifications.php' ? 'active' : '' ?>"><i class="fas fa-bell"></i> Notifications</a>
            <a href="finance.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'finance.php' ? 'active' : '' ?>"><i class="fas fa-chart-pie"></i> Rapports Financiers</a>
            <a href="site_stats.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'site_stats.php' ? 'active' : '' ?>"><i class="fas fa-chart-line"></i> Statistiques Site</a>
            <a href="users.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>"><i class="fas fa-user-friends"></i> Utilisateurs</a>
        </nav>
        <div class="mt-auto pt-6 border-t border-white/5">
            <div class="flex items-center gap-4 mb-8 px-2">
                <div class="w-10 h-10 rounded-full bg-orange-500/10 border border-orange-500/20 flex items-center justify-center text-orange-500"><i class="fas fa-user-shield"></i></div>
                <div>
                    <p class="text-sm font-bold text-white"><?= explode(' ', $_SESSION['user_name'])[0] ?></p>
                    <p class="text-[10px] text-gray-500 uppercase font-black tracking-widest"><?= $_SESSION['role'] ?></p>
                </div>
            </div>
            <a href="../../auth/logout.php" class="nav-link !text-red-500 hover:!bg-red-500/10"><i class="fas fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="flex flex-col md:flex-row md:items-center justify-between gap-8 mb-12">
            <div>
                <h2 class="text-4xl font-black tracking-tighter text-white">Gestion des <span class="text-orange-500">Cartes</span></h2>
                <p class="text-gray-400 mt-2">Validez ou refusez les demandes de cartes de service du personnel.</p>
            </div>
        </header>

        <div class="glass-card p-0 overflow-hidden shadow-2xl border-white/5">
            <div class="px-8 py-6 border-b border-white/5 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <h3 class="text-lg font-bold flex items-center gap-3"><i class="fas fa-id-card text-orange-500"></i> Demandes en attente</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="admin-table text-left">
                    <thead>
                        <tr>
                            <th class="px-8">Employé</th>
                            <th>Fonction & Dept</th>
                            <th>Matricule</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                            <tr>
                                <td class="px-8">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-xl border border-white/10 overflow-hidden bg-white/5">
                                            <img src="../../<?= $req['photo_path'] ?: 'asset/aspi.jpg' ?>" class="w-full h-full object-cover">
                                        </div>
                                        <div>
                                            <div class="font-bold text-white"><?= htmlspecialchars($req['full_name']) ?></div>
                                            <div class="text-[10px] text-gray-500"><?= htmlspecialchars($req['user_email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-sm font-semibold"><?= htmlspecialchars($req['role']) ?></div>
                                    <div class="text-[10px] text-orange-500 font-bold"><?= htmlspecialchars($req['department']) ?></div>
                                </td>
                                <td>
                                    <div class="text-xs font-mono"><?= htmlspecialchars($req['matricule'] ?: 'N/A') ?></div>
                                </td>
                                <td>
                                    <?php 
                                        $statusClass = match($req['status']) {
                                            'approved' => 'text-green-500 bg-green-500/10',
                                            'rejected' => 'text-red-500 bg-red-500/10',
                                            default => 'text-amber-500 bg-amber-500/10'
                                        };
                                        $statusLabel = match($req['status']) {
                                            'approved' => 'Approuvée',
                                            'rejected' => 'Refusée',
                                            default => 'En attente'
                                        };
                                    ?>
                                    <span class="text-[10px] font-black uppercase px-2 py-1 rounded <?= $statusClass ?>"><?= $statusLabel ?></span>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <?php if ($req['status'] !== 'approved'): ?>
                                            <button onclick="updateStatus(<?= $req['id'] ?>, 'approve')" class="p-2 bg-green-500/10 text-green-500 rounded-lg hover:bg-green-500 hover:text-white transition-all">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($req['status'] !== 'rejected'): ?>
                                            <button onclick="updateStatus(<?= $req['id'] ?>, 'reject')" class="p-2 bg-red-500/10 text-red-500 rounded-lg hover:bg-red-500 hover:text-white transition-all">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($requests)): ?>
                            <tr>
                                <td colspan="5" class="py-12 text-center text-gray-500 uppercase font-black tracking-widest text-xs">Aucune demande de carte trouvée</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        const glow = document.getElementById('glow');
        document.addEventListener('mousemove', (e) => { glow.style.left = (e.clientX - 200) + 'px'; glow.style.top = (e.clientY - 200) + 'px'; });

        async function updateStatus(cardId, action) {
            if (!confirm('Êtes-vous sûr de vouloir ' + (action === 'approve' ? 'approuver' : 'refuser') + ' cette carte ?')) return;

            const formData = new FormData();
            formData.append('card_id', cardId);

            try {
                const response = await fetch('../../api/service_cards.php?action=' + action, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    window.location.reload();
                } else {
                    alert('Erreur: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Une erreur est survenue.');
            }
        }
    </script>
</body>
</html>
