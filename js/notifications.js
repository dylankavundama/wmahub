// Notification system logic
document.addEventListener('DOMContentLoaded', () => {
    const notifBtn = document.getElementById('notificationBtn');
    const notifPanel = document.getElementById('notifPanel');
    const notifBadge = document.getElementById('notifBadge');
    const notifList = document.getElementById('notifList');

    if (!notifBtn) return;

    // Toggle Panel
    notifBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        notifPanel.classList.toggle('hidden');
        if (!notifPanel.classList.contains('hidden')) {
            loadNotifications();
        }
    });

    document.addEventListener('click', () => notifPanel.classList.add('hidden'));
    notifPanel.addEventListener('click', (e) => e.stopPropagation());

    // Polling
    const checkUnread = async () => {
        try {
            const response = await fetch('../../api/notifications.php?action=get_unread_count');
            const data = await response.json();
            if (data.success && data.count > 0) {
                notifBadge.innerText = data.count > 9 ? '9+' : data.count;
                notifBadge.classList.remove('hidden');
            } else {
                notifBadge.classList.add('hidden');
            }
        } catch (e) { console.error('Notif check error', e); }
    };

    const loadNotifications = async () => {
        notifList.innerHTML = '<div class="p-8 text-center text-gray-500 text-xs">Chargement...</div>';
        try {
            const response = await fetch('../../api/notifications.php?action=get_all');
            const data = await response.json();
            if (data.success) {
                if (data.notifications.length === 0) {
                    notifList.innerHTML = '<div class="p-8 text-center text-gray-500 text-xs">Aucune notification</div>';
                } else {
                    notifList.innerHTML = data.notifications.map(n => renderNotif(n)).join('');
                }
            }
        } catch (e) {
            notifList.innerHTML = '<div class="p-8 text-center text-red-500 text-xs">Erreur de chargement</div>';
        }
    };

    const renderNotif = (n) => {
        const icon = getNotifIcon(n.type);
        return `
            <div class="notif-item ${n.is_read ? '' : 'unread'}" onclick="markOneRead(${n.id}, '${n.type}', ${n.reference_id})">
                <div class="notif-icon ${icon.bg}">${icon.html}</div>
                <div>
                    <p class="text-xs text-white font-medium mb-1">${getNotifText(n)}</p>
                    <span class="text-[9px] text-gray-500">${timeAgo(n.created_at)}</span>
                </div>
            </div>
        `;
    };

    const getNotifIcon = (type) => {
        switch (type) {
            case 'new_project': return { bg: 'bg-green-500/10 text-green-500', html: '<i class="fas fa-plus"></i>' };
            case 'new_task': return { bg: 'bg-blue-500/10 text-blue-500', html: '<i class="fas fa-tasks"></i>' };
            case 'new_message': return { bg: 'bg-orange-500/10 text-orange-500', html: '<i class="fas fa-comment"></i>' };
            default: return { bg: 'bg-gray-500/10 text-gray-500', html: '<i class="fas fa-bell"></i>' };
        }
    };

    const getNotifText = (n) => {
        switch (n.type) {
            case 'new_project': return 'Nouveau projet créé';
            case 'new_task': return 'Une nouvelle tâche vous a été assignée';
            case 'new_message': return 'Nouveau message reçu';
            case 'new_broadcast_message': return 'Nouveau message dans le chat global';
            case 'task_update': return 'Mise à jour d\'une tâche';
            default: return 'Nouvelle notification';
        }
    };

    const timeAgo = (date) => {
        const seconds = Math.floor((new Date() - new Date(date)) / 1000);
        if (seconds < 60) return "À l'instant";
        const minutes = Math.floor(seconds / 60);
        if (minutes < 60) return `${minutes}m`;
        const hours = Math.floor(minutes / 60);
        if (hours < 24) return `${hours}h`;
        return `${Math.floor(hours / 24)}j`;
    };

    window.markAllRead = async () => {
        await fetch('../../api/notifications.php?action=mark_read', { method: 'POST' });
        loadNotifications();
        checkUnread();
    };

    window.markOneRead = async (id, type, refId) => {
        const formData = new FormData();
        formData.append('id', id);
        await fetch('../../api/notifications.php?action=mark_read', { method: 'POST', body: formData });
        checkUnread();
        // Handle redirection based on type
        // e.g. if type === 'new_task' window.location = 'tasks.php?id=' + refId;
    };

    checkUnread();
    setInterval(checkUnread, 30000); // Check every 30s
});
