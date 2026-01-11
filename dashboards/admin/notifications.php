<?php
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = getDBConnection();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMA ADMIN - Notifications</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; min-height: 100vh; }
        .bg-glow { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; display: flex; flex-direction: column; padding: 2rem 1.5rem; }
        .main-content { margin-left: 280px; padding: 2rem; min-height: 100vh; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; font-size: 0.9rem; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; transform: translateX(5px); }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 1.5rem; padding: 1.5rem; }
        .notif-row { display: flex; align-items: center; gap: 1.5rem; padding: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.05); transition: all 0.2s; }
        .notif-row:hover { background: rgba(255,255,255,0.02); }
        .notif-row.unread { background: rgba(255,102,0,0.03); border-left: 4px solid #ff6600; }
    </style>
</head>
<body>
    <div class="bg-glow"></div>
    
    <aside class="sidebar">
        <div class="flex items-center gap-4 mb-12 px-2">
            <img src="../../asset/trans.png" alt="Logo" class="h-10">
            <div>
                <h1 class="text-xl font-black bg-gradient-to-r from-orange-500 to-orange-300 bg-clip-text text-transparent tracking-tighter leading-tight">WMA HUB</h1>
                <p class="text-[8px] text-gray-500 font-bold uppercase tracking-[1px] -mt-1">We Farm Your Talent</p>
            </div>
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
    </aside>

    <main class="main-content">
        <header class="flex items-center justify-between mb-12">
            <div>
                <h2 class="text-4xl font-black tracking-tighter text-white">Vos <span class="text-orange-500">Notifications</span></h2>
                <p class="text-gray-400 mt-2">Restez informé de l'activité sur la plateforme.</p>
            </div>
            <?php include '../../includes/header_notifications.php'; ?>
        </header>

        <div class="glass-card p-0" id="fullNotifList">
            <!-- Notifications will be loaded via JS -->
            <div class="p-20 text-center"><i class="fas fa-spinner fa-spin text-3xl text-orange-500"></i></div>
        </div>
    </main>

    <script>
        // Custom rendering for full page
        async function loadFullHistory() {
            const list = document.getElementById('fullNotifList');
            try {
                const response = await fetch('../../api/notifications.php?action=get_all');
                const data = await response.json();
                if (data.success) {
                    if (data.notifications.length === 0) {
                        list.innerHTML = '<div class="p-20 text-center text-gray-500">Aucune notification à afficher</div>';
                    } else {
                        list.innerHTML = data.notifications.map(n => `
                            <div class="notif-row ${n.is_read ? '' : 'unread'}">
                                <div class="w-12 h-12 rounded-xl bg-orange-500/10 flex items-center justify-center text-orange-500 flex-shrink-0">
                                    <i class="fas ${getIcon(n.type)}"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-white font-semibold">${getText(n)}</p>
                                    <p class="text-[10px] text-gray-500 mt-1 uppercase font-black tracking-widest">${new Date(n.created_at).toLocaleString()}</p>
                                </div>
                                ${!n.is_read ? `<button onclick="markRead(${n.id})" class="text-[10px] font-bold text-orange-500 uppercase hover:underline">Marquer lu</button>` : ''}
                            </div>
                        `).join('');
                    }
                }
            } catch (e) {
                list.innerHTML = '<div class="p-20 text-center text-red-500">Erreur de chargement</div>';
            }
        }

        function getIcon(type) {
            switch(type) {
                case 'new_project': return 'fa-plus';
                case 'new_task': return 'fa-tasks';
                case 'new_message': return 'fa-comment';
                case 'new_broadcast_message': return 'fa-comments';
                default: return 'fa-bell';
            }
        }

        function getText(n) {
            switch(n.type) {
                case 'new_project': return 'Un nouveau projet a été créé sur la plateforme.';
                case 'new_task': return 'Une nouvelle mission vous attend.';
                case 'new_message': return 'Vous avez reçu un nouveau message privé.';
                case 'new_broadcast_message': return 'Un nouveau message a été posté dans le chat général de l\'équipe.';
                default: return 'Nouvelle alerte système.';
            }
        }

        async function markRead(id) {
            const fd = new FormData();
            fd.append('id', id);
            await fetch('../../api/notifications.php?action=mark_read', { method: 'POST', body: fd });
            loadFullHistory();
        }

        loadFullHistory();
    </script>
</body>
</html>
