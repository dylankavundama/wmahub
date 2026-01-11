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
    if (isset($_POST['update_payment_info'])) {
        $bank_account = $_POST['bank_account'] ?? '';
        $mobile_money = $_POST['mobile_money_account'] ?? '';
        
        $stmt = $db->prepare("UPDATE users SET bank_account = ?, mobile_money_account = ? WHERE id = ?");
        $stmt->execute([$bank_account, $mobile_money, $_POST['user_id']]);
        $_SESSION['success_message'] = "Informations de paiement mises à jour avec succès !";
    }
    header('Location: salaries.php' . ($_GET ? '?' . http_build_query($_GET) : ''));
    exit;
}

// Récupérer les filtres
$filter_month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$filter_year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Récupérer tous les employés actifs
$employees = $db->query("SELECT * FROM users WHERE role = 'employe' AND is_active = 1 ORDER BY name")->fetchAll();

// Pour chaque employé, vérifier s'il a encaissé pour le mois/année sélectionné
$employees_data = [];
foreach ($employees as $emp) {
    $stmt = $db->prepare("
        SELECT * FROM salary_withdrawals 
        WHERE user_id = ? 
        AND MONTH(date_encaissement) = ? 
        AND YEAR(date_encaissement) = ?
    ");
    $stmt->execute([$emp['id'], $filter_month, $filter_year]);
    $withdrawal = $stmt->fetch();
    
    $employees_data[] = [
        'employee' => $emp,
        'withdrawal' => $withdrawal,
        'has_withdrawn' => $withdrawal !== false
    ];
}

// Statistiques
$total_employees = count($employees);
$total_withdrawn = count(array_filter($employees_data, fn($e) => $e['has_withdrawn']));
$total_pending = $total_employees - $total_withdrawn;
$total_salary_paid = array_sum(array_map(fn($e) => $e['has_withdrawn'] ? $e['employee']['salary'] : 0, $employees_data));
$total_salary_pending = array_sum(array_map(fn($e) => !$e['has_withdrawn'] ? $e['employee']['salary'] : 0, $employees_data));

$months = [
    1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
    5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
    9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMA HUB - Gestion Salaires</title>
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
            <h2 class="text-4xl font-black tracking-tighter text-white">Gestion des <span class="text-orange-500">Salaires</span></h2>
            <p class="text-gray-400 mt-2">Gérez les informations de paiement et suivez les encaissements mensuels.</p>
        </header>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="mb-6 p-4 bg-green-500/10 border border-green-500/20 rounded-xl flex items-center gap-3">
                <i class="fas fa-check-circle text-green-500 text-xl"></i>
                <p class="text-green-500 font-bold"><?= $_SESSION['success_message'] ?></p>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <!-- Filtres -->
        <div class="glass-card mb-8">
            <form method="GET" class="flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-[200px]">
                    <label class="text-[10px] font-black uppercase text-gray-500 mb-2 block">Mois</label>
                    <select name="month" class="custom-select w-full">
                        <?php foreach ($months as $num => $name): ?>
                            <option value="<?= $num ?>" <?= $filter_month === $num ? 'selected' : '' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="text-[10px] font-black uppercase text-gray-500 mb-2 block">Année</label>
                    <select name="year" class="custom-select w-full">
                        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                            <option value="<?= $y ?>" <?= $filter_year === $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-6 rounded-lg transition-all">
                    <i class="fas fa-filter mr-2"></i>Appliquer
                </button>
            </form>
        </div>

        <!-- Statistiques -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="glass-card">
                <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest mb-2">Total Employés</p>
                <p class="text-4xl font-black text-white"><?= $total_employees ?></p>
            </div>
            <div class="glass-card border-green-500/20 bg-green-500/5">
                <p class="text-[10px] text-green-500 font-black uppercase tracking-widest mb-2">Encaissés</p>
                <p class="text-4xl font-black text-white"><?= $total_withdrawn ?></p>
                <p class="text-xs text-gray-500 mt-2"><?= number_format($total_salary_paid, 0, '.', ' ') ?>$</p>
            </div>
            <div class="glass-card border-amber-500/20 bg-amber-500/5">
                <p class="text-[10px] text-amber-500 font-black uppercase tracking-widest mb-2">En Attente</p>
                <p class="text-4xl font-black text-white"><?= $total_pending ?></p>
                <p class="text-xs text-gray-500 mt-2"><?= number_format($total_salary_pending, 0, '.', ' ') ?>$</p>
            </div>
            <div class="glass-card border-orange-500/20 bg-orange-500/5">
                <p class="text-[10px] text-orange-500 font-black uppercase tracking-widest mb-2">Période</p>
                <p class="text-2xl font-black text-white"><?= $months[$filter_month] ?> <?= $filter_year ?></p>
            </div>
        </div>

        <!-- Tableau des employés -->
        <div class="glass-card p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="admin-table w-full text-left">
                    <thead class="text-[10px] text-gray-500 uppercase tracking-widest">
                        <tr>
                            <th class="px-6 py-4 border-b border-white/5">Employé</th>
                            <th class="px-6 py-4 border-b border-white/5">Salaire</th>
                            <th class="px-6 py-4 border-b border-white/5">Compte Bancaire</th>
                            <th class="px-6 py-4 border-b border-white/5">Mobile Money</th>
                            <th class="px-6 py-4 border-b border-white/5">Statut</th>
                            <th class="px-6 py-4 border-b border-white/5">Date Encaissement</th>
                            <th class="px-6 py-4 border-b border-white/5">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees_data as $data): ?>
                            <?php $emp = $data['employee']; ?>
                            <tr class="border-b border-white/5">
                                <td class="px-6 py-4">
                                    <p class="text-sm font-bold text-white"><?= htmlspecialchars($emp['name']) ?></p>
                                    <p class="text-[10px] text-gray-500"><?= htmlspecialchars($emp['email']) ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-black text-green-500"><?= number_format($emp['salary'], 0, '.', ' ') ?>$</p>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if (!empty($emp['bank_account'])): ?>
                                        <input type="text" name="bank_account" form="form-user-<?= $emp['id'] ?>"
                                               value="<?= htmlspecialchars($emp['bank_account']) ?>" 
                                               class="search-bar text-xs w-full" placeholder="N° compte bancaire">
                                    <?php else: ?>
                                        <div class="flex items-center gap-2 w-full">
                                            <p class="text-xs text-gray-600 italic">Pas disponible</p>
                                            <button type="button" onclick="this.parentElement.innerHTML='<input type=\'text\' name=\'bank_account\' form=\'form-user-<?= $emp['id'] ?>\' class=\'search-bar text-xs w-full\' placeholder=\'N° compte bancaire\' autofocus>'" 
                                                    class="text-orange-500 hover:text-orange-600 text-xs text-nowrap">
                                                <i class="fas fa-plus-circle"></i> Ajouter
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if (!empty($emp['mobile_money_account'])): ?>
                                        <input type="text" name="mobile_money_account" form="form-user-<?= $emp['id'] ?>"
                                               value="<?= htmlspecialchars($emp['mobile_money_account']) ?>" 
                                               class="search-bar text-xs w-full" placeholder="N° mobile money">
                                    <?php else: ?>
                                        <div class="flex items-center gap-2 w-full">
                                            <p class="text-xs text-gray-600 italic">Pas disponible</p>
                                            <button type="button" onclick="this.parentElement.innerHTML='<input type=\'text\' name=\'mobile_money_account\' form=\'form-user-<?= $emp['id'] ?>\' class=\'search-bar text-xs w-full\' placeholder=\'N° mobile money\' autofocus>'" 
                                                    class="text-orange-500 hover:text-orange-600 text-xs text-nowrap">
                                                <i class="fas fa-plus-circle"></i> Ajouter
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($data['has_withdrawn']): ?>
                                        <span class="text-[9px] font-black uppercase px-3 py-1 rounded-full bg-green-500/10 text-green-500 border border-green-500/20">
                                            <i class="fas fa-check-circle mr-1"></i>Encaissé
                                        </span>
                                    <?php else: ?>
                                        <span class="text-[9px] font-black uppercase px-3 py-1 rounded-full bg-amber-500/10 text-amber-500 border border-amber-500/20">
                                            <i class="fas fa-clock mr-1"></i>En attente
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($data['has_withdrawn']): ?>
                                        <p class="text-xs text-gray-400"><?= date('d/m/Y', strtotime($data['withdrawal']['date_encaissement'])) ?></p>
                                    <?php else: ?>
                                        <p class="text-xs text-gray-600">-</p>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <form method="POST" id="form-user-<?= $emp['id'] ?>">
                                        <input type="hidden" name="user_id" value="<?= $emp['id'] ?>">
                                        <input type="hidden" name="update_payment_info" value="1">

                                        <button type="submit" 
                                                class="p-2 bg-orange-500/10 text-orange-500 rounded-lg hover:bg-orange-500/20 transition-all" 
                                                title="Sauvegarder">
                                            <i class="fas fa-save"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($employees_data)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <i class="fas fa-users text-4xl text-gray-800 mb-4 block"></i>
                                    <p class="text-xs text-gray-500 uppercase font-black tracking-widest">Aucun employé actif</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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
