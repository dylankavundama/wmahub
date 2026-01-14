<?php
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'artiste') {
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
    <title>WMA HUB - Vos Notifications</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; min-height: 100vh; }
        .bg-glow { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; transition: all 0.3s ease; display: flex; flex-direction: column; padding: 2rem 1.5rem; }
        .main-content { margin-left: 280px; padding: 2rem; min-height: 100vh; transition: all 0.3s ease; }
        
        /* Mobile Enhancements */
        .mobile-header { display: none; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; background: rgba(10, 10, 12, 0.8); backdrop-filter: blur(10px); position: sticky; top: 0; z-index: 90; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 95; backdrop-filter: blur(4px); }
        
        @media (max-width: 1024px) { 
            .sidebar { transform: translateX(-100%); } 
            .sidebar.active { transform: translateX(0); width: 280px; padding: 2rem 1.5rem; }
            .sidebar-overlay.active { display: block; }
            .main-content { margin-left: 0; padding: 1.5rem; } 
            .mobile-header { display: flex; }
        }
        
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; font-size: 0.9rem; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; transform: translateX(5px); }
    </style>
</head>
<body>
    <div class="bg-glow"></div>
    
    <div class="mobile-header">
        <div class="flex items-center gap-3">
            <img src="../../asset/trans.png" alt="Logo" class="h-8">
            <span class="font-bold tracking-tighter">WMA ARTISTE</span>
        </div>
        <button id="sidebarToggle" class="text-white text-2xl p-2"><i class="fas fa-bars"></i></button>
    </div>

    <div class="sidebar-overlay" id="overlay"></div>

    <aside class="sidebar" id="sidebar">
            <div class="flex items-center gap-4 mb-10 px-2">
                <img src="../../asset/trans.png" alt="Logo" class="h-10">
                <div>
                    <h1 class="text-xl font-bold bg-gradient-to-r from-orange-500 to-orange-300 bg-clip-text text-transparent leading-none">WMA HUB</h1>
                    <p class="text-[8px] text-gray-500 font-bold uppercase tracking-[1px] mt-1">We move, WMAFam</p>
                </div>
            </div>
        <nav class="flex-1">
            <a href="index.php" class="nav-link"><i class="fas fa-th-large"></i>Tableau de bord</a>
            <a href="submit.php" class="nav-link"><i class="fas fa-plus-circle"></i>Soumettre</a>
            <a href="notifications.php" class="nav-link active"><i class="fas fa-bell"></i>Notifications</a>
            <a href="catalogue.php" class="nav-link"><i class="fas fa-music"></i>Catalogue</a>
            <a href="stats.php" class="nav-link"><i class="fas fa-chart-line"></i>Stats</a>
            <a href="#" class="nav-link disabled opacity-50 cursor-not-allowed"><i class="fas fa-wallet"></i>Revenus</a>
        </nav>
        <div class="mt-auto pt-6 border-t border-white/5">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-full bg-orange-500/20 flex items-center justify-center text-orange-500 border border-orange-500/20"><i class="fas fa-user"></i></div>
                <div class="overflow-hidden"><p class="text-sm font-bold truncate"><?= htmlspecialchars($_SESSION['user_name']) ?></p></div>
            </div>
            <a href="../../auth/logout.php" class="nav-link !text-red-500 hover:!bg-red-500/10"><i class="fas fa-power-off"></i>Déconnexion</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="flex items-center justify-between mb-12">
            <div>
                <h2 class="text-4xl font-black tracking-tighter text-white">Vos <span class="text-orange-500">Notifications</span></h2>
                <p class="text-gray-400 mt-2">Suivez l'état de vos projets et de vos revenus.</p>
            </div>
            <?php include '../../includes/header_notifications.php'; ?>
        </header>

        <div class="glass-card p-0" id="fullNotifList">
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
                case 'new_project': return 'fa-rocket';
                case 'project_update': return 'fa-sync';
                case 'payment_received': return 'fa-wallet';
                default: return 'fa-bell';
            }
        }

        function getText(n) {
            if (n.message) return n.message;
            switch(n.type) {
                case 'new_project': return 'Votre demande de distribution a bien été enregistrée.';
                case 'project_update': return 'Le statut de votre projet a été mis à jour.';
                case 'payment_received': return 'Un nouveau paiement a été validé pour votre compte.';
                case 'new_broadcast_message': return 'Un nouveau message important de l\'équipe WMA.';
                default: return 'Information WMA Hub.';
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
