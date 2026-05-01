<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint aux employés
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employe') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = getDBConnection();

// Vérifier si l'employé est actif et récupérer ses infos (salaire)
$stmt = $db->prepare("SELECT is_active, salary FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
if (!$user || !$user['is_active']) {
    header('Location: ../../auth/pending.php');
    exit;
}

// Calculer les bonus en attente (missions terminées non encore payées)
$stmt_bonus = $db->prepare("SELECT SUM(revenue) FROM tasks WHERE user_id = ? AND status = 'termine' AND is_paid = 0");
$stmt_bonus->execute([$_SESSION['user_id']]);
$pending_bonuses = (float)($stmt_bonus->fetchColumn() ?: 0);
$total_monthly_revenue = (float)$user['salary'] + $pending_bonuses;

// Traitement des mises à jour de statut des tâches et encaissement de salaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_task_status'])) {
        $stmt = $db->prepare("UPDATE tasks SET status = ?, completed_at = CASE WHEN ? = 'termine' THEN CURRENT_TIMESTAMP ELSE completed_at END, rating = CASE WHEN ? = 'termine' AND rating IS NULL THEN 3 ELSE rating END WHERE id = ? AND user_id = ?");
        $stmt->execute([$_POST['task_status'], $_POST['task_status'], $_POST['task_status'], $_POST['task_id'], $_SESSION['user_id']]);

        if ($_POST['task_status'] === 'termine') {
            // Notifier les admins via createNotification
            $admins = $db->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll();
            require_once __DIR__ . '/../../includes/mailer.php';
            foreach ($admins as $admin) {
                createNotification($admin['id'], 'task_update', "L'employé " . $_SESSION['user_name'] . " a terminé la mission : " . $_POST['task_id'], $_POST['task_id']);
                
                // --- ENVOI D'EMAIL AUX ADMINS ---
                $stmt_task = $db->prepare("SELECT title FROM tasks WHERE id = ?");
                $stmt_task->execute([$_POST['task_id']]);
                $task_title = $stmt_task->fetchColumn();
                
                notifyAdmin('employee', "Mission terminée par " . $_SESSION['user_name'], [
                    'Mission' => $task_title,
                    'Employé' => $_SESSION['user_name'],
                    'Status' => 'TERMINÉ ✓'
                ], "https://wmahub.com/dashboards/admin/task_chat.php?id=" . $_POST['task_id']);
            }
        }
    }
    
    // Traitement de l'encaissement du salaire
    if (isset($_POST['encaisser_salaire'])) {
        $current_date = date('Y-m-d');
        $current_day = (int)date('d');
        
        // Vérifier que c'est le 28 du mois
        if ($current_day === 28) {
            // Vérifier si l'employé a déjà encaissé ce mois
            $current_month = date('Y-m');
            $stmt = $db->prepare("SELECT id FROM salary_withdrawals WHERE user_id = ? AND DATE_FORMAT(date_encaissement, '%Y-%m') = ?");
            $stmt->execute([$_SESSION['user_id'], $current_month]);
            $already_withdrawn = $stmt->fetch();
            
            if (!$already_withdrawn) {
                // Enregistrer l'encaissement (Salaire + Bonus)
                $stmt = $db->prepare("INSERT INTO salary_withdrawals (user_id, montant, date_encaissement) VALUES (?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $total_monthly_revenue, $current_date]);
                
                // Marquer les tâches comme payées
                $stmt_pay_tasks = $db->prepare("UPDATE tasks SET is_paid = 1 WHERE user_id = ? AND status = 'termine' AND is_paid = 0");
                $stmt_pay_tasks->execute([$_SESSION['user_id']]);

                // Notifier les admins
                $admins = $db->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll();
                foreach ($admins as $admin) {
                    createNotification($admin['id'], 'payment_received', "Salaire encaissé par " . $_SESSION['user_name'] . " (" . number_format($total_monthly_revenue, 2) . "$ dont " . number_format($pending_bonuses, 2) . "$ de bonus)", null);
                }

                // Créer une dépense pour déduire de la caisse
                $motif = "Salaire & Bonus - " . $_SESSION['user_name'];
                $stmt = $db->prepare("INSERT INTO expenses (project_id, motif, montant, date_depense) VALUES (NULL, ?, ?, ?)");
                $stmt->execute([$motif, $total_monthly_revenue, $current_date]);
                
                $_SESSION['success_message'] = "Salaire et bonus encaissés avec succès !";
            } else {
                $_SESSION['error_message'] = "Vous avez déjà encaissé votre salaire ce mois.";
            }
        } else {
            $_SESSION['error_message'] = "L'encaissement n'est disponible que le 28 de chaque mois.";
        }
    }
    
    header('Location: index.php');
    exit;
}

// Récupérer les tâches de l'employé
$stmt = $db->prepare("SELECT * FROM tasks WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$my_tasks = $stmt->fetchAll();

// Récupérer l'historique des encaissements
$stmt = $db->prepare("SELECT * FROM salary_withdrawals WHERE user_id = ? ORDER BY date_encaissement DESC");
$stmt->execute([$_SESSION['user_id']]);
$withdrawal_history = $stmt->fetchAll();

// Stats calculation (Uniquement salaire ici, ou tâches)
$in_progress_tasks = count(array_filter($my_tasks, fn($t) => $t['status'] === 'en_cours'));
$completed_tasks = count(array_filter($my_tasks, fn($t) => $t['status'] === 'termine'));

// Vérifier si l'employé peut encaisser son salaire
$can_withdraw = false;
$withdrawal_message = "";
$current_day = (int)date('d');
$current_month = date('Y-m');

if ($current_day === 28) {
    // Vérifier si déjà encaissé ce mois
    $stmt = $db->prepare("SELECT id FROM salary_withdrawals WHERE user_id = ? AND DATE_FORMAT(date_encaissement, '%Y-%m') = ?");
    $stmt->execute([$_SESSION['user_id'], $current_month]);
    $already_withdrawn = $stmt->fetch();
    
    if ($already_withdrawn) {
        $withdrawal_message = "Salaire déjà encaissé ce mois";
    } else {
        $can_withdraw = true;
    }
} else {
    $withdrawal_message = "Disponible le 28 du mois";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMA HUB - Espace Employé</title>
    <link rel="icon" type="image/png" href="/asset/icon.png"><link rel="apple-touch-icon" href="/asset/icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8882238368661853"
     crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; min-height: 100vh; overflow-x: hidden; }
        .bg-glow { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .glow-spot { position: fixed; width: 40vw; height: 40vw; background: radial-gradient(circle, rgba(255, 102, 0, 0.05) 0%, transparent 70%); border-radius: 50%; z-index: -1; filter: blur(80px); pointer-events: none; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; display: flex; flex-direction: column; padding: 2rem 1.5rem; transition: all 0.3s ease; }
        .main-content { margin-left: 280px; padding: 2rem; min-height: 100vh; transition: all 0.3s ease; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; font-size: 0.9rem; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; transform: translateX(5px); }
        
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

        .glass-card { 
            background: rgba(255, 255, 255, 0.02); 
            backdrop-filter: blur(20px); 
            border: 1px solid rgba(255, 255, 255, 0.05); 
            border-radius: 1.5rem; 
            padding: 1.5rem; 
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .glass-card:hover { 
            background: rgba(255, 255, 255, 0.05); 
            transform: translateY(-5px); 
            border-color: rgba(255, 102, 0, 0.2); 
        }
        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 99px;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <div class="bg-glow"></div>
    <div id="glow" class="glow-spot"></div>

    <div class="mobile-header">
        <div class="flex items-center gap-3">
            <img src="../../asset/trans.png" alt="Logo" class="h-8">
            <span class="font-bold tracking-tighter">WMA STAFF</span>
        </div>
        <button id="sidebarToggle" class="text-white text-2xl p-2"><i class="fas fa-bars"></i></button>
    </div>

    <div class="sidebar-overlay" id="overlay"></div>

    <aside class="sidebar" id="sidebar">
        <div class="flex items-center gap-4 mb-12 px-2">
            <img src="../../asset/trans.png" alt="Logo" class="h-10">
            <div>
                <h1 class="text-xl font-black bg-gradient-to-r from-orange-500 to-orange-300 bg-clip-text text-transparent tracking-tighter leading-tight">WMA HUB</h1>
                <p class="text-[8px] text-gray-500 font-bold uppercase tracking-[1px] -mt-1">We move, WMAFam</p>
            </div>
        </div>
        <nav class="flex-1">
            <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="missions.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'missions.php' ? 'active' : '' ?>"><i class="fas fa-tasks"></i> Mes Missions</a>
            <a href="project_files.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'project_files.php' ? 'active' : '' ?>"><i class="fas fa-folder-open"></i> Fichier Projet</a>
            <a href="service_card.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'service_card.php' ? 'active' : '' ?>"><i class="fas fa-id-card"></i> Carte de Service</a>
            <a href="notifications.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'notifications.php' ? 'active' : '' ?>"><i class="fas fa-bell"></i> Notifications</a>
        </nav>
        <div class="mt-auto pt-6 border-t border-white/5">
            <div class="flex items-center gap-4 mb-8 px-2">
                <div class="w-10 h-10 rounded-full bg-orange-500/10 border border-orange-500/20 flex items-center justify-center text-orange-500"><i class="fas fa-user-cog"></i></div>
                <div>
                    <p class="text-sm font-bold text-white"><?= explode(' ', $_SESSION['user_name'])[0] ?></p>
                    <p class="text-[10px] text-gray-500 uppercase font-black tracking-widest">Employé</p>
                </div>
            </div>
            <a href="../../auth/logout.php" class="nav-link !text-red-500 hover:!bg-red-500/10"><i class="fas fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="flex flex-col md:flex-row md:items-center justify-between gap-8 mb-12">
            <div>
                <h2 class="text-4xl font-black tracking-tighter text-white">Espace <span class="text-orange-500">Employé</span></h2>
                <p class="text-gray-400 mt-2">Bienvenue sur votre tableau de bord personnel.</p>
            </div>
            <div class="flex items-center gap-4">
                <button onclick="window.location.reload()" class="bg-white/5 hover:bg-white/10 text-white p-3 rounded-xl border border-white/10 transition-all"><i class="fas fa-sync-alt"></i></button>
                <?php include '../../includes/header_notifications.php'; ?>
            </div>
        </header>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="mb-6 p-4 bg-green-500/10 border border-green-500/20 rounded-xl flex items-center gap-3">
                <i class="fas fa-check-circle text-green-500 text-xl"></i>
                <p class="text-green-500 font-bold"><?= $_SESSION['success_message'] ?></p>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-xl flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                <p class="text-red-500 font-bold"><?= $_SESSION['error_message'] ?></p>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <div class="glass-card border-green-500/20 bg-green-500/5">
                <p class="text-[10px] text-green-500 font-black uppercase tracking-widest mb-2">Revenu Mensuel Est.</p>
                <p class="text-4xl font-black text-white mb-1"><?= number_format($total_monthly_revenue, 2, '.', ' ') ?>$</p>
                <div class="flex flex-col gap-1 mb-4">
                    <p class="text-[9px] text-gray-400 font-bold">Fixe: <?= number_format($user['salary'], 0) ?>$</p>
                    <p class="text-[9px] text-orange-400 font-bold">Bonus: <?= number_format($pending_bonuses, 2) ?>$</p>
                </div>
                
                <?php if ($can_withdraw): ?>
                    <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir encaisser votre revenu de <?= number_format($total_monthly_revenue, 2, '.', ' ') ?>$ ?');">
                        <button type="submit" name="encaisser_salaire" class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg transition-all flex items-center justify-center gap-2">
                            <i class="fas fa-money-bill-wave"></i>
                            Encaisser
                        </button>
                    </form>
                <?php else: ?>
                    <div class="bg-white/5 text-gray-400 text-center py-2 px-4 rounded-lg border border-white/10">
                        <p class="text-[10px] font-bold uppercase tracking-wider"><?= $withdrawal_message ?></p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="glass-card">
                <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest mb-2">En cours</p>
                <p class="text-4xl font-black text-amber-500"><?= $in_progress_tasks ?></p>
            </div>
            <div class="glass-card">
                <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest mb-2">Terminées</p>
                <p class="text-4xl font-black text-green-500"><?= $completed_tasks ?></p>
            </div>
            <div class="glass-card opacity-50">
                <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest mb-2">Objectifs</p>
                <p class="text-4xl font-black text-white"><?= count($my_tasks) ?></p>
            </div>
        </div>
        <!-- Recent Missions -->
        <section class="mb-12">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <i class="fas fa-tasks text-orange-500"></i>
                    <h3 class="text-xl font-bold">Missions Récentes</h3>
                </div>
                <a href="missions.php" class="text-[10px] font-black uppercase text-orange-500 hover:text-orange-400 transition-all tracking-widest">Voir tout</a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php 
                $recent_tasks = array_slice($my_tasks, 0, 3);
                foreach ($recent_tasks as $task): 
                    $status_class = match($task['status']) {
                        'en_cours' => 'bg-amber-500/10 text-amber-500 border-amber-500/20',
                        'termine' => 'bg-green-500/10 text-green-500 border-green-500/20',
                        'suspendu' => 'bg-red-500/10 text-red-500 border-red-500/20',
                        default => 'bg-gray-500/10 text-gray-500 border-gray-500/20'
                    };
                ?>
                <div class="glass-card flex flex-col h-full">
                    <div class="flex justify-between items-start mb-4">
                        <span class="status-badge <?= $status_class ?> border">
                            <?= str_replace('_', ' ', $task['status']) ?>
                        </span>
                        <span class="text-[9px] text-gray-500 font-bold"><?= date('d/m', strtotime($task['created_at'])) ?></span>
                    </div>
                    <h4 class="font-bold text-white mb-2 line-clamp-1"><?= htmlspecialchars($task['title']) ?></h4>
                    <p class="text-xs text-gray-400 mb-6 line-clamp-2 leading-relaxed"><?= htmlspecialchars($task['description']) ?></p>
                    
                    <div class="mt-auto flex items-center justify-between pt-4 border-t border-white/5">
                        <div class="flex -space-x-2">
                            <div class="w-6 h-6 rounded-full bg-orange-500/20 border border-orange-500/30 flex items-center justify-center text-[8px] text-orange-500">W</div>
                        </div>
                        <a href="missions.php" class="p-2 bg-white/5 hover:bg-white/10 rounded-lg text-white transition-all">
                            <i class="fas fa-arrow-right text-[10px]"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($recent_tasks)): ?>
                    <div class="col-span-full glass-card py-12 text-center">
                        <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest">Aucune mission assignée</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Historique des Encaissements -->
        <section class="mb-12">
            <div class="flex items-center gap-3 mb-6">
                <i class="fas fa-history text-green-500"></i>
                <h3 class="text-xl font-bold">Historique des Encaissements</h3>
            </div>
            <div class="glass-card p-0 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="text-[10px] text-gray-500 uppercase tracking-widest border-b border-white/5">
                            <tr>
                                <th class="px-6 py-4">Date</th>
                                <th class="px-6 py-4">Montant</th>
                                <th class="px-6 py-4">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($withdrawal_history as $hist): ?>
                                <tr class="border-b border-white/5 hover:bg-white/2">
                                    <td class="px-6 py-4 text-sm font-medium">
                                        <?= date('d/m/Y', strtotime($hist['date_encaissement'])) ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-black text-green-500">
                                        <?= number_format($hist['montant'], 0, '.', ' ') ?>$
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-[9px] font-black uppercase px-2 py-1 rounded bg-green-500/10 text-green-500 border border-green-500/20">
                                            Payload Confirmé ✓
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($withdrawal_history)): ?>
                                <tr>
                                    <td colspan="3" class="px-6 py-12 text-center text-gray-500 uppercase font-black tracking-widest text-xs">
                                        Aucun historique pour le moment
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
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
    </script>
</body>
</html>
