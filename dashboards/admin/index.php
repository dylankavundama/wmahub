<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint aux employés et admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'employe' && $_SESSION['role'] !== 'admin')) {
    header('Location: ../../auth/login.php');
    exit;
}

$db = getDBConnection();

// Si c'est un employé, vérifier s'il est actif
if ($_SESSION['role'] === 'employe') {
    $stmt = $db->prepare("SELECT is_active FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user || !$user['is_active']) {
        header('Location: ../../auth/pending.php');
        exit;
    }
}

// Traitement des mises à jour de statut
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_project_status'])) {
        $stmt = $db->prepare("UPDATE projects SET status = ? WHERE id = ?");
        $stmt->execute([$_POST['status'], $_POST['project_id']]);
    }
    if (isset($_POST['update_payment_status'])) {
        $stmt = $db->prepare("UPDATE projects SET payment_status = ? WHERE id = ?");
        $stmt->execute([$_POST['payment_status'], $_POST['project_id']]);
    }
    if (isset($_POST['activate_user']) && $_SESSION['role'] === 'admin') {
        $stmt = $db->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
        $stmt->execute([$_POST['user_id']]);
    }
}

// Récupérer tous les projets
$projects = $db->query("SELECT p.*, u.name as user_name, u.email as user_email FROM projects p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC")->fetchAll();

// Récupérer les employés en attente (seulement pour l'admin)
$pending_users = [];
if ($_SESSION['role'] === 'admin') {
    $pending_users = $db->query("SELECT * FROM users WHERE role = 'employe' AND is_active = 0")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - WMA Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f1f5f9; }
    </style>
</head>
<body>
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-slate-900 text-white p-6 hidden lg:block">
            <div class="mb-10">
                <img src="../../asset/trans.png" alt="WMA Hub" class="h-10 mb-2">
                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">WMA Hub Admin</p>
            </div>
            <nav class="space-y-2">
                <a href="#" class="block py-2.5 px-4 rounded bg-slate-800 text-white"><i class="fas fa-chart-line mr-3"></i> Projets</a>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="#" class="block py-2.5 px-4 rounded text-slate-400 hover:bg-slate-800 hover:text-white transition-colors"><i class="fas fa-users mr-3"></i> Utilisateurs</a>
                <?php endif; ?>
            </nav>
            <div class="mt-auto pt-10">
                <a href="../../auth/logout.php" class="text-slate-400 hover:text-red-400 text-sm"><i class="fas fa-sign-out-alt mr-2"></i> Déconnexion</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-6 lg:p-10">
            <header class="flex justify-between items-center mb-10">
                <h1 class="text-2xl font-bold text-slate-800">Gestion des Projets</h1>
                <div class="flex items-center gap-4">
                    <span class="text-sm font-medium text-slate-600">Bonjour, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    <div class="w-10 h-10 bg-slate-200 rounded-full flex items-center justify-center text-slate-500">
                        <i class="fas fa-user-shield"></i>
                    </div>
                </div>
            </header>

            <?php if ($_SESSION['role'] === 'admin' && !empty($pending_users)): ?>
                <!-- Pending Users Section -->
                <section class="mb-10 bg-white p-6 rounded-2xl shadow-sm border border-amber-100">
                    <h2 class="text-lg font-bold text-amber-800 mb-4"><i class="fas fa-exclamation-triangle mr-2"></i> Employés en attente d'activation</h2>
                    <div class="space-y-4">
                        <?php foreach ($pending_users as $user): ?>
                            <div class="flex items-center justify-between p-4 bg-amber-50 rounded-xl border border-amber-100">
                                <div>
                                    <p class="font-bold text-slate-800"><?= htmlspecialchars($user['name']) ?></p>
                                    <p class="text-sm text-slate-500"><?= htmlspecialchars($user['email']) ?></p>
                                </div>
                                <form method="POST">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" name="activate_user" class="px-4 py-2 bg-amber-600 text-white text-sm font-bold rounded-lg hover:bg-amber-700 transition-colors">
                                        Activer le compte
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Projects Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Artiste / Projet</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Détails</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Statut Projet</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Paiement</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($projects as $project): ?>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="mb-1 font-bold text-slate-900"><?= htmlspecialchars($project['title']) ?></div>
                                        <div class="text-xs text-slate-500"><?= htmlspecialchars($project['user_name']) ?> (<?= htmlspecialchars($project['user_email']) ?>)</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600">
                                        <?= htmlspecialchars($project['type']) ?> • <?= htmlspecialchars($project['genre']) ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <form method="POST" class="flex gap-2">
                                            <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                                            <select name="status" onchange="this.form.submit()" class="text-xs font-medium px-2 py-1 rounded bg-slate-100 border-none">
                                                <option value="en_attente" <?= $project['status'] === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                                                <option value="en_preparation" <?= $project['status'] === 'en_preparation' ? 'selected' : '' ?>>Préparation</option>
                                                <option value="distribue" <?= $project['status'] === 'distribue' ? 'selected' : '' ?>>Distribué</option>
                                            </select>
                                            <input type="hidden" name="update_project_status" value="1">
                                        </form>
                                    </td>
                                    <td class="px-6 py-4">
                                        <form method="POST" class="flex gap-2">
                                            <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                                            <select name="payment_status" onchange="this.form.submit()" class="text-xs font-medium px-2 py-1 rounded <?= $project['payment_status'] === 'paye' ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800' ?> border-none">
                                                <option value="en_attente" <?= $project['payment_status'] === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                                                <option value="paye" <?= $project['payment_status'] === 'paye' ? 'selected' : '' ?>>Payé</option>
                                            </select>
                                            <input type="hidden" name="update_payment_status" value="1">
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
