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
        $revenue = $_POST['revenue'] ?? 0;
        $stmt = $db->prepare("INSERT INTO tasks (user_id, title, description, image_path, revenue) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['employee_id'], $_POST['task_title'], $_POST['task_desc'], $image_path, $revenue]);
        $taskId = $db->lastInsertId();

        // Notifier l'employé
        $notifStmt = $db->prepare("INSERT INTO notifications (user_id, type, reference_id) VALUES (?, 'new_task', ?)");
        $notifStmt->execute([$_POST['employee_id'], $taskId]);

        // --- ENVOI D'EMAIL À L'EMPLOYÉ ---
        require_once __DIR__ . '/../../includes/mailer.php';
        $stmt_emp = $db->prepare("SELECT email, name FROM users WHERE id = ?");
        $stmt_emp->execute([$_POST['employee_id']]);
        $emp_info = $stmt_emp->fetch();

        if ($emp_info && $emp_info['email']) {
            notifyNewTask($emp_info['email'], $emp_info['name'], $_POST['task_title'], $taskId);
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
                                <div class="flex items-center gap-2">
                                    <p class="font-bold text-sm"><?= htmlspecialchars($emp['name']) ?></p>
                                    <?php
                                        // Calcul rapide du score pour l'affichage
                                        $stmt_score = $db->prepare("SELECT ( (SELECT COALESCE(AVG(rating),0) FROM tasks WHERE user_id = ? AND status='termine') + (SELECT COALESCE(AVG(rating),0) FROM evaluations WHERE employee_id = ?) ) / 2 as final");
                                        $stmt_score->execute([$emp['id'], $emp['id']]);
                                        $score = round($stmt_score->fetchColumn(), 1);
                                        if($score > 0):
                                    ?>
                                        <span class="text-[10px] bg-orange-500/10 text-orange-500 px-2 py-0.5 rounded-full font-bold">
                                            <i class="fas fa-star text-[8px]"></i> <?= $score ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
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
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black uppercase text-gray-500 mb-1 block">Description</label>
                            <textarea name="task_desc" class="search-bar w-full h-24 resize-none" placeholder="Détails..."></textarea>
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-gray-500 mb-1 block">Rémunération / Bonus ($)</label>
                            <input type="number" name="revenue" step="0.01" min="0" value="0" class="search-bar w-full" placeholder="0.00">
                        </div>
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
