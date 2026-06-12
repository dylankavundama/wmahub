<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint aux admins uniquement
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = getDBConnection();

// Traitement des actions (Suppression / Purge)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $delete_id = (int)$_POST['delete_id'];
        $stmt_del = $db->prepare("DELETE FROM system_errors WHERE id = ?");
        $stmt_del->execute([$delete_id]);
        
        // Redirection en conservant les filtres GET
        $get_params = $_GET;
        header('Location: logs.php?' . http_build_query($get_params));
        exit;
    }

    if (isset($_POST['purge_logs'])) {
        $purge_type = $_POST['purge_type'] ?? 'all';
        
        if ($purge_type === 'filtered') {
            // Reconstruire les filtres pour supprimer uniquement les éléments filtrés
            $where = [];
            $params = [];
            
            $source = $_GET['source'] ?? 'all';
            if ($source === 'web') {
                $where[] = "message NOT LIKE '[MOBILE/%'";
            } elseif ($source === 'mobile') {
                $where[] = "message LIKE '[MOBILE/%'";
            }
            
            $level = $_GET['level'] ?? 'all';
            if ($level !== 'all') {
                if ($level === 'fatal') {
                    $where[] = "(message LIKE 'FATAL:%' OR message LIKE '[MOBILE/FATAL]%' OR message LIKE '[MOBILE/CRITICAL]%')";
                } elseif ($level === 'error') {
                    $where[] = "(message LIKE '[MOBILE/ERROR]%' OR (message NOT LIKE '[MOBILE/%' AND message NOT LIKE 'FATAL:%'))";
                } elseif ($level === 'warning') {
                    $where[] = "message LIKE '[MOBILE/WARNING]%'";
                } elseif ($level === 'info') {
                    $where[] = "message LIKE '[MOBILE/INFO]%'";
                }
            }
            
            $date_filter = $_GET['date'] ?? 'all';
            if ($date_filter === 'today') {
                $where[] = "DATE(created_at) = CURDATE()";
            } elseif ($date_filter === 'week') {
                $where[] = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            } elseif ($date_filter === 'month') {
                $where[] = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            }
            
            $search = $_GET['search'] ?? '';
            if ($search !== '') {
                $where[] = "(message LIKE ? OR file LIKE ? OR line LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if (!empty($where)) {
                $purge_sql = "DELETE FROM system_errors WHERE " . implode(" AND ", $where);
                $stmt_purge = $db->prepare($purge_sql);
                $stmt_purge->execute($params);
            }
        } else {
            // Purger absolument tout
            $db->query("DELETE FROM system_errors");
        }
        
        header('Location: logs.php');
        exit;
    }
}

// Récupération des filtres GET
$source = $_GET['source'] ?? 'all';
$level = $_GET['level'] ?? 'all';
$date_filter = $_GET['date'] ?? 'all';
$search = $_GET['search'] ?? '';

// Construction de la requête principale
$where = [];
$params = [];

if ($source === 'web') {
    $where[] = "message NOT LIKE '[MOBILE/%'";
} elseif ($source === 'mobile') {
    $where[] = "message LIKE '[MOBILE/%'";
}

if ($level !== 'all') {
    if ($level === 'fatal') {
        $where[] = "(message LIKE 'FATAL:%' OR message LIKE '[MOBILE/FATAL]%' OR message LIKE '[MOBILE/CRITICAL]%')";
    } elseif ($level === 'error') {
        $where[] = "(message LIKE '[MOBILE/ERROR]%' OR (message NOT LIKE '[MOBILE/%' AND message NOT LIKE 'FATAL:%'))";
    } elseif ($level === 'warning') {
        $where[] = "message LIKE '[MOBILE/WARNING]%'";
    } elseif ($level === 'info') {
        $where[] = "message LIKE '[MOBILE/INFO]%'";
    }
}

if ($date_filter === 'today') {
    $where[] = "DATE(created_at) = CURDATE()";
} elseif ($date_filter === 'week') {
    $where[] = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($date_filter === 'month') {
    $where[] = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
}

if ($search !== '') {
    $where[] = "(message LIKE ? OR file LIKE ? OR line LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql = "SELECT * FROM system_errors";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$errors = $stmt->fetchAll();

// Statistiques globales
$total_count = $db->query("SELECT COUNT(*) FROM system_errors")->fetchColumn();
$web_count = $db->query("SELECT COUNT(*) FROM system_errors WHERE message NOT LIKE '[MOBILE/%'")->fetchColumn();
$mobile_count = $db->query("SELECT COUNT(*) FROM system_errors WHERE message LIKE '[MOBILE/%'")->fetchColumn();
$critical_count = $db->query("SELECT COUNT(*) FROM system_errors WHERE message LIKE 'FATAL:%' OR message LIKE '[MOBILE/FATAL]%' OR message LIKE '[MOBILE/CRITICAL]%' OR (message NOT LIKE '[MOBILE/%' AND message NOT LIKE 'FATAL:%')")->fetchColumn();

// Fonction utilitaire pour parser les logs
function parseLogEntry($err) {
    $raw_message = $err['message'];
    $file = $err['file'];
    $line = $err['line'];
    $created_at = $err['created_at'];
    
    $source = 'Site Web';
    $source_icon = 'fas fa-desktop text-blue-400';
    $level = 'ERROR';
    $badge_class = 'bg-red-500/10 text-red-400 border-red-500/20';
    $message = $raw_message;
    $context = '';
    
    // Détecter si c'est un log mobile
    if (strpos($raw_message, '[MOBILE/') === 0) {
        $source = 'App Mobile';
        $source_icon = 'fas fa-mobile-alt text-purple-400';
        
        // Extraire le niveau [MOBILE/LEVEL]
        preg_match('/^\[MOBILE\/([^\]]+)\]\s*(.*)$/i', $raw_message, $matches);
        if (count($matches) >= 3) {
            $level = strtoupper($matches[1]);
            $rest = $matches[2];
            
            // Séparer le message du contexte si disponible
            $parts = explode(' | Context:', $rest, 2);
            $message = trim($parts[0]);
            if (count($parts) > 1) {
                $context = trim($parts[1]);
            }
        }
    } else {
        // Log Web
        if (strpos($raw_message, 'FATAL:') === 0) {
            $level = 'FATAL';
            $message = trim(substr($raw_message, 6));
        }
    }
    
    // Style du badge selon le niveau
    switch ($level) {
        case 'FATAL':
        case 'CRITICAL':
            $badge_class = 'bg-red-600/20 text-red-500 border-red-600/30 font-black shadow-sm shadow-red-500/10';
            break;
        case 'WARNING':
            $badge_class = 'bg-amber-500/10 text-amber-500 border-amber-500/20';
            break;
        case 'INFO':
            $badge_class = 'bg-blue-500/10 text-blue-400 border-blue-500/20';
            break;
        default:
            $badge_class = 'bg-red-500/10 text-red-400 border-red-500/20';
            break;
    }
    
    return [
        'id' => $err['id'],
        'source' => $source,
        'source_icon' => $source_icon,
        'level' => $level,
        'badge_class' => $badge_class,
        'message' => $message,
        'context' => $context,
        'file' => $file,
        'line' => $line,
        'created_at' => $created_at
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journaux d'Erreurs (Logs) - WMA HUB</title>
    
    <!-- Scripts et CSS Prioritaires -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="/asset/icon.png">
    <link rel="apple-touch-icon" href="/asset/icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin-shared.css">
    
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
            background: #0a0a0c !important; 
            color: #fff; 
            min-height: 100vh; 
            margin: 0;
            overflow-x: hidden;
        }

        #wma-global-loader {
            position: fixed;
            inset: 0;
            background: #0a0a0c;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100000;
            transition: opacity 0.5s ease;
        }

        .loader-spin {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(255, 102, 0, 0.1);
            border-top-color: #ff6600;
            border-radius: 50%;
            animation: wma-spin 1s linear infinite;
        }

        @keyframes wma-spin { to { transform: rotate(360deg); } }
        
        .bg-glow { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .glow-spot { position: fixed; width: 40vw; height: 40vw; background: radial-gradient(circle, rgba(255, 102, 0, 0.05) 0%, transparent 70%); border-radius: 50%; z-index: -1; filter: blur(80px); pointer-events: none; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; display: flex; flex-direction: column; padding: 2rem 1.5rem; transition: all 0.3s ease; }
        .sidebar nav { overflow-y: auto; overflow-x: hidden; padding-right: 0.5rem; }
        .main-content { margin-left: 280px; padding: 2rem; min-height: 100vh; transition: all 0.3s ease; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; font-size: 0.9rem; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; transform: translateX(5px); }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 1.5rem; padding: 1.5rem; }
        
        @media (max-width: 1024px) { 
            .sidebar { transform: translateX(-100%); } 
            .sidebar.active { transform: translateX(0); width: 280px; padding: 2rem 1.5rem; }
            .sidebar-overlay.active { display: block; }
            .main-content { margin-left: 0; padding: 1.5rem; } 
            .mobile-header { display: flex; }
        }

        .status-badge { 
            padding: 0.35rem 0.75rem; 
            border-radius: 99px; 
            font-size: 0.65rem; 
            font-weight: 800; 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
            border: 1px solid transparent;
        }
        
        .code-block {
            font-family: monospace;
            background: #111;
            padding: 1rem;
            border-radius: 0.75rem;
            border: 1px solid rgba(255,255,255,0.05);
            color: #94a3b8;
            max-height: 250px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div id="wma-global-loader">
        <div class="loader-spin"></div>
    </div>
    <div class="bg-glow"></div>
    <div id="glow" class="glow-spot"></div>

    <div class="mobile-header">
        <div class="flex items-center gap-3">
            <img src="../../asset/trans.png" alt="Logo" class="h-8">
            <span class="font-bold tracking-tighter">WMA ADMIN</span>
        </div>
        <button id="sidebarToggle" class="text-white text-2xl p-2"><i class="fas fa-bars"></i></button>
    </div>

    <div class="sidebar-overlay" id="overlay"></div>

    <!-- Inclure la barre latérale commune -->
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <header class="flex flex-col md:flex-row md:items-center justify-between gap-8 mb-12">
            <div>
                <h2 class="text-4xl font-black tracking-tighter text-white">Journal des <span class="text-orange-500">Erreurs</span></h2>
                <p class="text-gray-400 mt-2">Suivi, diagnostic et résolution des anomalies du site et de l'application mobile.</p>
            </div>
            <div class="flex items-center gap-4">
                <button onclick="window.location.reload()" class="bg-white/5 hover:bg-white/10 text-white p-3 rounded-xl transition-all border border-white/10" title="Actualiser"><i class="fas fa-sync-alt"></i></button>
                <button onclick="openPurgeModal()" class="bg-red-500/10 border border-red-500/20 text-red-500 px-4 py-3 rounded-xl hover:bg-red-500 hover:text-white transition-all text-xs font-bold flex items-center gap-2">
                    <i class="fas fa-trash-alt"></i> Purger les logs
                </button>
                <?php include '../../includes/header_notifications.php'; ?>
            </div>
        </header>

        <!-- Grille de Statistiques -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <div class="glass-card">
                <p class="text-[10px] text-gray-500 font-black uppercase tracking-widest mb-2">Total Erreurs</p>
                <div class="flex items-end justify-between">
                    <p class="text-4xl font-black text-white"><?= number_format($total_count) ?></p>
                    <i class="fas fa-exclamation-triangle text-orange-500 text-2xl"></i>
                </div>
            </div>
            <div class="glass-card border-red-500/20">
                <p class="text-[10px] text-red-400 font-black uppercase tracking-widest mb-2">Critiques / Fatales</p>
                <div class="flex items-end justify-between">
                    <p class="text-4xl font-black text-red-500"><?= number_format($critical_count) ?></p>
                    <i class="fas fa-skull text-red-500 text-2xl"></i>
                </div>
            </div>
            <div class="glass-card">
                <p class="text-[10px] text-blue-400 font-black uppercase tracking-widest mb-2">Erreurs Site Web</p>
                <div class="flex items-end justify-between">
                    <p class="text-4xl font-black text-white"><?= number_format($web_count) ?></p>
                    <i class="fas fa-desktop text-blue-400 text-2xl"></i>
                </div>
            </div>
            <div class="glass-card">
                <p class="text-[10px] text-purple-400 font-black uppercase tracking-widest mb-2">Erreurs App Mobile</p>
                <div class="flex items-end justify-between">
                    <p class="text-4xl font-black text-white"><?= number_format($mobile_count) ?></p>
                    <i class="fas fa-mobile-alt text-purple-400 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Filtres et Recherche -->
        <div class="glass-card mb-8">
            <form method="GET" class="flex flex-col lg:flex-row items-center justify-between gap-6">
                <!-- Recherche Libre -->
                <div class="relative w-full lg:w-96">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-500"></i>
                    <input type="text" name="search" class="custom-select pl-10 w-full" placeholder="Message, fichier, ligne..." value="<?= htmlspecialchars($search) ?>">
                </div>

                <!-- Autres filtres -->
                <div class="flex flex-wrap items-center gap-4 w-full lg:w-auto justify-end">
                    <div class="flex items-center gap-2">
                        <label class="text-[10px] font-black uppercase text-gray-500">Source:</label>
                        <select name="source" class="custom-select" onchange="this.form.submit()">
                            <option value="all" <?= $source === 'all' ? 'selected' : '' ?>>Toutes</option>
                            <option value="web" <?= $source === 'web' ? 'selected' : '' ?>>🖥️ Site Web</option>
                            <option value="mobile" <?= $source === 'mobile' ? 'selected' : '' ?>>📱 App Mobile</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <label class="text-[10px] font-black uppercase text-gray-500">Niveau:</label>
                        <select name="level" class="custom-select" onchange="this.form.submit()">
                            <option value="all" <?= $level === 'all' ? 'selected' : '' ?>>Tous</option>
                            <option value="fatal" <?= $level === 'fatal' ? 'selected' : '' ?>>FATAL / CRITICAL</option>
                            <option value="error" <?= $level === 'error' ? 'selected' : '' ?>>ERROR</option>
                            <option value="warning" <?= $level === 'warning' ? 'selected' : '' ?>>WARNING</option>
                            <option value="info" <?= $level === 'info' ? 'selected' : '' ?>>INFO</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <label class="text-[10px] font-black uppercase text-gray-500">Date:</label>
                        <select name="date" class="custom-select" onchange="this.form.submit()">
                            <option value="all" <?= $date_filter === 'all' ? 'selected' : '' ?>>Toutes les dates</option>
                            <option value="today" <?= $date_filter === 'today' ? 'selected' : '' ?>>Aujourd'hui</option>
                            <option value="week" <?= $date_filter === 'week' ? 'selected' : '' ?>>7 derniers jours</option>
                            <option value="month" <?= $date_filter === 'month' ? 'selected' : '' ?>>30 derniers jours</option>
                        </select>
                    </div>

                    <?php if ($source !== 'all' || $level !== 'all' || $date_filter !== 'all' || $search !== ''): ?>
                        <a href="logs.php" class="text-[10px] font-black uppercase text-orange-500 hover:text-orange-400 flex items-center gap-1">
                            <i class="fas fa-times-circle"></i> Reset
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Tableau des Logs -->
        <div class="glass-card p-0 overflow-hidden shadow-2xl border-white/5">
            <div class="px-8 py-6 border-b border-white/5 flex items-center justify-between">
                <h3 class="text-lg font-bold flex items-center gap-3">
                    <i class="fas fa-list text-orange-500"></i> Événements enregistrés (<?= count($errors) ?>)
                </h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="admin-table text-left">
                    <thead>
                        <tr>
                            <th class="px-8">Source & Gravité</th>
                            <th>Détails de l'erreur</th>
                            <th>Emplacement</th>
                            <th>Date & Heure</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($errors as $err): 
                            $parsed = parseLogEntry($err);
                        ?>
                            <tr class="border-b border-white/5 hover:bg-white/2 transition-colors">
                                <td class="px-8">
                                    <div class="flex flex-col gap-2">
                                        <div class="flex items-center gap-2 text-xs font-bold text-gray-300">
                                            <i class="<?= $parsed['source_icon'] ?>"></i>
                                            <span><?= $parsed['source'] ?></span>
                                        </div>
                                        <div>
                                            <span class="status-badge <?= $parsed['badge_class'] ?>">
                                                <?= htmlspecialchars($parsed['level']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="max-w-md">
                                    <div class="font-semibold text-white text-xs truncate" title="<?= htmlspecialchars($parsed['message']) ?>">
                                        <?= htmlspecialchars($parsed['message']) ?>
                                    </div>
                                    <?php if (!empty($parsed['context'])): ?>
                                        <div class="text-[10px] text-gray-500 mt-1 italic truncate max-w-sm">
                                            <span class="text-orange-500 font-bold">Ctx:</span> <?= htmlspecialchars($parsed['context']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="text-[11px] text-gray-400 font-mono truncate max-w-xs" title="<?= htmlspecialchars($parsed['file']) ?>">
                                        <?= htmlspecialchars(basename($parsed['file'])) ?>
                                    </div>
                                    <div class="text-[10px] text-orange-500 font-bold mt-0.5">
                                        Ligne <?= $parsed['line'] ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-xs text-gray-300 font-medium">
                                        <?= date('d/m/Y H:i', strtotime($parsed['created_at'])) ?>
                                    </div>
                                    <div class="text-[9px] text-gray-500 mt-0.5">
                                        <?= date('s', strtotime($parsed['created_at'])) ?>s
                                    </div>
                                </td>
                                <td>
                                    <div class="flex items-center justify-center gap-2">
                                        <button onclick="inspectLog(<?= htmlspecialchars(json_encode($parsed)) ?>)" 
                                                class="p-2.5 bg-blue-500/10 text-blue-400 hover:bg-blue-500 hover:text-white rounded-lg border border-blue-500/20 transition-all text-xs"
                                                title="Inspecter">
                                            <i class="fas fa-search-plus"></i>
                                        </button>
                                        
                                        <form method="POST" onsubmit="return confirm('Supprimer ce log ?')" class="inline">
                                            <input type="hidden" name="delete_id" value="<?= $parsed['id'] ?>">
                                            <button type="submit" 
                                                    class="p-2.5 bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white rounded-lg border border-red-500/20 transition-all text-xs"
                                                    title="Supprimer">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($errors)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-24 text-gray-500 uppercase font-black tracking-widest text-sm">
                                    <i class="fas fa-check-circle text-green-500 text-3xl mb-4 block"></i>
                                    Aucune anomalie répertoriée
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal d'Inspection des Détails -->
    <div id="inspectModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[200] hidden items-center justify-center p-4">
        <div class="glass-card w-full max-w-2xl border-white/10 shadow-2xl relative">
            <button onclick="closeInspectModal()" class="absolute right-6 top-6 text-gray-400 hover:text-white text-lg"><i class="fas fa-times"></i></button>
            
            <h3 class="text-2xl font-black mb-1 flex items-center gap-3 text-white">
                <i class="fas fa-bug text-orange-500"></i> Diagnostic Erreur
            </h3>
            <p class="text-gray-500 text-xs mb-8">Analyse technique complète de l'anomalie.</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <span class="block text-[10px] text-gray-500 uppercase font-black tracking-widest mb-1">Source</span>
                    <span id="inspectSource" class="text-sm font-bold text-white flex items-center gap-2"></span>
                </div>
                <div>
                    <span class="block text-[10px] text-gray-500 uppercase font-black tracking-widest mb-1">Niveau & Gravité</span>
                    <span id="inspectLevelBadge" class="status-badge inline-block mt-1"></span>
                </div>
            </div>

            <div class="mb-6">
                <span class="block text-[10px] text-gray-500 uppercase font-black tracking-widest mb-2">Message d'erreur</span>
                <div id="inspectMessage" class="code-block text-white font-semibold"></div>
            </div>

            <div id="inspectContextContainer" class="mb-6 hidden">
                <span class="block text-[10px] text-gray-500 uppercase font-black tracking-widest mb-2">Contexte de l'Application</span>
                <div id="inspectContext" class="code-block text-purple-300"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div>
                    <span class="block text-[10px] text-gray-500 uppercase font-black tracking-widest mb-1">Fichier Source</span>
                    <span id="inspectFile" class="text-xs font-mono text-gray-300 break-all"></span>
                </div>
                <div>
                    <span class="block text-[10px] text-gray-500 uppercase font-black tracking-widest mb-1">Ligne</span>
                    <span id="inspectLine" class="text-sm font-bold text-orange-500"></span>
                </div>
            </div>

            <div class="flex items-center justify-between border-t border-white/5 pt-6">
                <span id="inspectDate" class="text-xs text-gray-500"></span>
                <button onclick="closeInspectModal()" class="px-6 py-3 bg-white/5 hover:bg-white/10 rounded-xl font-bold transition-all text-xs border border-white/10 text-white">
                    Fermer le diagnostic
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Purge -->
    <div id="purgeModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-[200] hidden items-center justify-center p-4">
        <div class="glass-card w-full max-w-md border-white/10 shadow-2xl relative">
            <button onclick="closePurgeModal()" class="absolute right-6 top-6 text-gray-400 hover:text-white text-lg"><i class="fas fa-times"></i></button>
            
            <h3 class="text-xl font-black mb-1 text-red-500 flex items-center gap-2">
                <i class="fas fa-exclamation-triangle"></i> Purger le journal
            </h3>
            <p class="text-gray-500 text-xs mb-6">Sélectionnez le type de nettoyage à effectuer.</p>
            
            <form method="POST">
                <div class="flex flex-col gap-4 mb-8">
                    <label class="flex items-start gap-4 p-4 rounded-xl border border-white/5 bg-white/2 hover:bg-white/5 cursor-pointer transition-colors">
                        <input type="radio" name="purge_type" value="filtered" class="mt-1 text-orange-500 focus:ring-orange-500" checked>
                        <div>
                            <span class="block text-sm font-bold text-white">Purger les logs filtrés uniquement</span>
                            <span class="block text-xs text-gray-400 mt-1">Supprimera uniquement les logs qui correspondent à vos critères de recherche et filtres actuels.</span>
                        </div>
                    </label>

                    <label class="flex items-start gap-4 p-4 rounded-xl border border-red-500/20 bg-red-500/5 hover:bg-red-500/10 cursor-pointer transition-colors">
                        <input type="radio" name="purge_type" value="all" class="mt-1 text-red-500 focus:ring-red-500">
                        <div>
                            <span class="block text-sm font-bold text-red-400">Purger TOUS les logs</span>
                            <span class="block text-xs text-gray-500 mt-1">Supprimera absolument tous les enregistrements d'erreurs de la base de données. Action irréversible.</span>
                        </div>
                    </label>
                </div>

                <div class="flex gap-4">
                    <button type="button" onclick="closePurgeModal()" class="flex-1 py-4 text-gray-500 font-bold hover:text-white transition-colors">Annuler</button>
                    <button type="submit" name="purge_logs" class="flex-1 py-4 bg-red-600 hover:bg-red-700 text-white font-bold rounded-2xl shadow-lg shadow-red-600/20 transition-all">
                        Confirmer la Purge
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Gestion de la sidebar
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const toggle = document.getElementById('sidebarToggle');

        function toggleSidebar() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        if (toggle) toggle.addEventListener('click', toggleSidebar);
        if (overlay) overlay.addEventListener('click', toggleSidebar);

        // Effet lumineux de souris
        const glow = document.getElementById('glow');
        document.addEventListener('mousemove', (e) => { 
            if (glow) {
                glow.style.left = (e.clientX - 200) + 'px'; 
                glow.style.top = (e.clientY - 200) + 'px'; 
            }
        });
        
        // Cacher le loader de chargement
        window.addEventListener('load', () => {
             const loader = document.getElementById('wma-global-loader');
             if (loader) {
                loader.style.opacity = '0';
                setTimeout(() => loader.style.display = 'none', 500);
             }
        });

        // Modale d'Inspection des Logs
        function inspectLog(log) {
            document.getElementById('inspectSource').innerHTML = `<i class="${log.source_icon}"></i> ${log.source}`;
            
            const badge = document.getElementById('inspectLevelBadge');
            badge.innerText = log.level;
            badge.className = 'status-badge ' + log.badge_class;
            
            document.getElementById('inspectMessage').innerText = log.message;
            
            const ctxContainer = document.getElementById('inspectContextContainer');
            if (log.context && log.context !== '') {
                document.getElementById('inspectContext').innerText = log.context;
                ctxContainer.classList.remove('hidden');
            } else {
                ctxContainer.classList.add('hidden');
            }
            
            document.getElementById('inspectFile').innerText = log.file;
            document.getElementById('inspectLine').innerText = log.line;
            document.getElementById('inspectDate').innerText = 'Enregistré le : ' + new Date(log.created_at).toLocaleString('fr-FR');
            
            const modal = document.getElementById('inspectModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeInspectModal() {
            const modal = document.getElementById('inspectModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // Modale de Purge
        function openPurgeModal() {
            const modal = document.getElementById('purgeModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closePurgeModal() {
            const modal = document.getElementById('purgeModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>
    <?php include_once __DIR__ . '/../../includes/language_selector.php'; ?>
</body>
</html>
