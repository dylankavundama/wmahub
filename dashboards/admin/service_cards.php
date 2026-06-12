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
    
    <!-- Scripts et CSS Prioritaires -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="/asset/icon.png"><link rel="apple-touch-icon" href="/asset/icon.png">
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
        .glow-spot { position: fixed; width: 40vw; height: 40vw; background: radial-gradient(circle, rgba(255, 102, 0, 0.05) 0%, transparent 70%); border-radius: 50%; z-index: -1; filter: blur(80px); pointer-events: none; }
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
    <div id="glow" class="glow-spot"></div>

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
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const toggle = document.getElementById('sidebarToggle');

        function toggleSidebar() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        if (toggle) toggle.addEventListener('click', toggleSidebar);
        if (overlay) overlay.addEventListener('click', toggleSidebar);

        const glow = document.getElementById('glow');
        document.addEventListener('mousemove', (e) => {
            if (glow) {
                glow.style.left = (e.clientX - 200) + 'px';
                glow.style.top = (e.clientY - 200) + 'px';
            }
        });

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
