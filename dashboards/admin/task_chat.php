<?php
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$db = getDBConnection();
$task_id = $_GET['id'] ?? null;

if (!$task_id) {
    die("ID de tâche manquant.");
}

// Récupérer les détails de la tâche
$stmt = $db->prepare("SELECT t.*, u.name as employee_name, u.id as emp_id FROM tasks t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
$stmt->execute([$task_id]);
$task = $stmt->fetch();

if (!$task) {
    die("Tâche introuvable.");
}

// Sécurité : Seul l'admin ou l'employé assigné peut voir le chat
if ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] != $task['emp_id']) {
    die("Accès refusé.");
}

// Traitement POST : Nouveau message ou changement de statut
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_message'])) {
        $message = $_POST['message'] ?? '';
        $image_path = null;

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('chat_') . '.' . $ext;
            $upload_dir = __DIR__ . '/../../uploads/tasks/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename);
            $image_path = 'uploads/tasks/' . $filename;
        }

        if (!empty($message) || $image_path) {
            $stmt = $db->prepare("INSERT INTO task_messages (task_id, sender_id, message, image_path) VALUES (?, ?, ?, ?)");
            $stmt->execute([$task_id, $_SESSION['user_id'], $message, $image_path]);

            // Notify receiver
            require_once __DIR__ . '/../../includes/mailer.php';
            if ($_SESSION['role'] === 'admin') {
                $notif = $db->prepare("INSERT INTO notifications (user_id, type, reference_id) VALUES (?, 'new_message', ?)");
                $notif->execute([$task['emp_id'], $task_id]);

                // Email à l'employé
                $stmt_emp = $db->prepare("SELECT email, name FROM users WHERE id = ?");
                $stmt_emp->execute([$task['emp_id']]);
                $emp = $stmt_emp->fetch();
                if ($emp) {
                    sendEmail($emp['email'], "Nouveau message - " . $task['title'], getProjectEmailTemplate(
                        "💬 Nouveau Message",
                        "<p>L'administration a laissé un message sur la mission : <strong>" . htmlspecialchars($task['title']) . "</strong></p><p><i>\"" . htmlspecialchars($message) . "\"</i></p>",
                        "Répondre au message",
                        "https://wmahub.com/dashboards/admin/task_chat.php?id=" . $task_id
                    ));
                }
            } else {
                $admins = $db->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll();
                $notif = $db->prepare("INSERT INTO notifications (user_id, type, reference_id) VALUES (?, 'new_message', ?)");
                foreach ($admins as $admin) {
                    $notif->execute([$admin['id'], $task_id]);
                    
                    // Email aux admins
                    notifyAdmin('employee', "Nouveau message de " . $_SESSION['user_name'], [
                        'Mission' => $task['title'],
                        'Message' => $message
                    ], "https://wmahub.com/dashboards/admin/task_chat.php?id=" . $task_id);
                }
            }
        }
    }
    
    if (isset($_POST['update_status'])) {
        $stmt = $db->prepare("UPDATE tasks SET status = ?, completed_at = CASE WHEN ? = 'termine' THEN CURRENT_TIMESTAMP ELSE completed_at END, rating = CASE WHEN ? = 'termine' AND rating IS NULL THEN 3 ELSE rating END WHERE id = ?");
        $stmt->execute([$_POST['status'], $_POST['status'], $_POST['status'], $task_id]);

        // Notifier l'employé
        if ($_SESSION['role'] === 'admin') {
            $notif = $db->prepare("INSERT INTO notifications (user_id, type, reference_id) VALUES (?, 'task_update', ?)");
            $notif->execute([$task['emp_id'], $task_id]);

            // Email à l'employé pour le changement de statut
            require_once __DIR__ . '/../../includes/mailer.php';
            $stmt_emp = $db->prepare("SELECT email, name FROM users WHERE id = ?");
            $stmt_emp->execute([$task['emp_id']]);
            $emp = $stmt_emp->fetch();
            if ($emp) {
                $status_lbl = $_POST['status'] === 'termine' ? 'TERMINÉE ✓' : 'EN COURS';
                sendEmail($emp['email'], "Mise à jour mission : " . $task['title'], getProjectEmailTemplate(
                    "🔄 Statut Mis à Jour",
                    "<p>Le statut de votre mission <strong>" . htmlspecialchars($task['title']) . "</strong> a été mis à jour par l'administration.</p><p>Nouveau statut : <strong style='color:#ff6600'>$status_lbl</strong></p>",
                    "Voir la mission",
                    "https://wmahub.com/dashboards/admin/task_chat.php?id=" . $task_id
                ));
            }
        }
    }

    header("Location: task_chat.php?id=$task_id");
    exit;
}

// Récupérer les messages
$stmt = $db->prepare("SELECT tm.*, u.name as sender_name, u.role as sender_role FROM task_messages tm JOIN users u ON tm.sender_id = u.id WHERE tm.task_id = ? ORDER BY tm.created_at ASC");
$stmt->execute([$task_id]);
$messages = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMA HUB - Chat Mission</title>
    
    <!-- Scripts et CSS Prioritaires -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin-shared.css">
    
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
            background: #0a0a0c !important; 
            color: #fff; 
            height: 100vh; 
            margin: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
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
        .chat-container { flex: 1; overflow-y: auto; padding: 2rem; scroll-behavior: smooth; }
        .message-bubble { max-width: 70%; padding: 1rem; border-radius: 1.5rem; margin-bottom: 1rem; position: relative; }
        .msg-left { background: rgba(255, 255, 255, 0.05); border-bottom-left-radius: 0.2rem; align-self: flex-start; }
        .msg-right { background: rgba(255, 102, 0, 0.1); border-bottom-right-radius: 0.2rem; align-self: flex-end; border: 1px solid rgba(255, 102, 0, 0.2); }
        .glass-header { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-bottom: 1px solid rgba(255, 255, 255, 0.05); padding: 1.5rem 2rem; position: sticky; top: 0; z-index: 50; }
        .glass-footer { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-top: 1px solid rgba(255, 255, 255, 0.05); padding: 1.5rem 2rem; position: sticky; bottom: 0; z-index: 50; }
        .custom-input { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 1rem; color: #fff; padding: 0.8rem 1.5rem; outline: none; width: 100%; }
        
        @media (max-width: 768px) {
            .chat-container { padding: 1rem; }
            .message-bubble { max-width: 85%; }
            .glass-header, .glass-footer { padding: 1rem; }
        }
    </style>
</head>
<body>
    <div id="wma-global-loader">
        <div class="loader-spin"></div>
    </div>
    <div class="bg-glow"></div>
    
    <header class="glass-header flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="<?= $_SESSION['role'] === 'admin' ? 'tasks.php' : 'index.php' ?>" class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center hover:bg-white/10 transition-all">
                <i class="fas fa-chevron-left"></i>
            </a>
            <div>
                <h1 class="text-lg font-bold text-white"><?= htmlspecialchars($task['title']) ?></h1>
                <p class="text-[10px] text-orange-500 uppercase font-black uppercase"><?= $_SESSION['role'] === 'admin' ? 'Avec ' . htmlspecialchars($task['employee_name']) : 'Admin Support' ?></p>
            </div>
        </div>
        <div class="flex items-center gap-4">
             <form method="POST" class="flex items-center gap-2">
                <select name="status" onchange="this.form.submit()" class="bg-white/5 border border-white/10 rounded-lg text-[10px] font-black uppercase px-3 py-2 text-white outline-none">
                    <option value="en_cours" <?= $task['status'] === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                    <option value="termine" <?= $task['status'] === 'termine' ? 'selected' : '' ?>>Terminé ✓</option>
                </select>
                <input type="hidden" name="update_status" value="1">
            </form>
        </div>
    </header>

    <div class="chat-container flex flex-col" id="chatContainer">
        <div class="bg-orange-500/5 border border-orange-500/10 rounded-2xl p-6 mb-8 text-center max-w-2xl mx-auto">
            <p class="text-xs text-gray-400 uppercase font-black tracking-widest mb-2">Description de la mission</p>
            <p class="text-sm text-white"><?= nl2br(htmlspecialchars($task['description'])) ?></p>
        </div>

        <?php foreach ($messages as $msg): ?>
            <?php $isMe = ($msg['sender_id'] == $_SESSION['user_id']); ?>
            <div class="message-bubble <?= $isMe ? 'msg-right' : 'msg-left' ?>">
                <div class="flex items-center justify-between gap-4 mb-2">
                    <span class="text-[10px] font-black uppercase <?= $isMe ? 'text-orange-500' : 'text-gray-500' ?>">
                        <?= htmlspecialchars($msg['sender_name']) ?>
                    </span>
                    <span class="text-[9px] text-gray-600"><?= date('H:i', strtotime($msg['created_at'])) ?></span>
                </div>
                <?php if ($msg['message']): ?>
                    <p class="text-sm text-white"><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                <?php endif; ?>
                <?php if ($msg['image_path']): ?>
                    <div class="mt-3 rounded-lg overflow-hidden border border-white/10">
                        <img src="../../<?= $msg['image_path'] ?>" alt="Attachment" class="max-w-full cursor-pointer hover:opacity-90" onclick="window.open(this.src)">
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <footer class="glass-footer">
        <form method="POST" enctype="multipart/form-data" class="flex items-center gap-4 max-w-6xl mx-auto">
            <div class="relative flex-1">
                <input type="text" name="message" class="custom-input" placeholder="Votre message..." autocomplete="off">
            </div>
            <label class="w-12 h-12 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center cursor-pointer hover:bg-white/10 transition-all text-gray-400">
                <i class="fas fa-image"></i>
                <input type="file" name="image" class="hidden" accept="image/*">
            </label>
            <button type="submit" name="send_message" class="w-12 h-12 rounded-xl bg-orange-500 flex items-center justify-center text-white shadow-lg shadow-orange-500/20 hover:scale-105 transition-all">
                <i class="fas fa-paper-plane"></i>
            </button>
        </form>
    </footer>

    <script>
        const chatContainer = document.getElementById('chatContainer');
        chatContainer.scrollTop = chatContainer.scrollHeight;
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
