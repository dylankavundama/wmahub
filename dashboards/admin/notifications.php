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
    
    <!-- Scripts et CSS Prioritaires -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin-shared.css">
    
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
            background: #0a0a0c !important; 
            color: #fff; 
            min-height: 100vh; 
            margin: 0;
            overflow-x: hidden;
        }

        /* Loader haute priorité */
        #wma-global-loader {
            position: fixed;
            inset: 0;
            background: #0a0a0c;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100000;
            transition: opacity 0.5s ease;
        }

        .loader-spin {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(255, 102, 0, 0.1);
            border-top-color: #ff6600;
            border-radius: 50%;
            animation: wma-spin 1s linear infinite;
        }

        @keyframes wma-spin { to { transform: rotate(360deg); } }
        
        .bg-glow { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; display: flex; flex-direction: column; padding: 2rem 1.5rem; transition: all 0.3s ease; }
        .main-content { margin-left: 280px; padding: 2rem; min-height: 100vh; transition: all 0.3s ease; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; font-size: 0.9rem; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; transform: translateX(5px); }
        
        @media (max-width: 1024px) { 
            .sidebar { transform: translateX(-100%); } 
            .sidebar.active { transform: translateX(0); width: 280px; padding: 2rem 1.5rem; }
            .main-content { margin-left: 0; padding: 1.5rem; } 
            .mobile-header { display: flex; }
        }
    </style>
</head>
<body>
    <div id="wma-global-loader">
        <div class="loader-spin"></div>
    </div>
    <div class="bg-glow"></div>
    
    <div class="mobile-header">
        <div class="flex items-center gap-3">
            <img src="../../asset/trans.png" alt="Logo" class="h-8">
            <span class="font-bold tracking-tighter">WMA ADMIN</span>
        </div>
        <button id="sidebarToggle" class="text-white text-2xl p-2"><i class="fas fa-bars"></i></button>
    </div>

    <div class="sidebar-overlay" id="overlay"></div>

    <?php include 'sidebar.php'; ?>

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
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const toggle = document.getElementById('sidebarToggle');

        function toggleSidebar() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        if (toggle) toggle.addEventListener('click', toggleSidebar);
        if (overlay) overlay.addEventListener('click', toggleSidebar);

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
