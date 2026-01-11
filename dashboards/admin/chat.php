<?php
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = getDBConnection();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMA ADMIN - Chat Équipe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; height: 100vh; display: flex; overflow: hidden; }
        .bg-glow { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; display: flex; flex-direction: column; padding: 2rem 1.5rem; }
        .chat-area { flex: 1; display: flex; flex-direction: column; background: rgba(255, 255, 255, 0.01); }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; font-size: 0.9rem; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; transform: translateX(5px); }
        .messages-container { flex: 1; overflow-y: auto; padding: 2rem; display: flex; flex-direction: column; gap: 1rem; }
        .message { max-width: 70%; padding: 1rem; border-radius: 1.5rem; position: relative; }
        .message.sent { align-self: flex-end; background: rgba(255, 102, 0, 0.1); border: 1px solid rgba(255, 102, 0, 0.2); border-bottom-right-radius: 0.2rem; }
        .message.received { align-self: flex-start; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-bottom-left-radius: 0.2rem; }
        .chat-input-area { padding: 1.5rem 2rem; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-top: 1px solid rgba(255, 255, 255, 0.05); }
        .custom-input { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 1rem; color: #fff; padding: 0.8rem 1.5rem; outline: none; width: 100%; }
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
    </aside>

    <div class="chat-area">
        <header class="p-6 border-b border-white/5 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold">Chat Équipe</h2>
                <p class="text-[10px] text-orange-500 font-black uppercase tracking-widest">Global Admin & Staff</p>
            </div>
            <?php include '../../includes/header_notifications.php'; ?>
        </header>

        <div class="messages-container" id="messagesContainer">
            <!-- Messages load here -->
        </div>

        <div class="chat-input-area">
            <form id="chatForm" class="flex gap-4 max-w-6xl mx-auto">
                <input type="text" id="messageInput" class="custom-input" placeholder="Écrivez votre message..." autocomplete="off">
                <button type="submit" class="w-12 h-12 rounded-xl bg-orange-500 flex items-center justify-center text-white shadow-lg shadow-orange-500/20 hover:scale-105 transition-all">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>

    <script>
        let lastId = 0;
        const container = document.getElementById('messagesContainer');
        const form = document.getElementById('chatForm');
        const input = document.getElementById('messageInput');

        async function loadMessages() {
            try {
                const response = await fetch(`../../api/chat.php?action=get_messages&last_id=${lastId}`);
                const data = await response.json();
                if (data.success && data.messages.length > 0) {
                    data.messages.forEach(msg => {
                        const div = document.createElement('div');
                        const isMe = msg.sender_id == <?= $_SESSION['user_id'] ?>;
                        div.className = `message ${isMe ? 'sent' : 'received'}`;
                        div.innerHTML = `
                            <div class="flex items-center justify-between gap-4 mb-1">
                                <span class="text-[9px] font-black uppercase ${isMe ? 'text-orange-500' : 'text-gray-500'}">${msg.sender_name}</span>
                                <span class="text-[8px] text-gray-600">${new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                            </div>
                            <p class="text-sm">${msg.message}</p>
                        `;
                        container.appendChild(div);
                        lastId = Math.max(lastId, msg.id);
                    });
                    container.scrollTop = container.scrollHeight;
                }
            } catch (e) { console.error(e); }
        }

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const msg = input.value.trim();
            if (!msg) return;

            const formData = new FormData();
            formData.append('message', msg);

            input.value = '';
            try {
                await fetch('../../api/chat.php?action=send', { method: 'POST', body: formData });
                loadMessages();
            } catch (e) { console.error(e); }
        });

        loadMessages();
        setInterval(loadMessages, 3000);
    </script>
</body>
</html>
