<!-- Reusable Notification Component -->
<div class="relative group" id="notificationMenu">
    <button id="notificationBtn" class="bg-white/5 hover:bg-white/10 text-white p-3 rounded-xl border border-white/10 relative transition-all">
        <i class="fas fa-bell"></i>
        <span id="notifBadge" class="hidden absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-black w-5 h-5 rounded-full flex items-center justify-center border-2 border-[#0a0a0c]">0</span>
    </button>
    
    <!-- Dropdown Panel -->
    <div id="notifPanel" class="hidden absolute right-0 mt-4 w-80 bg-[#16161e] border border-white/10 rounded-2xl shadow-2xl z-[1000] overflow-hidden">
        <div class="p-4 border-b border-white/5 flex items-center justify-between">
            <h4 class="font-bold text-sm">Notifications</h4>
            <button onclick="markAllRead()" class="text-[10px] text-orange-500 font-bold uppercase hover:underline">Tout marquer lu</button>
        </div>
        <div id="notifList" class="max-h-96 overflow-y-auto">
            <div class="p-8 text-center text-gray-500 text-xs">Chargement...</div>
        </div>
        <a href="notifications.php" class="block p-3 text-center border-t border-white/5 text-[10px] font-black uppercase text-gray-400 hover:text-white transition-all bg-white/2">
            Voir tout l'historique
        </a>
    </div>
</div>

<script src="../../js/notifications.js"></script>
<style>
    .notif-item { padding: 1rem; border-bottom: 1px solid rgba(255,255,255,0.03); transition: all 0.2s; cursor: pointer; display: flex; gap: 0.75rem; }
    .notif-item:hover { background: rgba(255,255,255,0.02); }
    .notif-item.unread { background: rgba(255,102,0,0.03); border-left: 3px solid #ff6600; }
    .notif-icon { width: 32px; height: 32px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
</style>
