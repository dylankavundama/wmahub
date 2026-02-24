<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint au superadmin uniquement
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = getDBConnection();
$userId = $_SESSION['user_id'];
$success = '';
$error = '';

// Gérer les actions (Changement de rôle, Activation/Désactivation)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['change_role'])) {
        $targetId = $_POST['user_id'];
        $newRole = $_POST['role'];
        
        // Empêcher un superadmin de se dégrader lui-même sans autre superadmin (sécurité minimale)
        if ($targetId == $userId && $newRole !== 'superadmin') {
            $otherSuper = $db->query("SELECT COUNT(*) FROM users WHERE role = 'superadmin' AND id != $userId")->fetchColumn();
            if ($otherSuper == 0) {
                $error = "Opération impossible : Vous êtes le seul Superadmin.";
            }
        }

        if (!$error) {
            $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
            if ($stmt->execute([$newRole, $targetId])) {
                $success = "Rôle mis à jour.";
            }
        }
    }

    if (isset($_POST['toggle_active'])) {
        $targetId = $_POST['user_id'];
        $status = $_POST['status'];
        
        if ($targetId == $userId && $status == 0) {
            $error = "Vous ne pouvez pas vous désactiver vous-même.";
        } else {
            $stmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            $stmt->execute([$status, $targetId]);
            $success = $status ? "Compte activé." : "Compte désactivé.";
        }
    }

    if (isset($_POST['promote_user'])) {
        $searchEmail = trim($_POST['email']);
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$searchEmail]);
        $target = $stmt->fetch();
        
        if ($target) {
            $stmt_upd = $db->prepare("UPDATE users SET role = 'admin', is_active = 1 WHERE id = ?");
            $stmt_upd->execute([$target['id']]);
            $success = "L'utilisateur $searchEmail a été promu Administrateur.";
        } else {
            $error = "Utilisateur non trouvé avec cet email.";
        }
    }
}

// Récupérer la liste des admins et superadmins
$admins = $db->query("SELECT * FROM users WHERE role IN ('admin', 'superadmin') ORDER BY role DESC, name ASC")->fetchAll();

$pageTitle = 'Gestion du Staff - WMA HUB';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/jpeg" href="../../asset/placeholder.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #050507; color: #fff; min-height: 100vh; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 50% 50%, #10101a 0%, #050507 100%); z-index: -1; }
        .sidebar { width: 300px; background: rgba(255, 255, 255, 0.01); backdrop-filter: blur(30px); border-right: 1px solid rgba(255, 255, 255, 0.03); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; padding: 2.5rem 2rem; display: flex; flex-direction: column; }
        .main-content { margin-left: 300px; padding: 4rem; }
        .nav-link { display: flex; align-items: center; gap: 1.25rem; padding: 1.15rem 1.5rem; color: rgba(255, 255, 255, 0.3); border-radius: 1.25rem; font-weight: 500; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); margin-bottom: 0.75rem; text-decoration: none; font-size: 0.95rem; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 215, 0, 0.05); color: #ffd700; transform: translateX(8px); }
        .nav-link.active { border-right: 3px solid #ffd700; border-radius: 1.25rem 0 0 1.25rem; }
        .glass-card { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 2.5rem; padding: 2.5rem; }
        .input-glass { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 1rem; color: #fff; padding: 1rem; width: 100%; outline: none; transition: all 0.3s ease; }
        .input-glass:focus { border-color: #ffd700; }
        .custom-select { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); color: #fff; border-radius: 0.75rem; padding: 0.5rem 1rem; font-size: 0.8rem; outline: none; cursor: pointer; }
    </style>
</head>
<body>
    <div class="bg-glow"></div>

    <aside class="sidebar">
        <div class="flex items-center gap-4 mb-20 px-2">
            <img src="../../asset/trans.png" alt="Logo" class="h-12">
            <div>
                <h1 class="text-2xl font-black bg-gradient-to-r from-yellow-400 to-yellow-200 bg-clip-text text-transparent tracking-tighter leading-tight">SUPERADMIN</h1>
                <p class="text-[9px] text-gray-500 font-bold uppercase tracking-[2px] -mt-1">Control Center</p>
            </div>
        </div>
        
        <nav class="flex-1">
            <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>"><i class="fas fa-th-large"></i> Vue d'ensemble</a>
            <a href="revenues.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'revenues.php' ? 'active' : '' ?>"><i class="fas fa-wallet"></i> Analyse Revenus</a>
            <a href="payments.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'payments.php' ? 'active' : '' ?>"><i class="fas fa-history"></i> Historique Paiements</a>
            <a href="payment_logs.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'payment_logs.php' ? 'active' : '' ?>"><i class="fas fa-file-alt"></i> Logs Paiement</a>
            <a href="artists.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'artists.php' ? 'active' : '' ?>"><i class="fas fa-microphone-alt"></i> Artistes</a>
            <a href="distributors.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'distributors.php' ? 'active' : '' ?>"><i class="fas fa-truck-loading"></i> Distributeurs</a>
            <a href="admins.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'admins.php' ? 'active' : '' ?>"><i class="fas fa-user-shield"></i> Gestion des Admins</a>
            <a href="settings.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : '' ?>"><i class="fas fa-cogs"></i> Paramètres</a>
            <a href="../admin/index.php" class="nav-link"><i class="fas fa-arrow-left"></i> Retour au Panel Admin</a>
        </nav>

        <div class="mt-auto pt-8 border-t border-white/5">
            <a href="../../auth/logout.php" class="nav-link !text-red-500 hover:!bg-red-500/5"><i class="fas fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="mb-16 flex flex-col lg:flex-row lg:items-center justify-between gap-8">
            <div>
                <h2 class="text-6xl font-black tracking-tighter leading-none">Équipe & <span class="text-yellow-500">Staff</span></h2>
                <p class="text-gray-500 mt-4 text-xl">Gérez les accès administratifs et les privilèges.</p>
            </div>
            <div class="glass-card !p-8 border-yellow-500/10">
                <p class="text-[10px] text-yellow-500 font-black uppercase tracking-widest mb-4">Promouvoir un utilisateur</p>
                <form method="POST" class="flex gap-4">
                    <input type="email" name="email" required placeholder="Email de l'utilisateur" class="input-glass !w-64 !py-3">
                    <button type="submit" name="promote_user" class="bg-yellow-500 text-black font-bold px-6 py-3 rounded-2xl hover:brightness-110 transition-all">Promouvoir</button>
                </form>
            </div>
        </header>

        <?php if ($success): ?>
            <div class="bg-green-500/10 border border-green-500/20 text-green-500 p-4 rounded-2xl mb-8 flex items-center gap-3">
                <i class="fas fa-check-circle"></i> <?= $success ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-2xl mb-8 flex items-center gap-3">
                <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <div class="glass-card !p-0 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-white/5">
                    <tr class="text-[10px] text-gray-500 uppercase tracking-widest">
                        <th class="px-8 py-6">Administrateur</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th class="text-right px-8">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    <?php foreach ($admins as $adm): ?>
                        <tr class="border-b border-white/5 hover:bg-white/[0.01] transition-all">
                            <td class="px-8 py-6 flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center border border-white/10 font-black text-yellow-500">
                                    <?= strtoupper(substr($adm['name'], 0, 1)) ?>
                                </div>
                                <span class="font-bold"><?= htmlspecialchars($adm['name']) ?></span>
                                <?php if ($adm['id'] == $userId): ?>
                                    <span class="text-[8px] bg-white/10 px-2 py-0.5 rounded text-gray-400 font-black">VOUS</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-gray-400"><?= htmlspecialchars($adm['email']) ?></td>
                            <td>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="user_id" value="<?= $adm['id'] ?>">
                                    <select name="role" onchange="this.form.submit()" class="custom-select <?= $adm['role'] === 'superadmin' ? '!text-yellow-500 !border-yellow-500/20' : '' ?>">
                                        <option value="admin" <?= $adm['role'] === 'admin' ? 'selected' : '' ?>>Admin Standard</option>
                                        <option value="superadmin" <?= $adm['role'] === 'superadmin' ? 'selected' : '' ?>>Superadmin</option>
                                        <option value="artiste">Rétrograder Artiste</option>
                                        <option value="distributeur">Rétrograder Distributeur</option>
                                    </select>
                                    <input type="hidden" name="change_role" value="1">
                                </form>
                            </td>
                            <td class="text-right px-8">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="user_id" value="<?= $adm['id'] ?>">
                                    <input type="hidden" name="status" value="<?= $adm['is_active'] ? 0 : 1 ?>">
                                    <button type="submit" name="toggle_active" class="px-4 py-2 rounded-lg text-xs font-bold transition-all <?= $adm['is_active'] ? 'bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white' : 'bg-green-500/10 text-green-500 hover:bg-green-500 hover:text-white' ?>">
                                        <?= $adm['is_active'] ? 'Désactiver' : 'Activer' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
