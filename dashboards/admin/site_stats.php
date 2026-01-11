<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint aux admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = getDBConnection();

// 1. Statistiques des Visites
$today = date('Y-m-d');
$this_month = date('Y-m');
$this_year = date('Y');

$visits_today = $db->prepare("SELECT COUNT(*) FROM site_visits WHERE visit_date = ?");
$visits_today->execute([$today]);
$count_today = $visits_today->fetchColumn();

$visits_month = $db->prepare("SELECT COUNT(*) FROM site_visits WHERE DATE_FORMAT(visit_date, '%Y-%m') = ?");
$visits_month->execute([$this_month]);
$count_month = $visits_month->fetchColumn();

$visits_year = $db->prepare("SELECT COUNT(*) FROM site_visits WHERE DATE_FORMAT(visit_date, '%Y') = ?");
$visits_year->execute([$this_year]);
$count_year = $visits_year->fetchColumn();

// 2. Nombre total d'utilisateurs
$total_users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();

// 3. Journal des Connexions (10 dernières)
$recent_logins = $db->query("
    SELECT l.*, u.name, u.email 
    FROM login_logs l 
    JOIN users u ON l.user_id = u.id 
    ORDER BY l.login_date DESC 
    LIMIT 10
")->fetchAll();

// 4. Journal des Erreurs (10 dernières)
$recent_errors = $db->query("SELECT * FROM system_errors ORDER BY created_at DESC LIMIT 10")->fetchAll();

// 5. Pages les plus visitées
$top_pages = $db->query("
    SELECT page, COUNT(*) as count 
    FROM site_visits 
    GROUP BY page 
    ORDER BY count DESC 
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMA HUB - Statistiques Site</title>
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
            <a href="index.php" class="nav-link"><i class="fas fa-layer-group"></i> Gestion Projets</a>
            <a href="employees.php" class="nav-link"><i class="fas fa-users-cog"></i> Équipe & Staff</a>
            <a href="tasks.php" class="nav-link"><i class="fas fa-tasks"></i> Gestion Tâches</a>
            <a href="salaries.php" class="nav-link"><i class="fas fa-money-check-alt"></i> Gestion Salaires</a>
            <a href="chat.php" class="nav-link"><i class="fas fa-comments"></i> Chat Équipe</a>
            <a href="service_cards.php" class="nav-link"><i class="fas fa-id-card"></i> Cartes de Service</a>
            <a href="notifications.php" class="nav-link"><i class="fas fa-bell"></i> Notifications</a>
            <a href="finance.php" class="nav-link"><i class="fas fa-chart-pie"></i> Rapports Financiers</a>
            <a href="site_stats.php" class="nav-link active"><i class="fas fa-chart-line"></i> Statistiques Site</a>
            <a href="users.php" class="nav-link"><i class="fas fa-user-friends"></i> Utilisateurs</a>
        </nav>
        <div class="mt-auto pt-6 border-t border-white/5">
            <div class="flex items-center gap-4 mb-8 px-2">
                <div class="w-10 h-10 rounded-full bg-orange-500/10 border border-orange-500/20 flex items-center justify-center text-orange-500"><i class="fas fa-user-shield"></i></div>
                <div>
                    <p class="text-sm font-bold text-white"><?= explode(' ', $_SESSION['user_name'])[0] ?></p>
                    <p class="text-[10px] text-gray-400 uppercase font-black tracking-widest">Administrateur</p>
                </div>
            </div>
            <a href="../../auth/logout.php" class="nav-link !text-red-500 hover:!bg-red-500/10"><i class="fas fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="flex flex-col md:flex-row md:items-center justify-between gap-8 mb-12">
            <div>
                <h2 class="text-4xl font-black tracking-tighter text-white">Statistiques <span class="text-orange-500">Site</span></h2>
                <p class="text-gray-400 mt-2">Analyse du trafic et de la stabilité du système.</p>
            </div>
            <div class="flex items-center gap-4">
                <button onclick="window.location.reload()" class="bg-white/5 hover:bg-white/10 text-white p-3 rounded-xl border border-white/10 transition-all"><i class="fas fa-sync-alt"></i></button>
                <?php include '../../includes/header_notifications.php'; ?>
            </div>
        </header>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <div class="glass-card">
                <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest mb-2">Visites Aujourd'hui</p>
                <div class="flex items-end justify-between">
                    <p class="text-4xl font-black text-white"><?= $count_today ?></p>
                    <i class="fas fa-eye text-orange-500 text-2xl mb-1"></i>
                </div>
            </div>
            <div class="glass-card">
                <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest mb-2">Visites Ce Mois</p>
                <div class="flex items-end justify-between">
                    <p class="text-4xl font-black text-white"><?= $count_month ?></p>
                    <i class="fas fa-calendar-alt text-orange-500 text-2xl mb-1"></i>
                </div>
            </div>
            <div class="glass-card">
                <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest mb-2">Visites Cette Année</p>
                <div class="flex items-end justify-between">
                    <p class="text-4xl font-black text-white"><?= $count_year ?></p>
                    <i class="fas fa-globe text-orange-500 text-2xl mb-1"></i>
                </div>
            </div>
            <div class="glass-card">
                <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest mb-2">Utilisateurs Inscrits</p>
                <div class="flex items-end justify-between">
                    <p class="text-4xl font-black text-white"><?= $total_users ?></p>
                    <i class="fas fa-users text-orange-500 text-2xl mb-1"></i>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
            <!-- Journal des Connexions -->
            <section class="glass-card p-0 overflow-hidden">
                <div class="p-6 border-b border-white/5 flex items-center justify-between">
                    <h3 class="font-bold text-lg">Dernières Connexions</h3>
                    <i class="fas fa-sign-in-alt text-gray-500 text-sm"></i>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="text-[8px] text-gray-500 uppercase font-black border-b border-white/5">
                            <tr>
                                <th class="px-6 py-4">Utilisateur</th>
                                <th class="px-6 py-4">Date & Heure</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_logins as $login): ?>
                                <tr class="border-b border-white/5 hover:bg-white/2">
                                    <td class="px-6 py-4">
                                        <p class="text-xs font-bold text-white"><?= htmlspecialchars($login['name']) ?></p>
                                        <p class="text-[10px] text-gray-500"><?= htmlspecialchars($login['email']) ?></p>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-400">
                                        <?= date('d/m/Y H:i', strtotime($login['login_date'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Pages les plus visitées -->
            <section class="glass-card p-0 overflow-hidden">
                <div class="p-6 border-b border-white/5 flex items-center justify-between">
                    <h3 class="font-bold text-lg">Top Pages</h3>
                    <i class="fas fa-chart-bar text-gray-500 text-sm"></i>
                </div>
                <div class="p-6">
                    <?php foreach ($top_pages as $page): 
                        $pct = ($count_year > 0) ? round(($page['count'] / $count_year) * 100) : 0;
                    ?>
                        <div class="mb-6 last:mb-0">
                            <div class="flex justify-between items-center mb-2">
                                <p class="text-xs font-bold text-white truncate max-w-[70%]"><?= htmlspecialchars($page['page']) ?></p>
                                <p class="text-xs font-black text-orange-500"><?= number_format($page['count']) ?> v.</p>
                            </div>
                            <div class="h-1.5 w-full bg-white/5 rounded-full overflow-hidden">
                                <div class="h-full bg-orange-500 rounded-full" style="width: <?= $pct ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Journal des Erreurs -->
            <section class="glass-card p-0 overflow-hidden xl:col-span-2">
                <div class="p-6 border-b border-white/5 flex items-center justify-between">
                    <h3 class="font-bold text-lg">Journal des Erreurs Système</h3>
                    <span class="text-[10px] font-black uppercase px-2 py-1 rounded bg-red-500/10 text-red-500 border border-red-500/20">Logs Récents</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="text-[8px] text-gray-500 uppercase font-black border-b border-white/5">
                            <tr>
                                <th class="px-6 py-4">Message</th>
                                <th class="px-6 py-4">Fichier & Ligne</th>
                                <th class="px-6 py-4">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_errors as $err): ?>
                                <tr class="border-b border-white/5 hover:bg-white/2">
                                    <td class="px-6 py-4">
                                        <p class="text-xs font-medium text-red-400"><?= htmlspecialchars($err['message']) ?></p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-[10px] text-gray-400"><?= htmlspecialchars($err['file']) ?></p>
                                        <p class="text-[10px] font-bold text-white">Ligne <?= $err['line'] ?></p>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-500">
                                        <?= date('d/m/Y H:i', strtotime($err['created_at'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recent_errors)): ?>
                                <tr>
                                    <td colspan="3" class="px-6 py-12 text-center text-gray-500 uppercase font-black tracking-widest text-xs">
                                        Aucune erreur enregistrée ✓
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>

    <script>
        const glow = document.getElementById('glow');
        document.addEventListener('mousemove', (e) => {
            glow.style.left = (e.clientX - 200) + 'px';
            glow.style.top = (e.clientY - 200) + 'px';
        });
    </script>
</body>
</html>
