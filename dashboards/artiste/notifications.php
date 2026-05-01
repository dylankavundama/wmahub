<?php
require_once __DIR__ . '/auth_artist.php';

$db = getDBConnection();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMA HUB - Vos Notifications</title>
    <link rel="icon" type="image/png" href="/asset/icon.png"><link rel="apple-touch-icon" href="/asset/icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; margin: 0; min-height: 100vh; overflow-x: hidden; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .sidebar { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .sidebar.active { transform: translateX(0); }
        @media (max-width: 1024px) { .sidebar { transform: translateX(-100%); position: fixed; z-index: 50; height: 100vh; } }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px); border: 1px border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 20px; transition: all 0.3s ease; }
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: #94a3b8; transition: all 0.3s ease; margin-bottom: 4px; }
        .nav-link:hover:not(.active) { background: rgba(255, 255, 255, 0.03); color: #fff; }
        .nav-link.active { background: linear-gradient(135deg, #ff6600 0%, #ff8c00 100%); color: #fff; box-shadow: 0 4px 15px rgba(255, 102, 0, 0.3); }
        .nav-link i { font-size: 1.1rem; }
        #wma-global-loader { position: fixed; inset: 0; background: #0a0a0c; z-index: 9999; display: flex; align-items: center; justify-content: center; transition: opacity 0.5s ease; }
        .loader-spin { width: 50px; height: 50px; border: 3px solid rgba(255, 102, 0, 0.1); border-top-color: #ff6600; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .notif-row { display: flex; align-items: center; gap: 20px; padding: 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); transition: all 0.3s ease; }
        .notif-row:hover { background: rgba(255, 255, 255, 0.02); }
        .notif-row:last-child { border-bottom: none; }
        .notif-row.unread { background: rgba(255, 102, 0, 0.03); border-left: 3px solid #ff6600; padding-left: 17px; }
    </style>
</head>
<body>
    <div id="wma-global-loader">
        <div class="loader-spin"></div>
    </div>
    <div class="bg-glow"></div>
    
    <!-- Mobile Header -->
    <div class="lg:hidden flex items-center justify-between p-4 bg-[#0a0a0c] border-b border-white/5 sticky top-0 z-40">
        <div class="flex items-center gap-3">
            <img src="../../asset/trans.png" alt="Logo" class="w-8 h-8 object-contain">
            <span class="text-lg font-bold tracking-tighter">WMA ARTISTE</span>
        </div>
        <button id="sidebarToggle" class="text-white text-2xl p-2"><i class="fas fa-bars"></i></button>
    </div>

    <!-- Sidebar Overlay -->
    <div id="overlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 hidden lg:hidden"></div>

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar w-72 bg-[#0d0d0f] border-r border-white/5 flex flex-col p-6 overflow-y-auto">
            <div class="flex items-center gap-4 mb-10 px-2">
                <img src="../../asset/trans.png" alt="Logo" class="w-10 h-10 object-contain">
                <div>
                    <h1 class="text-xl font-bold tracking-tighter bg-gradient-to-r from-white to-white/60 bg-clip-text text-transparent">WMA HUB</h1>
                    <p class="text-[10px] text-orange-500 font-bold uppercase tracking-widest">We move, WMAFam</p>
                </div>
            </div>

            <nav class="flex-1">
                <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
                    <i class="fas fa-th-large"></i>
                    <span class="font-medium">Tableau de bord</span>
                </a>
                <a href="submit.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'submit.php' ? 'active' : '' ?>">
                    <i class="fas fa-plus-circle"></i>
                    <span class="font-medium">Soumettre</span>
                </a>
                <a href="services.php" class="nav-link <?= strpos(basename($_SERVER['PHP_SELF']), 'services') !== false ? 'active' : '' ?>">
                    <i class="fas fa-magic"></i>
                    <span class="font-medium">Services</span>
                </a>
                <a href="notifications.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'notifications.php' ? 'active' : '' ?>">
                    <i class="fas fa-bell"></i>
                    <span class="font-medium">Notifications</span>
                </a>
                <a href="catalogue.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'catalogue.php' ? 'active' : '' ?>">
                    <i class="fas fa-music"></i>
                    <span class="font-medium">Catalogue</span>
                </a>
                <a href="stats.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'stats.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-line"></i>
                    <span class="font-medium">Stats</span>
                </a>
                <a href="reviews.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'reviews.php' ? 'active' : '' ?>">
                    <i class="fas fa-star"></i>
                    <span class="font-medium">Laisser un avis</span>
                </a>
                <a href="revenues.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'revenues.php' ? 'active' : '' ?>">
                    <i class="fas fa-wallet"></i>
                    <span class="font-medium">Revenus</span>
                </a>
                <a href="contrat.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'contrat.php' ? 'active' : '' ?>">
                    <i class="fas fa-file-contract"></i>
                    <span class="font-medium">Contrat</span>
                </a>
            </nav>

            <div class="mt-auto pt-6 border-t border-white/5">
                <div class="flex items-center gap-3 p-3 rounded-2xl bg-white/[0.03] border border-white/5 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-orange-500 flex items-center justify-center text-white shadow-lg shadow-orange-500/20">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="flex-1 overflow-hidden">
                        <p class="text-sm font-bold truncate"><?= htmlspecialchars($_SESSION['user_name']) ?></p>
                        <p class="text-[10px] text-gray-500 uppercase font-black">Artiste</p>
                    </div>
                </div>
                <a href="../../auth/logout.php" class="nav-link text-red-500 hover:bg-red-500/10 mb-0">
                    <i class="fas fa-power-off"></i>
                    <span class="font-medium">Déconnexion</span>
                </a>
            </div>
        </aside>

        <main class="flex-1 p-6 lg:p-12">
            <header class="flex items-center justify-between mb-12">
                <div>
                    <h2 class="text-4xl font-black tracking-tighter mb-2">Vos <span class="text-orange-500">Notifications</span></h2>
                    <p class="text-gray-500 font-medium tracking-tight">Suivez l'état de vos projets et de vos revenus.</p>
                </div>
                <?php include '../../includes/header_notifications.php'; ?>
            </header>

            <div class="glass-card overflow-hidden shadow-2xl" id="fullNotifList">
                <div class="p-20 text-center"><i class="fas fa-spinner fa-spin text-3xl text-orange-500"></i></div>
            </div>
        </main>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const toggle = document.getElementById('sidebarToggle');

        function toggleSidebar() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('hidden');
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
                        list.innerHTML = '<div class="p-20 text-center text-gray-500 font-medium uppercase tracking-widest text-[10px]">Aucune notification à afficher</div>';
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
                                ${!n.is_read ? `<button onclick="markRead(${n.id})" class="px-4 py-2 text-[10px] font-black text-orange-500 bg-orange-500/10 rounded-full uppercase tracking-widest hover:bg-orange-500 hover:text-white transition-all">Marquer lu</button>` : ''}
                            </div>
                        `).join('');
                    }
                }
            } catch (e) {
                list.innerHTML = '<div class="p-20 text-center text-red-500 font-bold uppercase tracking-widest text-[10px]">Erreur de chargement</div>';
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

        window.addEventListener('load', () => {
            const loader = document.getElementById('wma-global-loader');
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(() => loader.style.display = 'none', 500);
            }
            loadFullHistory();
        });
    </script>
</body>
</html>
