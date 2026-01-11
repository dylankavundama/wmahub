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

// Traitement des mises à jour de statut des tâches et encaissement de salaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_task_status'])) {
        $stmt = $db->prepare("UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$_POST['task_status'], $_POST['task_id'], $_SESSION['user_id']]);

        if ($_POST['task_status'] === 'termine') {
            // Notifier les admins via createNotification
            $admins = $db->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll();
            foreach ($admins as $admin) {
                createNotification($admin['id'], 'task_update', "L'employé " . $_SESSION['user_name'] . " a terminé la mission : " . $_POST['task_id'], $_POST['task_id']);
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
                // Enregistrer l'encaissement
                $stmt = $db->prepare("INSERT INTO salary_withdrawals (user_id, montant, date_encaissement) VALUES (?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $user['salary'], $current_date]);
                
                // Notifier les admins
                $admins = $db->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll();
                foreach ($admins as $admin) {
                    createNotification($admin['id'], 'payment_received', "Salaire encaissé par " . $_SESSION['user_name'] . " (" . $user['salary'] . "$)", null);
                }

                // Créer une dépense pour déduire de la caisse
                $motif = "Salaire - " . $_SESSION['user_name'];
                $stmt = $db->prepare("INSERT INTO expenses (project_id, motif, montant, date_depense) VALUES (NULL, ?, ?, ?)");
                $stmt->execute([$motif, $user['salary'], $current_date]);
                
                $_SESSION['success_message'] = "Salaire encaissé avec succès !";
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
        .custom-select { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 0.75rem; color: #fff; font-size: 0.75rem; padding: 0.5rem 1rem; outline: none; cursor: pointer; }
        @media (max-width: 1024px) { .sidebar { transform: translateX(-100%); width: 0; padding: 0; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <div class="bg-glow"></div>
    <div id="glow" class="glow-spot"></div>

    <aside class="sidebar">
        <div class="flex items-center gap-4 mb-12 px-2">
            <img src="../../asset/trans.png" alt="Logo" class="h-10">
            <h1 class="text-xl font-black bg-gradient-to-r from-orange-500 to-orange-300 bg-clip-text text-transparent tracking-tighter">WMA STAFF</h1>
        </div>
        <nav class="flex-1">
            <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>"><i class="fas fa-tasks"></i> Mes Missions</a>
            <a href="chat.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'chat.php' ? 'active' : '' ?>"><i class="fas fa-comments"></i> Chat Équipe</a>
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
                <p class="text-[10px] text-green-500 font-black uppercase tracking-widest mb-2">Revenu Mensuel</p>
                <p class="text-4xl font-black text-white mb-4"><?= number_format($user['salary'], 0, '.', ' ') ?>$</p>
                
                <?php if ($can_withdraw): ?>
                    <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir encaisser votre salaire de <?= number_format($user['salary'], 0, '.', ' ') ?>$ ?');">
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
        const glow = document.getElementById('glow');
        document.addEventListener('mousemove', (e) => { glow.style.left = (e.clientX - 200) + 'px'; glow.style.top = (e.clientY - 200) + 'px'; });
    </script>
</body>
</html>
