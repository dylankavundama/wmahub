<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès admin uniquement
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = getDBConnection();

// Auto-création de la table si elle n'existe pas
$db->exec("CREATE TABLE IF NOT EXISTS accounting_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('income', 'expense') NOT NULL,
    category VARCHAR(100) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    description TEXT,
    transaction_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Traitement de l'ajout d'une transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_transaction'])) {
    $type = $_POST['type'] ?? 'income';
    $category = $_POST['category'] ?? 'Autre';
    $amount = $_POST['amount'] ?? 0;
    $description = $_POST['description'] ?? '';
    $date = !empty($_POST['date']) ? $_POST['date'] : date('Y-m-d');

    if ($amount > 0) {
        $stmt = $db->prepare("INSERT INTO accounting_transactions (type, category, amount, description, transaction_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$type, $category, $amount, $description, $date]);
        header('Location: comptabilite.php?success=1');
        exit;
    }
}

// Suppression d'une transaction
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM accounting_transactions WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header('Location: comptabilite.php');
    exit;
}

// Récupération des filtres
$f_type = $_GET['f_type'] ?? 'all';
$f_category = $_GET['f_category'] ?? 'all';
$f_month = $_GET['f_month'] ?? 'all';
$f_date = $_GET['f_date'] ?? '';

// Construction de la requête avec filtres
$query = "SELECT * FROM accounting_transactions WHERE 1=1";
$params = [];

if ($f_type !== 'all') {
    $query .= " AND type = ?";
    $params[] = $f_type;
}
if ($f_category !== 'all') {
    $query .= " AND category = ?";
    $params[] = $f_category;
}
if ($f_month !== 'all') {
    $query .= " AND DATE_FORMAT(transaction_date, '%Y-%m') = ?";
    $params[] = $f_month;
}
if (!empty($f_date)) {
    $query .= " AND transaction_date = ?";
    $params[] = $f_date;
}

$query .= " ORDER BY transaction_date DESC, id DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Liste des mois disponibles pour le filtre
$available_months = $db->query("SELECT DISTINCT DATE_FORMAT(transaction_date, '%Y-%m') as m FROM accounting_transactions ORDER BY m DESC")->fetchAll();

// Stats globales
$total_income = $db->query("SELECT SUM(amount) FROM accounting_transactions WHERE type = 'income'")->fetchColumn() ?: 0;
$total_expense = $db->query("SELECT SUM(amount) FROM accounting_transactions WHERE type = 'expense'")->fetchColumn() ?: 0;
$balance = $total_income - $total_expense;

// Revenus du mois en cours
$current_month = date('Y-m');
$stmt_month_income = $db->prepare("SELECT SUM(amount) FROM accounting_transactions WHERE type = 'income' AND DATE_FORMAT(transaction_date, '%Y-%m') = ?");
$stmt_month_income->execute([$current_month]);
$month_income = $stmt_month_income->fetchColumn() ?: 0;

$stmt_month_expense = $db->prepare("SELECT SUM(amount) FROM accounting_transactions WHERE type = 'expense' AND DATE_FORMAT(transaction_date, '%Y-%m') = ?");
$stmt_month_expense->execute([$current_month]);
$month_expense = $stmt_month_expense->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comptabilité - WMA HUB</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin-shared.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; min-height: 100vh; }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 24px; padding: 24px; }
        .sidebar { width: 280px; background: rgba(255,255,255,0.02); height: 100vh; position: fixed; border-right: 1px solid rgba(255,255,255,0.05); padding: 2rem 1.5rem; z-index: 100; }
        .main-content { margin-left: 280px; padding: 2rem; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4); border-radius: 1rem; transition: all 0.3s; text-decoration: none; font-size: 0.9rem; margin-bottom: 0.5rem; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; transform: translateX(5px); }
        input, select, textarea { background: rgba(255,255,255,0.05) !important; border: 1px solid rgba(255,255,255,0.1) !important; color: white !important; border-radius: 12px !important; padding: 12px !important; outline: none; }
        input:focus, select:focus { border-color: #ff6600 !important; }
        .btn-primary { background: linear-gradient(135deg, #ff6600, #ff9900); color: white; padding: 12px 24px; border-radius: 12px; font-weight: 700; transition: all 0.3s; border: none; cursor: pointer; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(255, 102, 0, 0.2); }
        
        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="flex items-center gap-4 mb-12 px-2">
            <img src="../../asset/trans.png" alt="Logo" class="h-10">
            <div>
                <h1 class="text-xl font-black bg-gradient-to-r from-orange-500 to-orange-300 bg-clip-text text-transparent tracking-tighter leading-tight">WMA HUB</h1>
                <p class="text-[8px] text-gray-500 font-bold uppercase tracking-[1px] -mt-1">We move, WMAFam</p>
            </div>
        </div>
        <nav class="flex-1 overflow-y-auto">
            <a href="index.php" class="nav-link"><i class="fas fa-layer-group"></i> Gestion Projets</a>
            <a href="subscriptions.php" class="nav-link"><i class="fas fa-crown"></i> Abonnements</a>
            <a href="comptabilite.php" class="nav-link active"><i class="fas fa-calculator"></i> Comptabilité</a>
            <a href="finance.php" class="nav-link"><i class="fas fa-chart-pie"></i> Rapports</a>
            <a href="users.php" class="nav-link"><i class="fas fa-user-friends"></i> Utilisateurs</a>
            <div class="mt-8 pt-8 border-t border-white/5">
                <a href="../../auth/logout.php" class="nav-link text-red-500"><i class="fas fa-power-off"></i> Déconnexion</a>
            </div>
        </nav>
    </aside>

    <main class="main-content">
        <header class="flex flex-col md:flex-row md:items-center justify-between gap-8 mb-12">
            <div>
                <h2 class="text-4xl font-black tracking-tighter text-white">Gestion <span class="text-orange-500">Comptable</span></h2>
                <p class="text-gray-400 mt-2">Suivi financier et flux de trésorerie en temps réel.</p>
            </div>
            <div class="flex items-center gap-4">
                <button onclick="window.location.reload()" class="bg-white/5 hover:bg-white/10 text-white p-3 rounded-xl transition-all border border-white/10"><i class="fas fa-sync-alt"></i></button>
                <?php include '../../includes/header_notifications.php'; ?>
            </div>
        </header>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            <div class="glass-card">
                <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest mb-2">Solde Total (Net)</p>
                <p class="text-4xl font-black <?= $balance >= 0 ? 'text-green-500' : 'text-red-500' ?>"><?= number_format($balance, 2, '.', ' ') ?> $</p>
                <p class="text-[10px] text-gray-400 mt-2">Cumul historique global</p>
            </div>
            <div class="glass-card">
                <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest mb-2">Recettes (<?= date('F') ?>)</p>
                <p class="text-4xl font-black text-white"><?= number_format($month_income, 2, '.', ' ') ?> $</p>
                <p class="text-[10px] text-green-500 mt-2 font-bold"><i class="fas fa-arrow-up mr-1"></i> Flux entrant</p>
            </div>
            <div class="glass-card">
                <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest mb-2">Dépenses (<?= date('F') ?>)</p>
                <p class="text-4xl font-black text-white"><?= number_format($month_expense, 2, '.', ' ') ?> $</p>
                <p class="text-[10px] text-red-500 mt-2 font-bold"><i class="fas fa-arrow-down mr-1"></i> Flux sortant</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Formulaire -->
            <div class="glass-card h-fit">
                <h3 class="text-lg font-bold mb-6 flex items-center gap-2"><i class="fas fa-plus-circle text-orange-500"></i> Nouvelle Opération</h3>
                <form action="" method="POST">
                    <div class="space-y-5">
                        <div class="flex flex-col">
                            <label class="text-[10px] font-black uppercase text-gray-500 mb-2">Type de mouvement</label>
                            <select name="type" class="w-full" required>
                                <option value="income">Recette (+)</option>
                                <option value="expense">Dépense (-)</option>
                            </select>
                        </div>
                        <div class="flex flex-col">
                            <label class="text-[10px] font-black uppercase text-gray-500 mb-2">Catégorie</label>
                            <select name="category" class="w-full" required>
                                <option value="Distribution">Distribution</option>
                                <option value="OneRpm">OneRpm</option>
                                <option value="Abonnement">Abonnement</option>
                                <option value="Marketing">Marketing</option>
                                <option value="Salaire">Salaire</option>
                                <option value="Serveur/Tech">Serveur/Tech</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>
                        <div class="flex flex-col">
                            <label class="text-[10px] font-black uppercase text-gray-500 mb-2">Montant ($)</label>
                            <input type="number" name="amount" step="0.01" class="w-full text-xl font-bold" placeholder="0.00" required>
                        </div>
                        <div class="flex flex-col">
                            <label class="text-[10px] font-black uppercase text-gray-500 mb-2">Date</label>
                            <input type="date" name="date" class="w-full" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="flex flex-col">
                            <label class="text-[10px] font-black uppercase text-gray-500 mb-2">Description</label>
                            <textarea name="description" class="w-full h-24 resize-none" placeholder="Détails de l'opération..."></textarea>
                        </div>
                        <button type="submit" name="add_transaction" class="btn-primary w-full mt-4 uppercase tracking-widest text-xs">Enregistrer l'opération</button>
                    </div>
                </form>
            </div>

            <!-- Historique -->
            <div class="lg:col-span-2 glass-card">
                <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-6 mb-8 border-b border-white/5 pb-6">
                    <h3 class="text-lg font-bold flex items-center gap-2"><i class="fas fa-history text-orange-500"></i> Historique</h3>
                    
                    <form method="GET" class="flex flex-wrap items-center gap-2">
                        <select name="f_type" class="!py-1.5 !text-[10px] !px-3" onchange="this.form.submit()">
                            <option value="all">Tous types</option>
                            <option value="income" <?= $f_type === 'income' ? 'selected' : '' ?>>Recettes</option>
                            <option value="expense" <?= $f_type === 'expense' ? 'selected' : '' ?>>Dépenses</option>
                        </select>
                        
                        <select name="f_month" class="!py-1.5 !text-[10px] !px-3" onchange="this.form.submit()">
                            <option value="all">Tous les mois</option>
                            <?php foreach ($available_months as $m): ?>
                                <option value="<?= $m['m'] ?>" <?= $f_month === $m['m'] ? 'selected' : '' ?>><?= $m['m'] ?></option>
                            <?php endforeach; ?>
                        </select>

                        <?php if ($f_type !== 'all' || $f_month !== 'all' || !empty($f_date)): ?>
                            <a href="comptabilite.php" class="text-[10px] font-black uppercase text-orange-500 hover:text-orange-400"><i class="fas fa-times mr-1"></i>Reset</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-gray-600 text-[10px] uppercase font-black tracking-widest">
                                <th class="pb-4 px-2">Date</th>
                                <th class="pb-4 px-2">Catégorie</th>
                                <th class="pb-4 px-2">Description</th>
                                <th class="pb-4 px-2">Montant</th>
                                <th class="pb-4 px-2 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php if (empty($transactions)): ?>
                                <tr><td colspan="5" class="py-20 text-center text-gray-500 italic">Aucune opération trouvée.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($transactions as $t): ?>
                                <tr class="border-b border-white/5 hover:bg-white/[0.02] transition-colors group">
                                    <td class="py-4 px-2 text-gray-400 font-mono text-xs"><?= date('d/m/Y', strtotime($t['transaction_date'])) ?></td>
                                    <td class="py-4 px-2">
                                        <span class="px-3 py-1 rounded-lg text-[9px] font-black uppercase bg-white/5 border border-white/10">
                                            <?= htmlspecialchars($t['category']) ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-2 text-gray-300 text-xs"><?= htmlspecialchars($t['description']) ?: '-' ?></td>
                                    <td class="py-4 px-2 font-black <?= $t['type'] === 'income' ? 'text-green-500' : 'text-red-500' ?>">
                                        <?= $t['type'] === 'income' ? '+' : '-' ?> <?= number_format($t['amount'], 2, '.', ' ') ?> $
                                    </td>
                                    <td class="py-4 px-2 text-right">
                                        <a href="?delete=<?= $t['id'] ?>" onclick="return confirm('Confirmer la suppression ?')" class="w-8 h-8 rounded-lg bg-red-500/10 text-red-500/40 flex items-center justify-center hover:bg-red-500 hover:text-white transition-all ml-auto">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
