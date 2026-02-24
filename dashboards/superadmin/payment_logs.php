<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint aux super admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = getDBConnection();

// Récupérer les infos du super admin
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();

// Chemins des fichiers de log
$flexpayLog = __DIR__ . '/../../api/logs/flexpay.log';
$checksLog = __DIR__ . '/../../api/logs/payment_checks.log';

// Fonction pour lire et inverser les lignes (plus récent en haut)
function readLogFile($path, $limit = 200) {
    if (!file_exists($path)) return [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    return array_reverse(array_slice($lines, -$limit));
}

$flexpayLines = readLogFile($flexpayLog);
$checksLines = readLogFile($checksLog);

$activeTab = $_GET['tab'] ?? 'flexpay';

// Logique pour vider les logs
if (isset($_GET['action']) && isset($_GET['type'])) {
    $type = $_GET['type'];
    $fileToClear = ($type === 'flexpay') ? $flexpayLog : (($type === 'checks') ? $checksLog : null);
    
    if ($fileToClear && file_exists($fileToClear)) {
        file_put_contents($fileToClear, "");
        // Redirection propre pour éviter le resoumission
        header("Location: payment_logs.php?tab=" . ($type === 'flexpay' ? 'flexpay' : 'checks') . "&cleared=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs de Paiement - WMA Hub</title>
    <link rel="icon" type="image/jpeg" href="../../asset/placeholder.jpg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; min-height: 100vh; }
        .log-line { font-family: 'Courier New', monospace; font-size: 0.85rem; padding: 0.5rem 1rem; border-left: 3px solid transparent; }
        .log-line:hover { background: rgba(255,255,255,0.05); }
        .log-entry { background: rgba(255,255,255,0.02); border-radius: 8px; margin-bottom: 0.5rem; border: 1px solid rgba(255,255,255,0.05); }
        .log-success { border-left-color: #22c55e; }
        .log-error { border-left-color: #ef4444; background: rgba(239, 68, 68, 0.05); }
        .log-warning { border-left-color: #f59e0b; }
        .log-info { border-left-color: #3b82f6; }
    </style>
</head>
<body>
    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-gradient-to-b from-gray-900 to-black h-screen fixed left-0 top-0 border-r border-gray-800 p-6">
            <div class="mb-8">
                <img src="../../asset/logo.png" alt="Logo" class="h-12 mb-2">
                <h1 class="text-xl font-black text-orange-500">WMA HUB</h1>
                <p class="text-xs text-gray-500 uppercase tracking-widest">Super Admin</p>
            </div>
            <nav class="space-y-2">
                <a href="index.php" class="block px-4 py-2 rounded-lg text-gray-400 hover:bg-orange-500/10 hover:text-orange-500"><i class="fas fa-chart-line mr-3"></i>Dashboard</a>
                <a href="payments.php" class="block px-4 py-2 rounded-lg text-gray-400 hover:bg-orange-500/10 hover:text-orange-500"><i class="fas fa-history mr-3"></i>Historique Paiements</a>
                <a href="payment_logs.php" class="block px-4 py-2 rounded-lg bg-orange-500/10 text-orange-500"><i class="fas fa-file-alt mr-3"></i>Logs Paiement</a>
                <a href="../../auth/logout.php" class="block px-4 py-2 rounded-lg text-red-500 hover:bg-red-500/10"><i class="fas fa-power-off mr-3"></i>Déconnexion</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="ml-64 flex-1 p-8">
            <header class="mb-8">
                <h2 class="text-4xl font-black tracking-tight">Logs de <span class="text-orange-500">Paiement</span></h2>
                <p class="text-gray-400 mt-2">Surveillance en temps réel des callbacks FlexPay et des vérifications de statut</p>
            </header>

            <!-- Tabs -->
            <div class="flex gap-4 mb-6 border-b border-gray-800">
                <a href="?tab=flexpay" class="px-6 py-3 font-bold <?= $activeTab === 'flexpay' ? 'text-orange-500 border-b-2 border-orange-500' : 'text-gray-500 hover:text-gray-300' ?>">
                    <i class="fas fa-bell mr-2"></i>FlexPay Callback (<?= count($flexpayLines) ?>)
                </a>
                <a href="?tab=checks" class="px-6 py-3 font-bold <?= $activeTab === 'checks' ? 'text-orange-500 border-b-2 border-orange-500' : 'text-gray-500 hover:text-gray-300' ?>">
                    <i class="fas fa-sync mr-2"></i>Vérifications Polling (<?= count($checksLines) ?>)
                </a>
            </div>

            <!-- Log Display -->
            <div class="bg-black/40 backdrop-blur-xl rounded-2xl p-6 border border-gray-800">
                <?php if ($activeTab === 'flexpay'): ?>
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-3">
                        <i class="fas fa-server text-orange-500"></i>
                        FlexPay Callback Log
                        <span class="text-xs text-gray-500 font-normal">(<?= file_exists($flexpayLog) ? 'Actif' : 'Aucun log' ?>)</span>
                    </h3>
                    <?php if (empty($flexpayLines)): ?>
                        <p class="text-gray-500 text-center py-8">Aucun log disponible pour le moment.</p>
                    <?php else: ?>
                        <div class="space-y-2 max-h-[70vh] overflow-y-auto">
                            <?php foreach ($flexpayLines as $line): 
                                $class = 'log-info';
                                if (stripos($line, 'SUCCESS') !== false) $class = 'log-success';
                                elseif (stripos($line, 'ERROR') !== false) $class = 'log-error';
                                elseif (stripos($line, 'FAILED') !== false) $class = 'log-warning';
                            ?>
                                <div class="log-entry log-line <?= $class ?>"><?= htmlspecialchars($line) ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <h3 class="text-xl font-bold mb-4 flex items-center gap-3">
                        <i class="fas fa-search text-blue-500"></i>
                        Vérifications Polling Log
                        <span class="text-xs text-gray-500 font-normal">(<?= file_exists($checksLog) ? 'Actif' : 'Aucun log' ?>)</span>
                    </h3>
                    <?php if (empty($checksLines)): ?>
                        <p class="text-gray-500 text-center py-8">Aucun log disponible pour le moment.</p>
                    <?php else: ?>
                        <div class="space-y-2 max-h-[70vh] overflow-y-auto">
                            <?php foreach ($checksLines as $line): 
                                $class = 'log-info';
                                if (stripos($line, 'SUCCESS') !== false) $class = 'log-success';
                                elseif (stripos($line, 'ERROR') !== false) $class = 'log-error';
                                elseif (stripos($line, 'CHECK REQUEST') !== false) $class = 'log-info';
                            ?>
                                <div class="log-entry log-line <?= $class ?>"><?= htmlspecialchars($line) ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Refresh Info -->
            <div class="mt-4 text-center">
                <p class="text-xs text-gray-600">
                    <i class="fas fa-sync-alt mr-2"></i>
                    Rafraîchissez cette page pour voir les nouveaux logs. Les 200 dernières entrées sont affichées.
                </p>
            </div>
        </main>
    </div>
</body>
</html>
