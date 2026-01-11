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
    // Changement de rôle
    if (isset($_POST['update_role'])) {
        $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$_POST['role'], $_POST['user_id']]);
    }
    // Suppression d'utilisateur
    if (isset($_POST['delete_user'])) {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$_POST['user_id']]);
    }
    header('Location: users.php');
    exit;
}

// Récupérer tous les utilisateurs triés par rôle
$users = $db->query("SELECT * FROM users ORDER BY FIELD(role, 'admin', 'employe', 'artiste'), created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMA HUB - Gestion Utilisateurs</title>
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
        .admin-table { width: 100%; border-collapse: separate; border-spacing: 0 0.75rem; }
        .admin-table tr { background: rgba(255, 255, 255, 0.02); }
        .admin-table td, .admin-table th { padding: 1.25rem 1.5rem; }
        .custom-select { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 0.75rem; color: #fff; font-size: 0.75rem; padding: 0.5rem 1rem; outline: none; cursor: pointer; }
        .search-bar { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 1rem; padding: 0.8rem 1.5rem; color: #fff; width: 300px; outline: none; }
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
            <a href="../../auth/logout.php" class="nav-link !text-red-500 hover:!bg-red-500/10"><i class="fas fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="flex flex-col md:flex-row md:items-center justify-between gap-8 mb-12">
            <div>
                <h2 class="text-4xl font-black tracking-tighter text-white">Gestion <span class="text-orange-500">Utilisateurs</span></h2>
                <p class="text-gray-400 mt-2">Gérez les rôles et permissions des membres.</p>
            </div>
            <div class="flex items-center gap-4">
                <input type="text" id="userSearch" class="search-bar" placeholder="Rechercher...">
            </div>
        </header>

        <div class="glass-card p-0 overflow-hidden shadow-2xl border-white/5">
            <div class="overflow-x-auto">
                <table class="admin-table text-left" id="usersTable">
                    <thead>
                        <tr class="text-[10px] text-gray-500 uppercase tracking-widest">
                            <th class="px-8">Utilisateur</th>
                            <th>Rôle</th>
                            <th>Date d'inscription</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td class="px-8">
                                    <div class="font-bold text-white"><?= htmlspecialchars($u['name']) ?></div>
                                    <div class="text-[10px] text-gray-500"><?= htmlspecialchars($u['email']) ?></div>
                                </td>
                                <td>
                                    <form method="POST" class="flex items-center gap-2">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <select name="role" onchange="this.form.submit()" class="custom-select <?php 
                                            echo match($u['role']) {
                                                'admin' => '!border-red-500/30 !text-red-500',
                                                'employe' => '!border-orange-500/30 !text-orange-500',
                                                'artiste' => '!border-blue-500/30 !text-blue-500',
                                                default => ''
                                            };
                                        ?>">
                                            <option value="artiste" <?= $u['role'] === 'artiste' ? 'selected' : '' ?>>Artiste</option>
                                            <option value="employe" <?= $u['role'] === 'employe' ? 'selected' : '' ?>>Employé</option>
                                            <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                        </select>
                                        <input type="hidden" name="update_role" value="1">
                                    </form>
                                </td>
                                <td class="text-xs text-gray-500">
                                    <?= date('d/m/Y', strtotime($u['created_at'])) ?>
                                </td>
                                <td>
                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" onsubmit="return confirm('Supprimer cet utilisateur ?')">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <button type="submit" name="delete_user" class="text-gray-600 hover:text-red-500">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        const glow = document.getElementById('glow');
        document.addEventListener('mousemove', (e) => { glow.style.left = (e.clientX - 200) + 'px'; glow.style.top = (e.clientY - 200) + 'px'; });
        
        document.getElementById('userSearch').addEventListener('keyup', function() {
            const value = this.value.toLowerCase();
            document.querySelectorAll('#usersTable tbody tr').forEach(row => {
                row.style.display = row.innerText.toLowerCase().indexOf(value) > -1 ? '' : 'none';
            });
        });
    </script>
</body>
</html>
