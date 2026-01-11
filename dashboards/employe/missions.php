<?php
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employe') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = getDBConnection();

// Traitement du changement de statut via POST (si besoin rapide)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $task_id = $_POST['task_id'];
    $new_status = $_POST['status'];
    
    $stmt = $db->prepare("UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$new_status, $task_id, $_SESSION['user_id']]);
    
    // Notification si terminé
    if ($new_status === 'termine') {
        $admins = $db->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll();
        foreach ($admins as $admin) {
            createNotification($admin['id'], 'task_update', "L'employé " . $_SESSION['user_name'] . " a terminé la mission : " . $task_id, $task_id);
        }
    }
    
    $_SESSION['success_message'] = "Statut mis à jour !";
    header("Location: missions.php");
    exit;
}

// Récupérer les tâches de l'employé
$stmt = $db->prepare("SELECT * FROM tasks WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$my_tasks = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMA STAFF - Mes Missions</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; min-height: 100vh; }
        .bg-glow { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; display: flex; flex-direction: column; padding: 2rem 1.5rem; transition: all 0.3s ease; z-index: 50; }
        .main-content { margin-left: 280px; padding: 3rem; transition: all 0.3s ease; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; font-size: 0.9rem; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; transform: translateX(5px); }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 1.5rem; padding: 1.5rem; }
        .status-badge { font-size: 10px; font-weight: 900; text-transform: uppercase; padding: 4px 12px; border-radius: 9999px; letter-spacing: 1px; }
        @media (max-width: 1024px) { .sidebar { transform: translateX(-100%); width: 0; padding: 0; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <div class="bg-glow"></div>

    <aside class="sidebar">
        <div class="flex items-center gap-4 mb-12 px-2">
            <img src="../../asset/trans.png" alt="Logo" class="h-10">
            <div>
                <h1 class="text-xl font-black bg-gradient-to-r from-orange-500 to-orange-300 bg-clip-text text-transparent tracking-tighter leading-tight">WMA HUB</h1>
                <p class="text-[8px] text-gray-500 font-bold uppercase tracking-[1px] -mt-1">We Farm Your Talent</p>
            </div>
        </div>
        <nav class="flex-1">
            <a href="index.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="missions.php" class="nav-link active"><i class="fas fa-tasks"></i> Mes Missions</a>
            <a href="chat.php" class="nav-link"><i class="fas fa-comments"></i> Chat Équipe</a>
            <a href="service_card.php" class="nav-link"><i class="fas fa-id-card"></i> Carte de Service</a>
            <a href="notifications.php" class="nav-link"><i class="fas fa-bell"></i> Notifications</a>
        </nav>
        <div class="mt-auto pt-6 border-t border-white/5">
            <a href="../../auth/logout.php" class="nav-link !text-red-500 hover:!bg-red-500/10"><i class="fas fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="flex flex-col md:flex-row md:items-center justify-between gap-8 mb-12">
            <div>
                <h2 class="text-4xl font-black tracking-tighter text-white">Mes <span class="text-orange-500">Missions</span></h2>
                <p class="text-gray-400 mt-2">Gérez vos tâches assignées et collaborez.</p>
            </div>
            <?php include '../../includes/header_notifications.php'; ?>
        </header>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="mb-6 p-4 bg-green-500/10 border border-green-500/20 rounded-xl flex items-center gap-3">
                <i class="fas fa-check-circle text-green-500 text-xl"></i>
                <p class="text-green-500 font-bold"><?= $_SESSION['success_message'] ?></p>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <div class="grid grid-cols-1 gap-6">
            <?php foreach ($my_tasks as $task): 
                $status_class = '';
                $status_text = '';
                switch($task['status']) {
                    case 'en_attente': $status_class = 'bg-amber-500/10 text-amber-500 border-amber-500/20'; $status_text = 'En attente'; break;
                    case 'en_cours': $status_class = 'bg-blue-500/10 text-blue-500 border-blue-500/20'; $status_text = 'En cours'; break;
                    case 'termine': $status_class = 'bg-green-500/10 text-green-500 border-green-500/20'; $status_text = 'Terminé'; break;
                }
            ?>
                <div class="glass-card flex flex-col md:flex-row gap-6 items-start hover:bg-white/5 transition-all">
                    <?php if ($task['image_path']): ?>
                        <img src="../../<?= $task['image_path'] ?>" class="w-full md:w-32 h-32 object-cover rounded-xl border border-white/10 shadow-lg">
                    <?php else: ?>
                        <div class="w-full md:w-32 h-32 bg-white/5 flex items-center justify-center rounded-xl border border-white/10">
                            <i class="fas fa-tasks text-3xl text-gray-700"></i>
                        </div>
                    <?php endif; ?>

                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-3 mb-2">
                            <h3 class="text-xl font-bold"><?= htmlspecialchars($task['title']) ?></h3>
                            <span class="status-badge border <?= $status_class ?>"><?= $status_text ?></span>
                        </div>
                        <p class="text-gray-400 text-sm mb-6 line-clamp-2"><?= htmlspecialchars($task['description']) ?></p>
                        
                        <div class="flex flex-wrap items-center gap-4">
                            <a href="../admin/task_chat.php?id=<?= $task['id'] ?>" class="px-6 py-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-xs font-bold transition-all flex items-center gap-2">
                                <i class="fas fa-comments text-orange-500"></i> Discuter & Détails
                            </a>
                            
                            <?php if ($task['status'] !== 'termine'): ?>
                                <form method="POST" class="flex gap-2">
                                    <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                    <input type="hidden" name="update_status" value="1">
                                    <select name="status" onchange="this.form.submit()" class="bg-white/5 border border-white/10 rounded-xl text-[10px] font-black uppercase px-4 py-2 outline-none cursor-pointer hover:bg-white/10 transition-all">
                                        <option value="en_attente" <?= $task['status'] === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                                        <option value="en_cours" <?= $task['status'] === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                                        <option value="termine" <?= $task['status'] === 'termine' ? 'selected' : '' ?>>Terminé ✓</option>
                                    </select>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="text-right">
                        <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest mb-1">Assigné le</p>
                        <p class="text-sm font-bold text-white"><?= date('d/m/Y', strtotime($task['created_at'])) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($my_tasks)): ?>
                <div class="glass-card py-20 text-center">
                    <i class="fas fa-clipboard-list text-6xl text-gray-800 mb-6"></i>
                    <p class="text-gray-400 font-medium">Vous n'avez aucune mission assignée pour le moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
