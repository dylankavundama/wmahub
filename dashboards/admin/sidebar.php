<?php
// dashboards/admin/sidebar.php
// Ce fichier sert de barre latérale commune pour le panneau d'administration.
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
    <div class="flex items-center gap-4 mb-12 px-2">
        <img src="../../asset/trans.png" alt="Logo" class="h-10">
        <div>
            <h1 class="text-xl font-black bg-gradient-to-r from-orange-500 to-orange-300 bg-clip-text text-transparent tracking-tighter leading-tight">WMA HUB</h1>
            <p class="text-[8px] text-gray-500 font-bold uppercase tracking-[1px] -mt-1">We move, WMAFam</p>
        </div>
    </div>
    <nav class="flex-1 overflow-y-auto pr-2">
        <?php
        $menu_items = [
            ['file' => 'index.php', 'icon' => 'fas fa-layer-group', 'label' => 'Gestion Projets'],
            ['file' => 'subscriptions.php', 'icon' => 'fas fa-crown', 'label' => 'Abonnements'],
            ['file' => 'payments.php', 'icon' => 'fas fa-history', 'label' => 'Paiements'],
            ['file' => 'artists.php', 'icon' => 'fas fa-microphone-alt', 'label' => 'Artistes'],
            ['file' => 'distributors.php', 'icon' => 'fas fa-truck-loading', 'label' => 'Distributeurs'],
            ['file' => 'revenues.php', 'icon' => 'fas fa-wallet', 'label' => 'Revenus Gérés'],
            ['file' => 'payouts.php', 'icon' => 'fas fa-money-bill-transfer', 'label' => 'Retraits & Payouts'],
            ['file' => 'employees.php', 'icon' => 'fas fa-users-cog', 'label' => 'Équipe & Staff'],
            ['file' => 'tasks.php', 'icon' => 'fas fa-tasks', 'label' => 'Gestion Tâches'],
            ['file' => 'salaries.php', 'icon' => 'fas fa-money-check-alt', 'label' => 'Gestion Salaires'],
            ['file' => 'project_files.php', 'icon' => 'fas fa-folder-open', 'label' => 'Fichier Projet'],
            ['file' => 'service_cards.php', 'icon' => 'fas fa-id-card', 'label' => 'Cartes de Service'],
            ['file' => 'notifications.php', 'icon' => 'fas fa-bell', 'label' => 'Notifications'],
            ['file' => 'finance.php', 'icon' => 'fas fa-chart-pie', 'label' => 'Rapports Financiers'],
            ['file' => 'site_stats.php', 'icon' => 'fas fa-chart-line', 'label' => 'Statistiques Site'],
            ['file' => 'logs.php', 'icon' => 'fas fa-exclamation-triangle text-red-500', 'label' => 'Journaux d\'Erreurs'],
            ['file' => 'comptabilite.php', 'icon' => 'fas fa-calculator', 'label' => 'Comptabilité'],
            ['file' => 'hero_slider.php', 'icon' => 'fas fa-images', 'label' => 'Gestion Slider'],
            ['file' => 'distributions.php', 'icon' => 'fas fa-music', 'label' => 'Distributions Vitrine'],
            ['file' => 'users.php', 'icon' => 'fas fa-user-friends', 'label' => 'Utilisateurs'],
        ];

        foreach ($menu_items as $item) {
            $active = ($current_page === $item['file']) ? 'active' : '';
            echo '<a href="' . $item['file'] . '" class="nav-link ' . $active . '"><i class="' . $item['icon'] . '"></i> ' . $item['label'] . '</a>';
        }
        
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin') {
            echo '<div class="mt-4 pt-4 border-t border-white/5">';
            echo '<p class="text-[9px] text-yellow-500/50 font-black uppercase tracking-widest px-4 mb-2">Master Controls</p>';
            echo '<a href="../superadmin/index.php" class="nav-link !text-yellow-500 hover:!bg-yellow-500/10"><i class="fas fa-crown"></i> Console Superadmin</a>';
            echo '</div>';
        }
        ?>
    </nav>
    <div class="mt-auto pt-6 border-t border-white/5">
        <div class="flex items-center gap-4 mb-8 px-2">
            <div class="w-10 h-10 rounded-full bg-orange-500/10 border border-orange-500/20 flex items-center justify-center text-orange-500"><i class="fas fa-user-shield"></i></div>
            <div>
                <p class="text-sm font-bold text-white"><?= isset($_SESSION['user_name']) ? explode(' ', $_SESSION['user_name'])[0] : 'Admin' ?></p>
                <p class="text-[10px] text-gray-400 uppercase font-black tracking-widest"><?= $_SESSION['role'] ?? 'admin' ?></p>
            </div>
        </div>
        <a href="../../auth/logout.php" class="nav-link !text-red-500 hover:!bg-red-500/10"><i class="fas fa-power-off"></i> Déconnexion</a>
    </div>
</aside>
