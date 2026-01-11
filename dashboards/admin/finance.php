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
    // Ajout d'une entrée d'argent (Externe)
    if (isset($_POST['add_income'])) {
        $stmt = $db->prepare("INSERT INTO incomes (motif, montant, date_entree) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['motif'], $_POST['montant'], $_POST['date_entree']]);
    }
    // Suppression d'une entrée
    if (isset($_POST['delete_income'])) {
        $stmt = $db->prepare("DELETE FROM incomes WHERE id = ?");
        $stmt->execute([$_POST['income_id']]);
    }
    // Ajout d'une dépense
    if (isset($_POST['add_expense'])) {
        $stmt = $db->prepare("INSERT INTO expenses (project_id, motif, montant, date_depense) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['project_id'], $_POST['motif'], $_POST['montant'], $_POST['date_depense']]);
    }
    // Suppression d'une dépense
    if (isset($_POST['delete_expense'])) {
        $stmt = $db->prepare("DELETE FROM expenses WHERE id = ?");
        $stmt->execute([$_POST['expense_id']]);
    }
    header('Location: finance.php');
    exit;
}

// Récupérer tous les projets (pour lier les dépenses et calculer revenus)
$projects = $db->query("SELECT * FROM projects")->fetchAll();

// Calcul des revenus des projets payés
$total_project_revenue = 0;
foreach($projects as $p) {
    if($p['payment_status'] === 'paye') {
        $price = match($p['promo_pack']) {
            'Starter' => 50,
            'Standard' => 90,
            'Pro' => 150,
            'Premium' => 350,
            default => 0
        };
        $total_project_revenue += $price;
    }
}

// Récupérer toutes les dépenses
$expensesRaw = $db->query("SELECT e.*, p.title as project_title FROM expenses e JOIN projects p ON e.project_id = p.id ORDER BY e.date_depense DESC")->fetchAll();
$total_expenses = array_sum(array_column($expensesRaw, 'montant'));

// Récupérer toutes les entrées d'argent externes
$incomesRaw = $db->query("SELECT * FROM incomes ORDER BY date_entree DESC")->fetchAll();
$total_external_income = array_sum(array_column($incomesRaw, 'montant'));

// Caisse (Argent disponible)
$available_cash = ($total_project_revenue + $total_external_income) - $total_expenses;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMA HUB - Rapports Financiers</title>
    <link rel="icon" type="image/png" href="../../asset/icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; min-height: 100vh; }
        .bg-glow { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .glow-spot { position: fixed; width: 40vw; height: 40vw; background: radial-gradient(circle, rgba(34, 197, 94, 0.05) 0%, transparent 70%); border-radius: 50%; z-index: -1; filter: blur(80px); pointer-events: none; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; display: flex; flex-direction: column; padding: 2rem 1.5rem; }
        .main-content { margin-left: 280px; padding: 2rem; min-height: 100vh; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; font-size: 0.9rem; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 1.5rem; padding: 1.5rem; }
        .custom-select, .search-bar { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 0.75rem; color: #fff; padding: 0.5rem 1rem; outline: none; }
        .admin-table { width: 100%; border-collapse: separate; border-spacing: 0 0.75rem; }
        .admin-table tr { background: rgba(255, 255, 255, 0.02); }
        .admin-table td, .admin-table th { padding: 1rem 1.5rem; }
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
            <div class="flex items-center gap-4 mb-8 px-2">
                <div class="w-10 h-10 rounded-full bg-orange-500/10 border border-orange-500/20 flex items-center justify-center text-orange-500">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div>
                    <p class="text-sm font-bold text-white"><?= explode(' ', $_SESSION['user_name'])[0] ?></p>
                    <p class="text-[10px] text-gray-500 uppercase font-black tracking-widest"><?= $_SESSION['role'] ?></p>
                </div>
            </div>
            <a href="../../auth/logout.php" class="nav-link !text-red-500 hover:!bg-red-500/10">
                <i class="fas fa-power-off"></i>
                Déconnexion
            </a>
        </div>
    </aside>

    <main class="main-content">
        <header class="mb-12">
            <h2 class="text-4xl font-black tracking-tighter text-white">Rapports <span class="text-orange-500">Financiers</span></h2>
            <p class="text-gray-400 mt-2">Suivez les entrées, sorties et l'état de la caisse.</p>
        </header>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <div class="glass-card">
                <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest mb-2">Revenus Projets</p>
                <p class="text-4xl font-black text-green-500"><?= number_format($total_project_revenue, 0, '.', ' ') ?>$</p>
            </div>
            <div class="glass-card">
                <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest mb-2">Entrées Externes</p>
                <p class="text-4xl font-black text-blue-500"><?= number_format($total_external_income, 0, '.', ' ') ?>$</p>
            </div>
            <div class="glass-card">
                <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest mb-2">Total Dépenses</p>
                <p class="text-4xl font-black text-red-500"><?= number_format($total_expenses, 0, '.', ' ') ?>$</p>
            </div>
            <div class="glass-card border-orange-500/30 bg-orange-500/5">
                <p class="text-[10px] text-orange-500 font-black uppercase tracking-widest mb-2">Caisse Actuelle</p>
                <p class="text-4xl font-black text-white"><?= number_format($available_cash, 0, '.', ' ') ?>$</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Entrées Externes -->
            <section class="glass-card">
                <h3 class="text-lg font-bold mb-6 flex items-center gap-2">
                    <i class="fas fa-plus-circle text-green-500"></i>
                    Apports Externes
                </h3>
                <form method="POST" class="mb-8 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <input type="text" name="motif" required class="search-bar w-full" placeholder="Motif">
                        <input type="number" name="montant" step="0.01" required class="search-bar w-full" placeholder="Montant ($)">
                    </div>
                    <input type="date" name="date_entree" required class="search-bar w-full" value="<?= date('Y-m-d') ?>">
                    <button type="submit" name="add_income" class="w-full bg-green-500/10 text-green-500 font-bold py-3 rounded-xl border border-green-500/20">Ajouter Entrée</button>
                </form>
                <div class="max-h-[300px] overflow-y-auto">
                    <table class="admin-table w-full text-left">
                        <tbody>
                            <?php foreach ($incomesRaw as $in): ?>
                                <tr>
                                    <td>
                                        <p class="text-xs font-bold"><?= htmlspecialchars($in['motif']) ?></p>
                                        <p class="text-[9px] text-gray-500"><?= date('d/m/Y', strtotime($in['date_entree'])) ?></p>
                                    </td>
                                    <td class="text-xs font-black text-green-500">+<?= number_format($in['montant'], 2) ?>$</td>
                                    <td>
                                        <form method="POST">
                                            <input type="hidden" name="income_id" value="<?= $in['id'] ?>">
                                            <button type="submit" name="delete_income" class="text-gray-600 hover:text-red-500"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Dépenses -->
            <section class="glass-card">
                <h3 class="text-lg font-bold mb-6 flex items-center gap-2">
                    <i class="fas fa-minus-circle text-red-500"></i>
                    Dépenses Projets
                </h3>
                <form method="POST" class="mb-8 space-y-4">
                    <select name="project_id" required class="custom-select w-full">
                        <?php foreach ($projects as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="grid grid-cols-2 gap-4">
                        <input type="text" name="motif" required class="search-bar w-full" placeholder="Motif">
                        <input type="number" name="montant" step="0.01" required class="search-bar w-full" placeholder="Montant">
                    </div>
                    <input type="date" name="date_depense" required class="search-bar w-full" value="<?= date('Y-m-d') ?>">
                    <button type="submit" name="add_expense" class="w-full bg-red-500/10 text-red-500 font-bold py-3 rounded-xl border border-red-500/20">Ajouter Dépense</button>
                </form>
                <div class="max-h-[300px] overflow-y-auto">
                    <table class="admin-table w-full text-left">
                        <tbody>
                            <?php foreach ($expensesRaw as $ex): ?>
                                <tr>
                                    <td>
                                        <p class="text-xs font-bold"><?= htmlspecialchars($ex['motif']) ?></p>
                                        <p class="text-[9px] text-gray-500"><?= htmlspecialchars($ex['project_title']) ?></p>
                                    </td>
                                    <td class="text-xs font-black text-red-500">-<?= number_format($ex['montant'], 2) ?>$</td>
                                    <td>
                                        <form method="POST">
                                            <input type="hidden" name="expense_id" value="<?= $ex['id'] ?>">
                                            <button type="submit" name="delete_expense" class="text-gray-600 hover:text-red-500"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>

    <script>
        const glow = document.getElementById('glow');
        document.addEventListener('mousemove', (e) => {
            glow.style.left = (e.clientX - 200) + 'px';
            glow.style.top = (e.clientY - 200) + 'px';
        });
    </script>
</body>
</html>
