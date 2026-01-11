<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint à l'admin uniquement
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = getDBConnection();

// Traitement des mises à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Activation d'un employé
    if (isset($_POST['activate_user'])) {
        $stmt = $db->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
        $stmt->execute([$_POST['user_id']]);
    }
    // Mise à jour du salaire
    if (isset($_POST['update_salary'])) {
        $stmt = $db->prepare("UPDATE users SET salary = ? WHERE id = ?");
        $stmt->execute([$_POST['salary'], $_POST['user_id']]);
    }
    // Ajout d'une tâche
    if (isset($_POST['add_task'])) {
        $image_path = null;
        if (isset($_FILES['task_image']) && $_FILES['task_image']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['task_image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('task_') . '.' . $ext;
            $upload_dir = __DIR__ . '/../../uploads/tasks/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            move_uploaded_file($_FILES['task_image']['tmp_name'], $upload_dir . $filename);
            $image_path = 'uploads/tasks/' . $filename;
        }
        $stmt = $db->prepare("INSERT INTO tasks (user_id, title, description, image_path) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['employee_id'], $_POST['task_title'], $_POST['task_desc'], $image_path]);
        $taskId = $db->lastInsertId();

        // Notifier l'employé
        $notifStmt = $db->prepare("INSERT INTO notifications (user_id, type, reference_id) VALUES (?, 'new_task', ?)");
        $notifStmt->execute([$_POST['employee_id'], $taskId]);

        // --- ENVOI D'EMAIL À L'EMPLOYÉ ---
        $stmt_emp = $db->prepare("SELECT email, name FROM users WHERE id = ?");
        $stmt_emp->execute([$_POST['employee_id']]);
        $emp_info = $stmt_emp->fetch();

        if ($emp_info && $emp_info['email']) {
            $to = $emp_info['email'];
            $subject = "🚀 Nouvelle mission attribuée : " . $_POST['task_title'];
            $task_url = "https://wmahub.com/dashboards/admin/task_chat.php?id=" . $taskId;
            
            $message = "
            <html>
            <head><title>Nouvelle mission</title></head>
            <body style='font-family: sans-serif;'>
                <h2>Bonjour " . htmlspecialchars($emp_info['name']) . ",</h2>
                <p>Une nouvelle mission vous a été attribuée sur WMA HUB.</p>
                <p><strong>Mission :</strong> " . htmlspecialchars($_POST['task_title']) . "</p>
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
    }
    // Suppression d'une tâche
    if (isset($_POST['delete_task'])) {
        $stmt = $db->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->execute([$_POST['task_id']]);
    }
    header('Location: employees.php');
    exit;
}

// Récupérer les employés en attente
$pending_users = $db->query("SELECT * FROM users WHERE role = 'employe' AND is_active = 0")->fetchAll();

// Récupérer tous les employés actifs
$active_employees = $db->query("SELECT * FROM users WHERE role = 'employe' AND is_active = 1")->fetchAll();

// Récupérer les tâches en cours
$active_tasks = $db->query("SELECT t.*, u.name as employee_name FROM tasks t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMA HUB - Gestion Staff</title>
    <link rel="icon" type="image/png" href="../../asset/icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
            background: #0a0a0c;
            color: #fff;
            min-height: 100vh;
        }
        .bg-glow {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%);
            z-index: -1;
        }
        .glow-spot {
            position: fixed;
            width: 40vw; height: 40vw;
            background: radial-gradient(circle, rgba(255, 102, 0, 0.05) 0%, transparent 70%);
            border-radius: 50%;
            z-index: -1; filter: blur(80px); pointer-events: none;
        }
        .sidebar {
            width: 280px;
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            height: 100vh;
            position: fixed;
            left: 0; top: 0; z-index: 100;
            display: flex; flex-direction: column; padding: 2rem 1.5rem;
        }
        .main-content { margin-left: 280px; padding: 2rem; min-height: 100vh; }
        .nav-link {
            display: flex; align-items: center; gap: 1rem;
            padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4);
            border-radius: 1rem; font-weight: 500; transition: all 0.3s ease;
            margin-bottom: 0.5rem; text-decoration: none; font-size: 0.9rem;
        }
        .nav-link:hover, .nav-link.active {
            background: rgba(255, 102, 0, 0.1); color: #ff6600;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1.5rem; padding: 1.5rem;
        }
        .custom-select, .search-bar {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem; color: #fff;
            padding: 0.5rem 1rem; outline: none;
        }
        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); width: 0; padding: 0; }
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
        <header class="mb-12">
            <h2 class="text-4xl font-black tracking-tighter text-white">Gestion de l'<span class="text-orange-500">Équipe</span></h2>
            <p class="text-gray-400 mt-2">Activez les membres, gérez les salaires et assignez des tâches.</p>
        </header>

        <?php if (!empty($pending_users)): ?>
            <!-- Nouveaux Membres -->
            <section class="mb-12">
                <h3 class="text-xl font-bold mb-6 flex items-center gap-2">
                    <i class="fas fa-user-plus text-orange-500"></i>
                    Attente d'Activation
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <?php foreach ($pending_users as $user): ?>
                        <div class="glass-card flex items-center justify-between">
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

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Revenus -->
            <section class="glass-card">
                <h3 class="text-lg font-bold mb-6 uppercase tracking-widest text-gray-500">Revenus & Staff Actif</h3>
                <div class="space-y-4">
                    <?php foreach ($active_employees as $emp): ?>
                        <div class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/5">
                            <div>
                                <p class="font-bold text-sm"><?= htmlspecialchars($emp['name']) ?></p>
                                <p class="text-[10px] text-gray-500"><?= htmlspecialchars($emp['email']) ?></p>
                            </div>
                            <form method="POST" class="flex items-center gap-3">
                                <input type="hidden" name="user_id" value="<?= $emp['id'] ?>">
                                <div class="relative">
                                    <input type="number" name="salary" value="<?= $emp['salary'] ?>" class="custom-select !w-32 !pr-8" step="0.01">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 text-[10px]">$</span>
                                </div>
                                <button type="submit" name="update_salary" class="p-2 bg-orange-500/10 text-orange-500 rounded-lg">
                                    <i class="fas fa-save"></i>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Tâches -->
            <section class="glass-card">
                <h3 class="text-lg font-bold mb-6 uppercase tracking-widest text-gray-500">Assigner une Mission</h3>
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black uppercase text-gray-500 mb-1 block">Employé</label>
                            <select name="employee_id" required class="custom-select w-full">
                                <?php foreach ($active_employees as $emp): ?>
                                    <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-gray-500 mb-1 block">Titre</label>
                            <input type="text" name="task_title" required class="search-bar w-full" placeholder="Ex: Update social media">
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase text-gray-500 mb-1 block">Description</label>
                        <textarea name="task_desc" class="search-bar w-full h-24 resize-none" placeholder="Détails..."></textarea>
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase text-gray-500 mb-1 block">Image d'illustration (optionnel)</label>
                        <input type="file" name="task_image" accept="image/*" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-white/5 file:text-white hover:file:bg-white/10 cursor-pointer">
                    </div>
                    <button type="submit" name="add_task" class="w-full bg-white/5 hover:bg-white/10 text-white font-bold py-3 rounded-xl border border-white/10">
                        Lancer la tâche
                    </button>
                </form>
            </section>
        </div>

        <!-- Liste des tâches -->
        <section class="mt-12">
            <h3 class="text-xl font-bold mb-6 flex items-center gap-2">
                <i class="fas fa-clipboard-list text-orange-500"></i>
                Suivi des Missions
            </h3>
            <div class="glass-card p-0 overflow-hidden">
                <table class="admin-table w-full text-left">
                    <thead class="text-[10px] text-gray-500 uppercase tracking-widest">
                        <tr>
                            <th class="px-6 py-4 border-b border-white/5">Employé</th>
                            <th class="px-6 py-4 border-b border-white/5">Mission</th>
                            <th class="px-6 py-4 border-b border-white/5">Statut</th>
                            <th class="px-6 py-4 border-b border-white/5">Date</th>
                            <th class="px-6 py-4 border-b border-white/5">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($active_tasks as $task): ?>
                            <tr class="border-b border-white/5">
                                <td class="px-6 py-4 text-xs font-bold"><?= htmlspecialchars($task['employee_name']) ?></td>
                                <td class="px-6 py-4">
                                    <p class="text-xs font-bold text-white"><?= htmlspecialchars($task['title']) ?></p>
                                    <p class="text-[10px] text-gray-500"><?= htmlspecialchars($task['description']) ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-[9px] font-black uppercase px-2 py-1 rounded <?= $task['status'] === 'termine' ? 'bg-green-500/10 text-green-500' : 'bg-amber-500/10 text-amber-500' ?>">
                                        <?= str_replace('_', ' ', $task['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-[10px] text-gray-500"><?= date('d/m/Y', strtotime($task['created_at'])) ?></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <a href="task_chat.php?id=<?= $task['id'] ?>" class="text-orange-500 hover:text-orange-600 p-2 bg-orange-500/10 rounded-lg" title="Ouvrir le chat">
                                            <i class="fas fa-comments"></i>
                                        </a>
                                        <form method="POST">
                                            <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                            <button type="submit" name="delete_task" class="text-gray-600 hover:text-red-500 p-2">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
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
