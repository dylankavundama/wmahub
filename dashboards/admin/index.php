<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint à l'admin uniquement
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

// Traitement des mises à jour de statut
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_project_status'])) {
        $stmt = $db->prepare("UPDATE projects SET status = ? WHERE id = ?");
        $stmt->execute([$_POST['status'], $_POST['project_id']]);
    }
    if (isset($_POST['update_payment_status'])) {
        $stmt = $db->prepare("UPDATE projects SET payment_status = ? WHERE id = ?");
        $stmt->execute([$_POST['payment_status'], $_POST['project_id']]);
    }
    if (isset($_POST['activate_user'])) {
        $stmt = $db->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
        $stmt->execute([$_POST['user_id']]);
    }
    // Simple redirect to avoid form resubmission
    header('Location: index.php');
    exit;
}

// Récupérer tous les projets
$projects = $db->query("SELECT p.*, u.name as user_name, u.email as user_email FROM projects p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC")->fetchAll();

// Stats calculation
$total_projects = count($projects);
$distributed_count = count(array_filter($projects, fn($p) => $p['status'] === 'distribue'));
$pending_payment = count(array_filter($projects, fn($p) => $p['payment_status'] !== 'paye'));
$total_revenue = 0;
foreach($projects as $p) {
    if($p['payment_status'] === 'paye') {
        $price = match($p['promo_pack']) {
            'Starter' => 50,
            'Standard' => 90,
            'Pro' => 150,
            'Premium' => 350,
            default => 0
        };
        $total_revenue += $price;
    }
}

// Récupérer les employés en attente
$pending_users = $db->query("SELECT * FROM users WHERE role = 'employe' AND is_active = 0")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMA HUB - Administration</title>
    <link rel="icon" type="image/png" href="../../asset/icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
            margin: 0;
            background: #0a0a0c;
            color: #fff;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Animated Background */
        .bg-glow {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%);
            z-index: -1;
        }

        .glow-spot {
            position: fixed;
            width: 40vw;
            height: 40vw;
            background: radial-gradient(circle, rgba(255, 102, 0, 0.05) 0%, transparent 70%);
            border-radius: 50%;
            z-index: -1;
            filter: blur(80px);
            pointer-events: none;
        }

        /* Glassmorphism Sidebar */
        .sidebar {
            width: 280px;
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 100;
            display: flex;
            flex-direction: column;
            padding: 2rem 1.5rem;
        }

        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.25rem;
            color: rgba(255, 255, 255, 0.4);
            border-radius: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-bottom: 0.5rem;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(255, 102, 0, 0.1);
            color: #ff6600;
            transform: translateX(5px);
        }

        /* Glass Cards */
        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1.5rem;
            padding: 1.5rem;
            transition: all 0.4s ease;
        }

        .glass-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 102, 0, 0.3);
            background: rgba(255, 255, 255, 0.05);
        }

        /* Table Styles */
        .admin-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 0.75rem;
        }

        .admin-table tr {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.2s ease;
        }

        .admin-table tr:hover {
            background: rgba(255, 255, 255, 0.04);
            transform: scale(1.002);
        }

        .admin-table td {
            padding: 1.25rem 1.5rem;
            vertical-align: middle;
        }

        .admin-table th {
            padding: 1rem 1.5rem;
            text-transform: uppercase;
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 1.5px;
            color: rgba(255, 255, 255, 0.3);
        }

        /* Select styling */
        .custom-select {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            color: #fff;
            font-size: 0.75rem;
            padding: 0.5rem 1rem;
            outline: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .custom-select:hover {
            border-color: #ff6600;
            background: rgba(255, 102, 0, 0.1);
        }

        .badge-status {
            padding: 0.4rem 0.8rem;
            border-radius: 99px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        /* Search input */
        .search-bar {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 0.8rem 1.5rem;
            color: #fff;
            width: 300px;
            outline: none;
            transition: all 0.3s ease;
        }

        .search-bar:focus {
            border-color: #ff6600;
            background: rgba(255, 102, 0, 0.05);
            width: 350px;
        }

        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); width: 0; padding: 0; border: none; }
            .main-content { margin-left: 0; }
        }
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
            <a href="index.php" class="nav-link active">
                <i class="fas fa-layer-group"></i>
                Gestion Projets
            </a>
            <a href="#" class="nav-link">
                <i class="fas fa-users-cog"></i>
                Équipe & Staff
            </a>
            <a href="#" class="nav-link">
                <i class="fas fa-chart-pie"></i>
                Rapports Financiers
            </a>
        </nav>

        <div class="mt-auto pt-6 border-t border-white/5">
            <div class="flex items-center gap-4 mb-8 px-2">
                <div class="w-10 h-10 rounded-full bg-orange-500/10 border border-orange-500/20 flex items-center justify-center text-orange-500">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div>
                    <p class="text-sm font-bold text-white"><?= explode(' ', $_SESSION['user_name'])[0] ?></p>
                    <p class="text-[10px] text-gray-500 uppercase font-black tracking-widest"><?= $_SESSION['role'] ?></p>
                </div>
            </div>
            <a href="../../auth/logout.php" class="nav-link !text-red-500 hover:!bg-red-500/10">
                <i class="fas fa-power-off"></i>
                Déconnexion
            </a>
        </div>
    </aside>

    <main class="main-content">
        <header class="flex flex-col md:flex-row md:items-center justify-between gap-8 mb-12">
            <div>
                <h2 class="text-4xl font-black tracking-tighter text-white">Tableau de <span class="text-orange-500">Bord</span></h2>
                <p class="text-gray-400 mt-2">Gérez les demandes de distribution et le catalogue.</p>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="relative hidden md:block">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-600"></i>
                    <input type="text" id="tableSearch" class="search-bar pl-12" placeholder="Rechercher un projet, artiste...">
                </div>
                <button onclick="window.location.reload()" class="bg-white/5 hover:bg-white/10 text-white p-3 rounded-xl transition-all border border-white/10">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </header>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <div class="glass-card">
                <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest mb-2">Total Projets</p>
                <p class="text-4xl font-black text-white"><?= $total_projects ?></p>
                <div class="mt-4 flex items-center gap-2 text-xs text-orange-500/60 font-bold">
                    <i class="fas fa-chart-line"></i> <span>Activité globale</span>
                </div>
            </div>
            <div class="glass-card">
                <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest mb-2">Distributions</p>
                <div class="flex items-end justify-between">
                    <p class="text-4xl font-black text-white"><?= $distributed_count ?></p>
                    <span class="text-xs font-bold text-green-500 bg-green-500/10 px-2 py-1 rounded-lg">
                        <?= $total_projects ? round(($distributed_count/$total_projects)*100) : 0 ?>%
                    </span>
                </div>
            </div>
            <div class="glass-card">
                <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest mb-2">Paiements Attente</p>
                <p class="text-4xl font-black text-amber-500"><?= $pending_payment ?></p>
                <div class="mt-4 flex items-center gap-2 text-xs text-amber-500/60 font-bold">
                    <i class="fas fa-clock"></i> <span>À relancer</span>
                </div>
            </div>
            <div class="glass-card">
                <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest mb-2">Revenus Estimés</p>
                <p class="text-4xl font-black text-green-500"><?= number_format($total_revenue, 0, '.', ' ') ?>$</p>
                <div class="mt-4 flex items-center gap-2 text-xs text-green-500/60 font-bold">
                    <i class="fas fa-wallet"></i> <span>Total perçu</span>
                </div>
            </div>
        </div>

        <?php if (!empty($pending_users)): ?>
            <!-- New Team Members -->
            <section class="mb-12">
                <div class="flex items-center gap-3 mb-6">
                    <i class="fas fa-user-plus text-orange-500"></i>
                    <h3 class="text-xl font-bold">Nouveaux Employés en Attente</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($pending_users as $user): ?>
                        <div class="glass-card border-amber-500/20 bg-amber-500/5 flex items-center justify-between">
                            <div>
                                <p class="font-bold"><?= htmlspecialchars($user['name']) ?></p>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($user['email']) ?></p>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <button type="submit" name="activate_user" class="p-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Table Container -->
        <div class="glass-card p-0 overflow-hidden shadow-2xl border-white/5">
            <div class="px-8 py-6 border-b border-white/5 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <h3 class="text-lg font-bold flex items-center gap-3">
                    <i class="fas fa-compact-disc text-orange-500"></i>
                    Dernières demandes de distribution
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="admin-table text-left" id="projectsTable">
                    <thead>
                        <tr>
                            <th class="px-8">Projet & Artiste</th>
                            <th>Contact / Origine</th>
                            <th>Assets & Pack</th>
                            <th>Statut Distribution</th>
                            <th>État Paiement</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $project): ?>
                            <tr>
                                <td class="px-8">
                                    <div class="font-bold text-white"><?= htmlspecialchars($project['title']) ?></div>
                                    <div class="text-[10px] text-orange-500 font-black uppercase mt-1"><?= htmlspecialchars($project['artist_name'] ?: $project['user_name']) ?></div>
                                    <div class="text-[10px] text-gray-500 mt-1"><?= htmlspecialchars($project['type']) ?> • <?= htmlspecialchars($project['genre']) ?></div>
                                </td>
                                <td>
                                    <div class="text-xs font-medium"><?= htmlspecialchars($project['phone']) ?></div>
                                    <div class="text-[10px] text-gray-400"><?= htmlspecialchars($project['city']) ?></div>
                                    <div class="text-[10px] text-gray-600 truncate max-w-[150px]"><?= htmlspecialchars($project['user_email']) ?></div>
                                </td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <?php if ($project['audio_file']): ?>
                                            <a href="../artiste/uploads/<?= $project['audio_file'] ?>" target="_blank" class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center text-blue-500 hover:bg-blue-500 hover:text-white transition-all border border-blue-500/20" title="Écouter l'audio">
                                                <i class="fas fa-play text-[10px]"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($project['cover_file']): ?>
                                            <a href="../artiste/uploads/<?= $project['cover_file'] ?>" target="_blank" class="w-8 h-8 rounded-lg bg-purple-500/10 flex items-center justify-center text-purple-500 hover:bg-purple-500 hover:text-white transition-all border border-purple-500/20" title="Voir la pochette">
                                                <i class="fas fa-image text-[10px]"></i>
                                            </a>
                                        <?php endif; ?>
                                        <div class="text-[10px] font-black text-orange-500 uppercase bg-orange-500/10 px-2 py-1 rounded">
                                            Pack <?= htmlspecialchars($project['promo_pack']) ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                                        <select name="status" onchange="this.form.submit()" class="custom-select">
                                            <option value="en_attente" <?= $project['status'] === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                                            <option value="en_preparation" <?= $project['status'] === 'en_preparation' ? 'selected' : '' ?>>Préparation</option>
                                            <option value="distribue" <?= $project['status'] === 'distribue' ? 'selected' : '' ?>>Distribué</option>
                                        </select>
                                        <input type="hidden" name="update_project_status" value="1">
                                    </form>
                                </td>
                                <td>
                                    <form method="POST">
                                        <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                                        <select name="payment_status" onchange="this.form.submit()" class="custom-select <?= $project['payment_status'] === 'paye' ? '!border-green-500/30 !text-green-500' : '!border-amber-500/30 !text-amber-500' ?>">
                                            <option value="en_attente" <?= $project['payment_status'] === 'en_attente' ? 'selected' : '' ?>>Non payé</option>
                                            <option value="paye" <?= $project['payment_status'] === 'paye' ? 'selected' : '' ?>>Payé ✓</option>
                                        </select>
                                        <input type="hidden" name="update_payment_status" value="1">
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($projects)): ?>
                            <tr>
                                <td colspan="5" class="px-8 py-20 text-center text-gray-500 uppercase font-black tracking-widest text-xs">
                                    <i class="fas fa-folder-open text-4xl block mb-4 opacity-10"></i>
                                    Aucun projet à gérer
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        // Glow effect interaction
        const glow = document.getElementById('glow');
        document.addEventListener('mousemove', (e) => {
            const x = e.clientX;
            const y = e.clientY;
            glow.style.left = (x - glow.offsetWidth / 2) + 'px';
            glow.style.top = (y - glow.offsetHeight / 2) + 'px';
        });

        // Search Filter
        document.getElementById('tableSearch').addEventListener('keyup', function() {
            const value = this.value.toLowerCase();
            const rows = document.querySelectorAll('#projectsTable tbody tr');
            
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.indexOf(value) > -1 ? '' : 'none';
            });
        });
    </script>
</body>
</html>
