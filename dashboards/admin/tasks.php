<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint à l'admin uniquement
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = getDBConnection();

// Traitement de l'ajout de tâche
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_task'])) {
    $title = $_POST['task_title'] ?? '';
    $employee_id = $_POST['employee_id'] ?? 0;
    $description = $_POST['task_desc'] ?? '';
    $image_path = null;

    if (isset($_FILES['task_image']) && $_FILES['task_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['task_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('task_') . '.' . $ext;
        $upload_dir = __DIR__ . '/../../uploads/tasks/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        move_uploaded_file($_FILES['task_image']['tmp_name'], $upload_dir . $filename);
        $image_path = 'uploads/tasks/' . $filename;
    }

    if ($title && $employee_id) {
        $stmt = $db->prepare("INSERT INTO tasks (user_id, title, description, image_path) VALUES (?, ?, ?, ?)");
        $stmt->execute([$employee_id, $title, $description, $image_path]);
        $taskId = $db->lastInsertId();

        // Notifier l'employé
        createNotification($employee_id, 'new_task', "Une nouvelle mission vous a été attribuée : $title", $taskId);

        // --- ENVOI D'EMAIL À L'EMPLOYÉ ---
        $stmt_emp = $db->prepare("SELECT email, name FROM users WHERE id = ?");
        $stmt_emp->execute([$employee_id]);
        $emp_info = $stmt_emp->fetch();

        if ($emp_info && $emp_info['email']) {
            $to = $emp_info['email'];
            $subject = "🚀 Nouvelle mission attribuée : " . $title;
            $task_url = "https://wmahub.com/dashboards/admin/task_chat.php?id=" . $taskId;
            
            $message = "
            <html>
            <head><title>Nouvelle mission</title></head>
            <body style='font-family: sans-serif;'>
                <h2>Bonjour " . htmlspecialchars($emp_info['name']) . ",</h2>
                <p>Une nouvelle mission vous a été attribuée sur WMA HUB.</p>
                <p><strong>Mission :</strong> " . htmlspecialchars($title) . "</p>
                <hr>
                <p><a href='$task_url' style='background: #ff6600; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>Voir les détails et commencer</a></p>
                <p style='font-size: 12px; color: #666; margin-top: 20px;'>Ceci est une notification automatique, merci de ne pas y répondre.</p>
            </body>
            </html>
            ";
            
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: WMA HUB <noreply@wmahub.com>" . "\r\n";

            @mail($to, $subject, $message, $headers);
        }
        // ----------------------------------
        
        $_SESSION['success_message'] = "Mission attribuée avec succès !";
        header('Location: tasks.php');
        exit;
    }
}

// Récupérer les filtres
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$filter_employee = isset($_GET['employee']) ? (int)$_GET['employee'] : 0;

// Construire la requête avec filtres
$query = "SELECT t.*, u.name as employee_name, u.email as employee_email 
          FROM tasks t 
          JOIN users u ON t.user_id = u.id 
          WHERE 1=1";

$params = [];

if ($filter_status !== 'all') {
    $query .= " AND t.status = ?";
    $params[] = $filter_status;
}

if ($filter_employee > 0) {
    $query .= " AND t.user_id = ?";
    $params[] = $filter_employee;
}

$query .= " ORDER BY t.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$all_tasks = $stmt->fetchAll();

// Séparer les tâches actives et terminées
$active_tasks = array_filter($all_tasks, fn($t) => $t['status'] !== 'termine');
$completed_tasks = array_filter($all_tasks, fn($t) => $t['status'] === 'termine');

// Stats (calculées sur l'échantillon filtré par la barre de recherche si applicable, ou globalement)
$total_tasks = count($all_tasks);
$in_progress = count(array_filter($all_tasks, fn($t) => $t['status'] === 'en_cours'));
$completed_count = count($completed_tasks);

// Récupérer tous les employés pour le filtre
$employees = $db->query("SELECT id, name FROM users WHERE role = 'employe' AND is_active = 1 ORDER BY name")->fetchAll();

// Statistiques
$total_tasks = count($all_tasks);
$in_progress = count(array_filter($all_tasks, fn($t) => $t['status'] === 'en_cours'));
$completed = $completed_count;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMA HUB - Gestion Tâches</title>
    <link rel="icon" type="image/png" href="../../asset/icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; min-height: 100vh; }
        .bg-glow { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .glow-spot { position: fixed; width: 40vw; height: 40vw; background: radial-gradient(circle, rgba(255, 102, 0, 0.05) 0%, transparent 70%); border-radius: 50%; z-index: -1; filter: blur(80px); pointer-events: none; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; display: flex; flex-direction: column; padding: 2rem 1.5rem; }
        .main-content { margin-left: 280px; padding: 2rem; min-height: 100vh; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; font-size: 0.9rem; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 1.5rem; padding: 1.5rem; }
        .custom-select, .search-bar { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 0.75rem; color: #fff; padding: 0.5rem 1rem; outline: none; }
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
        <header class="mb-12 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <h2 class="text-4xl font-black tracking-tighter text-white">Gestion des <span class="text-orange-500">Tâches</span></h2>
                <p class="text-gray-400 mt-2">Suivez toutes les tâches attribuées aux employés.</p>
            </div>
            <button onclick="document.getElementById('taskModal').classList.remove('hidden')" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-8 rounded-xl transition-all flex items-center gap-3 shadow-lg shadow-orange-500/20">
                <i class="fas fa-plus-circle"></i>
                Nouvelle Mission
            </button>
        </header>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="mb-8 p-4 bg-green-500/10 border border-green-500/20 rounded-xl flex items-center gap-3 animate-slide-up">
                <i class="fas fa-check-circle text-green-500"></i>
                <p class="text-green-500 font-bold"><?= $_SESSION['success_message'] ?></p>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <!-- Filtres -->
        <div class="glass-card mb-8">
            <form method="GET" class="flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-[200px]">
                    <label class="text-[10px] font-black uppercase text-gray-500 mb-2 block">Statut</label>
                    <select name="status" class="custom-select w-full">
                        <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>Tous les statuts</option>
                        <option value="en_cours" <?= $filter_status === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                        <option value="termine" <?= $filter_status === 'termine' ? 'selected' : '' ?>>Terminé</option>
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="text-[10px] font-black uppercase text-gray-500 mb-2 block">Employé</label>
                    <select name="employee" class="custom-select w-full">
                        <option value="0" <?= $filter_employee === 0 ? 'selected' : '' ?>>Tous les employés</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['id'] ?>" <?= $filter_employee === (int)$emp['id'] ? 'selected' : '' ?>><?= htmlspecialchars($emp['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-6 rounded-lg transition-all">
                    <i class="fas fa-filter mr-2"></i>Appliquer
                </button>
                <?php if ($filter_status !== 'all' || $filter_employee > 0): ?>
                    <a href="tasks.php" class="bg-white/5 hover:bg-white/10 text-white font-bold py-2 px-6 rounded-lg transition-all border border-white/10">
                        <i class="fas fa-times mr-2"></i>Réinitialiser
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Statistiques -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
            <div class="glass-card">
                <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest mb-2">Total Tâches</p>
                <p class="text-4xl font-black text-white"><?= $total_tasks ?></p>
            </div>
            <div class="glass-card border-amber-500/20 bg-amber-500/5">
                <p class="text-[10px] text-amber-500 font-black uppercase tracking-widest mb-2">En Cours</p>
                <p class="text-4xl font-black text-white"><?= $in_progress ?></p>
            </div>
            <div class="glass-card border-green-500/20 bg-green-500/5">
                <p class="text-[10px] text-green-500 font-black uppercase tracking-widest mb-2">Terminées</p>
                <p class="text-4xl font-black text-white"><?= $completed ?></p>
            </div>
        </div>

        <!-- Missions Actives -->
        <div class="mb-12">
            <div class="flex items-center gap-3 mb-6">
                <i class="fas fa-rocket text-orange-500"></i>
                <h3 class="text-xl font-bold">Missions en cours</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($active_tasks as $task): ?>
                    <div class="glass-card flex flex-col justify-between border-white/5 hover:border-orange-500/30 transition-all duration-300">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-[10px] font-black uppercase px-2 py-1 rounded bg-white/5 text-gray-400">
                                    <?= date('d/m/Y', strtotime($task['created_at'])) ?>
                                </span>
                                <?php 
                                    $statusClass = $task['status'] === 'en_cours' ? 'text-amber-500 bg-amber-500/10 border-amber-500/20' : 'text-blue-500 bg-blue-500/10 border-blue-500/20';
                                    $statusLabel = $task['status'] === 'en_cours' ? 'En cours' : 'Assignée';
                                ?>
                                <span class="text-[10px] font-black uppercase px-2 py-1 rounded border <?= $statusClass ?>">
                                    <?= $statusLabel ?>
                                </span>
                            </div>
                            <h4 class="font-bold text-lg mb-2 text-white"><?= htmlspecialchars($task['title']) ?></h4>
                            <p class="text-sm text-gray-500 line-clamp-3 mb-4"><?= htmlspecialchars($task['description']) ?></p>
                            
                            <div class="flex items-center gap-2 p-3 bg-white/5 rounded-lg mb-4">
                                <div class="w-8 h-8 rounded-full bg-orange-500/10 border border-orange-500/20 flex items-center justify-center text-orange-500 text-xs">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="overflow-hidden">
                                    <p class="text-xs font-bold text-white truncate"><?= htmlspecialchars($task['employee_name']) ?></p>
                                    <p class="text-[10px] text-gray-500 truncate"><?= htmlspecialchars($task['employee_email']) ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-auto flex gap-2">
                            <a href="task_chat.php?id=<?= $task['id'] ?>" class="flex-1 bg-white/5 hover:bg-white/10 text-white text-center py-2 rounded-lg text-[10px] font-black uppercase transition-all border border-white/10">
                                <i class="fas fa-comments mr-2"></i>Détails
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($active_tasks)): ?>
                    <div class="col-span-full py-20 text-center glass-card border-dashed">
                        <i class="fas fa-check-double text-4xl text-gray-800 mb-4 block"></i>
                        <p class="text-xs text-gray-500 uppercase font-black tracking-widest text-balance">Aucune mission active en ce moment</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Historique (Se affiche seulement si des tâches sont terminées) -->
        <?php if (!empty($completed_tasks)): ?>
        <section class="opacity-60 hover:opacity-100 transition-opacity duration-500">
            <div class="flex items-center gap-3 mb-6">
                <i class="fas fa-history text-gray-500"></i>
                <h3 class="text-xl font-bold">Historique des missions passées</h3>
            </div>
            <div class="glass-card p-0 overflow-hidden">
                <table class="w-full text-left">
                    <thead class="text-[10px] font-black uppercase text-gray-500 border-b border-white/5">
                        <tr>
                            <th class="px-8 py-4">Titre</th>
                            <th class="px-8 py-4">Employé</th>
                            <th class="px-8 py-4 text-center">Date Fin</th>
                            <th class="px-8 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php foreach ($completed_tasks as $task): ?>
                            <tr class="hover:bg-white/[0.02]">
                                <td class="px-8 py-4">
                                    <p class="text-sm font-bold text-white"><?= htmlspecialchars($task['title']) ?></p>
                                </td>
                                <td class="px-8 py-4">
                                    <p class="text-xs text-gray-400"><?= htmlspecialchars($task['employee_name']) ?></p>
                                </td>
                                <td class="px-8 py-4 text-center">
                                    <span class="text-[10px] text-gray-600 font-bold"><?= date('d/m/Y', strtotime($task['created_at'])) ?></span>
                                </td>
                                <td class="px-8 py-4 text-right">
                                    <a href="task_chat.php?id=<?= $task['id'] ?>" class="text-[10px] font-black uppercase text-orange-500 hover:underline">Voir Archive</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <!-- Modal Nouvelle Mission -->
    <div id="taskModal" class="hidden fixed inset-0 z-[1000] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="this.parentElement.classList.add('hidden')"></div>
        <div class="glass-card w-full max-w-xl relative z-10 animate-scale-up">
            <div class="flex items-center justify-between mb-8">
                <h3 class="text-2xl font-black tracking-tighter text-white uppercase">Nouvelle <span class="text-orange-500">Mission</span></h3>
                <button onclick="document.getElementById('taskModal').classList.add('hidden')" class="text-gray-500 hover:text-white transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div>
                    <label class="text-[10px] font-black uppercase text-gray-500 mb-2 block tracking-widest">Choisir l'employé</label>
                    <select name="employee_id" required class="custom-select w-full pt-3 pb-3">
                        <option value="">Sélectionner un membre...</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="text-[10px] font-black uppercase text-gray-500 mb-2 block tracking-widest">Titre de la mission</label>
                    <input type="text" name="task_title" required class="search-bar w-full" placeholder="Ex: Publication réseaux sociaux">
                </div>
                
                <div>
                    <label class="text-[10px] font-black uppercase text-gray-500 mb-2 block tracking-widest">Description détaillée</label>
                    <textarea name="task_desc" rows="4" class="search-bar w-full resize-none" placeholder="Décrivez les objectifs..."></textarea>
                </div>
                
                <div>
                    <label class="text-[10px] font-black uppercase text-gray-500 mb-2 block tracking-widest">Illustration (optionnel)</label>
                    <input type="file" name="task_image" accept="image/*" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-white/5 file:text-white hover:file:bg-white/10 cursor-pointer">
                </div>
                
                <div class="pt-4 flex gap-4">
                    <button type="button" onclick="document.getElementById('taskModal').classList.add('hidden')" class="flex-1 bg-white/5 hover:bg-white/10 text-white font-bold py-4 rounded-xl transition-all border border-white/10">
                        Annuler
                    </button>
                    <button type="submit" name="add_task" class="flex-1 bg-orange-500 hover:bg-orange-600 text-white font-bold py-4 rounded-xl shadow-lg shadow-orange-500/20 transition-all">
                        Attribuer la mission
                    </button>
                </div>
            </form>
        </div>
    </div>
    </main>

    <style>
        @keyframes scaleUp { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        @keyframes slideUp { from { transform: translateY(10px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .animate-scale-up { animation: scaleUp 0.3s ease-out; }
        .animate-slide-up { animation: slideUp 0.4s ease-out; }
    </style>
    <script>
        const glow = document.getElementById('glow');
        document.addEventListener('mousemove', (e) => {
            glow.style.left = (e.clientX - 200) + 'px';
            glow.style.top = (e.clientY - 200) + 'px';
        });
    </script>
</body>
</html>
