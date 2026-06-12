<?php
/**
 * DB ONLINE UPDATE & MANAGER - WMA Hub
 * Outil d'administration premium sécurisé pour mettre à jour la base de données en ligne.
 * Permet d'exécuter des requêtes personnalisées ou d'importer un fichier SQL.
 */

require_once __DIR__ . '/includes/config.php';

// Code de sécurité pour empêcher l'exécution non autorisée en ligne
define('SECURE_KEY', 'wma_db_sync_2026');

// Initialisation des variables
$action = $_GET['action'] ?? '';
$key = $_GET['key'] ?? '';
$passcode = $_POST['passcode'] ?? '';
$is_authenticated = false;

// Authentification de session ou via clé de sécurité
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($key === SECURE_KEY || (isset($_SESSION['db_auth']) && $_SESSION['db_auth'] === true)) {
    $is_authenticated = true;
    $_SESSION['db_auth'] = true;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_auth']) && $passcode === 'wmahub2026') {
    $is_authenticated = true;
    $_SESSION['db_auth'] = true;
}

// Déconnexion
if ($action === 'logout') {
    $_SESSION['db_auth'] = false;
    unset($_SESSION['db_auth']);
    header('Location: db_online_update.php');
    exit;
}

$db = null;
$db_error = null;

try {
    $db = getDBConnection();
} catch (Exception $e) {
    $db_error = $e->getMessage();
}

// Traitement de l'exécution SQL personnalisée
$execution_results = null;

if ($is_authenticated && !$db_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Exécution de requête SQL textuelle
    if (isset($_POST['action_sql'])) {
        $sql_query = $_POST['sql_query'] ?? '';
        if (!empty(trim($sql_query))) {
            $execution_results = executeSQLText($db, $sql_query);
        } else {
            $execution_results = [
                'type' => 'error',
                'message' => 'La requête SQL est vide.'
            ];
        }
    }
    
    // 2. Importation de fichier SQL
    if (isset($_POST['action_upload'])) {
        if (isset($_FILES['sql_file']) && $_FILES['sql_file']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['sql_file']['tmp_name'];
            $file_name = $_FILES['sql_file']['name'];
            $sql_content = file_get_contents($file_tmp);
            
            if (!empty(trim($sql_content))) {
                $execution_results = executeSQLText($db, $sql_content, "Importation du fichier : " . htmlspecialchars($file_name));
            } else {
                $execution_results = [
                    'type' => 'error',
                    'message' => 'Le fichier SQL téléversé est vide.'
                ];
            }
        } else {
            $error_code = $_FILES['sql_file']['error'] ?? 'Inconnu';
            $execution_results = [
                'type' => 'error',
                'message' => 'Erreur lors du téléversement du fichier. Code : ' . $error_code
            ];
        }
    }
}

/**
 * Sépare et exécute les requêtes SQL contenues dans une chaîne de texte
 */
function executeSQLText($db, $sqlContent, $title = "Console SQL") {
    // Suppression des commentaires sur une seule ligne (-- ou #)
    $sqlContent = preg_replace('/--.*\n/', "\n", $sqlContent);
    $sqlContent = preg_replace('/#.*\n/', "\n", $sqlContent);
    // Suppression des commentaires multilignes
    $sqlContent = preg_replace('!/\*.*?\*/!s', '', $sqlContent);
    
    // Découpage par point-virgule en gérant les sauts de ligne
    $lines = explode("\n", $sqlContent);
    $queries = [];
    $temp = '';
    
    foreach ($lines as $line) {
        $trimmed_line = trim($line);
        if ($trimmed_line === '') continue;
        
        $temp .= ' ' . $line;
        
        // Si la ligne se termine par un point-virgule, on valide la requête
        if (substr($trimmed_line, -1) === ';') {
            $queries[] = trim($temp);
            $temp = '';
        }
    }
    
    // S'il reste du texte sans point-virgule final
    if (trim($temp) !== '') {
        $queries[] = trim($temp);
    }
    
    $results = [];
    $success_count = 0;
    $error_count = 0;
    
    // Activation du mode d'exception temporaire si ce n'est pas déjà fait
    $old_err_mode = $db->getAttribute(PDO::ATTR_ERRMODE);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (empty($query)) continue;
        
        try {
            // Exécution
            if (stripos($query, 'select') === 0 || stripos($query, 'show') === 0 || stripos($query, 'describe') === 0) {
                // Pour les SELECT, on fait un query et on affiche le nombre de lignes retournées
                $stmt = $db->query($query);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $count = count($rows);
                
                $results[] = [
                    'query' => $query,
                    'success' => true,
                    'message' => "Requête exécutée avec succès. Lignes retournées : {$count}.",
                    'data' => $count > 0 ? array_slice($rows, 0, 5) : null, // On affiche les 5 premières lignes max
                    'total_rows' => $count
                ];
            } else {
                // Pour les UPDATE/INSERT/DELETE/CREATE/ALTER, on utilise exec
                $affected = $db->exec($query);
                $results[] = [
                    'query' => $query,
                    'success' => true,
                    'message' => "Requête exécutée avec succès. Lignes affectées : {$affected}."
                ];
            }
            $success_count++;
        } catch (PDOException $e) {
            $results[] = [
                'query' => $query,
                'success' => false,
                'message' => "Erreur : " . $e->getMessage()
            ];
            $error_count++;
        }
    }
    
    // Restauration de l'ancien mode d'erreur
    $db->setAttribute(PDO::ATTR_ERRMODE, $old_err_mode);
    
    return [
        'type' => 'results',
        'title' => $title,
        'queries' => $results,
        'success_count' => $success_count,
        'error_count' => $error_count,
        'total_count' => count($results)
    ];
}

// Analyse de la base de données
$db_tables = [];
$total_rows = 0;
$total_size = 0; // en octets

if ($is_authenticated && !$db_error) {
    try {
        $stmt = $db->query("SELECT TABLE_NAME, ENGINE, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH 
                            FROM information_schema.TABLES 
                            WHERE TABLE_SCHEMA = DATABASE()");
        $tables_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($tables_data as $t) {
            $size = $t['DATA_LENGTH'] + $t['INDEX_LENGTH'];
            $db_tables[] = [
                'name' => $t['TABLE_NAME'],
                'engine' => $t['ENGINE'] ?? 'N/A',
                'rows' => (int)$t['TABLE_ROWS'],
                'size' => $size
            ];
            $total_rows += (int)$t['TABLE_ROWS'];
            $total_size += $size;
        }
    } catch (Exception $e) {
        // En cas d'erreur de lecture d'information_schema (droits insuffisants en ligne), repli sur SHOW TABLES
        try {
            $stmt = $db->query("SHOW TABLES");
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $tableName = $row[0];
                
                // Nombre de lignes estimé
                $countStmt = $db->query("SELECT COUNT(*) FROM `{$tableName}`");
                $rows = (int)$countStmt->fetchColumn();
                
                $db_tables[] = [
                    'name' => $tableName,
                    'engine' => 'Inconnu',
                    'rows' => $rows,
                    'size' => 0
                ];
                $total_rows += $rows;
            }
        } catch (Exception $ex) {}
    }
}

// Formater la taille en Ko/Mo
function formatSize($bytes) {
    if ($bytes <= 0) return '0 Octet';
    $units = ['Octets', 'Ko', 'Mo', 'Go'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMA Hub — Console de Mise à Jour de Base de Données</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #08080c;
            --card-bg: rgba(18, 18, 26, 0.75);
            --primary: #ff6600;
            --primary-glow: rgba(255, 102, 0, 0.2);
            --primary-hover: #ff771a;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --success: #10b981;
            --success-glow: rgba(16, 185, 129, 0.1);
            --error: #ef4444;
            --error-glow: rgba(239, 68, 68, 0.1);
            --border: rgba(255, 255, 255, 0.08);
            --border-focus: rgba(255, 102, 0, 0.4);
            --font: 'Outfit', sans-serif;
            --code-font: 'Fira Code', monospace;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: var(--font);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background-image: 
                radial-gradient(circle at 5% 5%, rgba(255, 102, 0, 0.04) 0%, transparent 40%),
                radial-gradient(circle at 95% 95%, rgba(16, 185, 129, 0.03) 0%, transparent 45%);
            background-attachment: fixed;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            width: 100%;
            max-width: 1000px;
            padding: 40px 20px;
            box-sizing: border-box;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 40px;
            backdrop-filter: blur(25px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), #10b981);
        }

        .header {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo {
            font-size: 36px;
            font-weight: 800;
            color: #fff;
            margin: 0 0 8px;
            letter-spacing: -1px;
        }

        .logo span {
            color: var(--primary);
        }

        .subtitle {
            color: var(--text-muted);
            margin: 0;
            font-size: 16px;
            font-weight: 300;
            letter-spacing: 0.5px;
        }

        /* --- AUTHENTICATION FORM --- */
        .auth-form {
            max-width: 420px;
            margin: 40px auto 20px;
            text-align: center;
            background: rgba(255, 255, 255, 0.01);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 30px;
        }

        .input-group {
            margin-bottom: 24px;
            text-align: left;
        }

        .input-label {
            display: block;
            margin-bottom: 10px;
            color: var(--text-muted);
            font-size: 14px;
            font-weight: 500;
        }

        .input-field {
            width: 100%;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border);
            padding: 15px 18px;
            border-radius: 14px;
            color: #fff;
            font-family: var(--font);
            font-size: 16px;
            box-sizing: border-box;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .input-field:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-glow);
            background: rgba(0, 0, 0, 0.3);
        }

        .btn {
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 15px 28px;
            font-family: var(--font);
            font-size: 16px;
            font-weight: 600;
            border-radius: 14px;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 14px var(--primary-glow);
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 102, 0, 0.4);
            background: var(--primary-hover);
        }

        .btn:active {
            transform: translateY(0);
        }

        /* --- DASHBOARD GRID --- */
        .db-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }

        @media (max-width: 900px) {
            .db-grid {
                grid-template-columns: 1fr;
            }
        }

        .panel-card {
            background: rgba(0, 0, 0, 0.15);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 24px;
            transition: border-color 0.3s;
        }

        .panel-card:hover {
            border-color: rgba(255, 255, 255, 0.15);
        }

        .panel-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 20px;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 12px;
        }

        /* --- DB CONFIG & STATS --- */
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .info-label {
            color: var(--text-muted);
        }

        .info-value {
            font-weight: 500;
            font-family: var(--code-font);
            font-size: 13px;
        }

        .status-pill {
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-online {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .status-offline {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        /* --- FORMS --- */
        .sql-textarea {
            width: 100%;
            height: 150px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: #e2e8f0;
            font-family: var(--code-font);
            font-size: 13px;
            padding: 15px;
            box-sizing: border-box;
            resize: vertical;
            line-height: 1.5;
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .sql-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-glow);
        }

        .file-upload-wrapper {
            position: relative;
            margin-bottom: 20px;
        }

        .file-upload-box {
            border: 2px dashed var(--border);
            border-radius: 14px;
            padding: 30px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: rgba(255, 255, 255, 0.01);
        }

        .file-upload-box:hover {
            border-color: var(--primary);
            background: rgba(255, 102, 0, 0.02);
        }

        .file-upload-box input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .upload-icon {
            font-size: 32px;
            margin-bottom: 10px;
            display: block;
        }

        .upload-text {
            font-size: 14px;
            color: var(--text-muted);
        }

        .selected-file-name {
            font-size: 13px;
            color: var(--success);
            font-weight: 500;
            margin-top: 10px;
            word-break: break-all;
            display: none;
        }

        /* --- RESULTS WINDOW --- */
        .results-console {
            background: #040406;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 24px;
            margin-top: 30px;
        }

        .console-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 10px;
        }

        .console-title {
            font-size: 16px;
            font-weight: 600;
            margin: 0;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .console-stats {
            font-size: 13px;
        }

        .console-log {
            max-height: 300px;
            overflow-y: auto;
            font-family: var(--code-font);
            font-size: 12px;
            line-height: 1.6;
            padding-right: 5px;
        }

        .log-item {
            margin-bottom: 14px;
            padding: 12px 16px;
            border-radius: 10px;
            border-left: 3px solid;
            background: rgba(255, 255, 255, 0.01);
        }

        .log-success {
            border-left-color: var(--success);
            color: #d1fae5;
        }

        .log-error {
            border-left-color: var(--error);
            color: #fee2e2;
        }

        .log-query {
            color: var(--text-muted);
            margin-bottom: 4px;
            word-break: break-all;
            font-weight: 500;
        }

        .log-message {
            font-weight: 600;
        }

        /* Table display inside console logs */
        .log-table-wrapper {
            margin-top: 10px;
            overflow-x: auto;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 8px;
        }

        .log-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            background: #000;
        }

        .log-table th {
            background: rgba(255, 255, 255, 0.03);
            color: #fff;
            padding: 6px 10px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .log-table td {
            padding: 6px 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.02);
            color: var(--text-muted);
        }

        /* --- DATABASE TABLES LIST --- */
        .tables-section {
            margin-top: 30px;
        }

        .tables-list-wrapper {
            overflow-x: auto;
            border: 1px solid var(--border);
            border-radius: 20px;
            background: rgba(0, 0, 0, 0.1);
        }

        .tables-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        .tables-table th {
            padding: 16px 20px;
            background: rgba(255, 255, 255, 0.02);
            color: #fff;
            font-weight: 600;
            border-bottom: 1px solid var(--border);
        }

        .tables-table td {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            color: var(--text-muted);
        }

        .tables-table tr:last-child td {
            border-bottom: none;
        }

        .tables-table tr:hover td {
            color: var(--text-main);
            background: rgba(255, 255, 255, 0.01);
        }

        .table-name {
            font-weight: 600;
            color: #fff;
            font-family: var(--code-font);
            font-size: 13px;
        }

        .badge-count {
            background: rgba(96, 165, 250, 0.1);
            color: #60a5fa;
            border: 1px solid rgba(96, 165, 250, 0.2);
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        /* --- UTILS --- */
        .alert-box {
            padding: 16px 20px;
            border-radius: 14px;
            margin-bottom: 24px;
            font-size: 14px;
            line-height: 1.5;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-danger {
            background: var(--error-glow);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: var(--error);
        }

        .logout-link {
            text-align: center;
            display: block;
            margin-top: 25px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }

        .logout-link:hover {
            color: var(--error);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h1 class="logo">WMA<span>HUB</span></h1>
                <p class="subtitle">Console de Mise à Jour de Base de Données en Ligne</p>
            </div>

            <?php if ($db_error): ?>
                <div class="alert-box alert-danger">
                    <span>⚠️</span>
                    <div>
                        <strong>Erreur de connexion SQL :</strong> <?php echo htmlspecialchars($db_error); ?><br>
                        <span style="font-size: 13px; opacity: 0.8;">Veuillez vérifier les variables de connexion (hôte, nom, identifiant, mot de passe) dans le fichier <code>includes/config.php</code>.</span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!$is_authenticated): ?>
                <!-- FORMULAIRE DE CONNEXION SÉCURISÉ -->
                <div class="auth-form">
                    <form method="POST">
                        <input type="hidden" name="action_auth" value="1">
                        <div class="input-group">
                            <label class="input-label" for="passcode">Code d'accès administrateur</label>
                            <input class="input-field" type="password" id="passcode" name="passcode" placeholder="Saisir le code d'accès" required autocomplete="current-password">
                        </div>
                        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_auth']) && $passcode !== 'wmahub2026'): ?>
                            <div style="color: var(--error); font-size: 14px; margin-bottom: 18px; text-align: left; font-weight: 500;">
                                ⚠️ Code d'accès incorrect.
                            </div>
                        <?php endif; ?>
                        <button type="submit" class="btn">
                            S'authentifier 🔐
                        </button>
                    </form>
                    <p style="font-size: 11px; color: var(--text-muted); margin-top: 20px; line-height: 1.4;">
                        Cet utilitaire permet d'exécuter des modifications directes sur le schéma et les données. Protégez cet accès avec rigueur.
                    </p>
                </div>
            <?php else: ?>
                <!-- INTERFACE PRINCIPALE DE GESTION -->
                
                <div class="db-grid">
                    <!-- CONFIGURATION ET STATS -->
                    <div class="panel-card">
                        <h2 class="panel-title">
                            <span>📡</span> Statut de la Base de Données
                        </h2>
                        
                        <div class="info-row">
                            <span class="info-label">Statut Connexion</span>
                            <span class="info-value">
                                <span class="status-pill <?php echo $db ? 'status-online' : 'status-offline'; ?>">
                                    <?php echo $db ? 'Connecté' : 'Erreur'; ?>
                                </span>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Serveur DB (Hôte)</span>
                            <span class="info-value"><?php echo htmlspecialchars(DB_HOST); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Nom Base de Données</span>
                            <span class="info-value"><?php echo htmlspecialchars(DB_NAME); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Utilisateur SQL</span>
                            <span class="info-value"><?php echo htmlspecialchars(DB_USER); ?></span>
                        </div>
                        <div class="info-row" style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.03); padding-top: 15px;">
                            <span class="info-label">Total Tables</span>
                            <span class="info-value" style="color: #fff; font-weight: bold;"><?php echo count($db_tables); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Total Lignes</span>
                            <span class="info-value" style="color: #fff; font-weight: bold;"><?php echo number_format($total_rows, 0, ',', ' '); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Taille Totale</span>
                            <span class="info-value" style="color: #fff; font-weight: bold;"><?php echo formatSize($total_size); ?></span>
                        </div>
                    </div>

                    <!-- EXÉCUTION DE CODE SQL -->
                    <div class="panel-card">
                        <h2 class="panel-title">
                            <span>💻</span> Exécution SQL Directe
                        </h2>
                        <form method="POST">
                            <input type="hidden" name="action_sql" value="1">
                            <textarea class="sql-textarea" name="sql_query" placeholder="Saisissez vos requêtes SQL ici...&#10;Exemple :&#10;ALTER TABLE users ADD COLUMN api_token VARCHAR(255) NULL;&#10;SELECT * FROM users LIMIT 5;" required></textarea>
                            <button type="submit" class="btn" <?php echo !$db ? 'disabled' : ''; ?>>
                                ⚡ Exécuter la requête
                            </button>
                        </form>
                    </div>

                    <!-- TÉLÉVERSEMENT DE FICHIER SQL -->
                    <div class="panel-card" style="grid-column: span 2;">
                        <h2 class="panel-title">
                            <span>📂</span> Téléverser et Exécuter un Script SQL (.sql)
                        </h2>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action_upload" value="1">
                            <div class="file-upload-wrapper">
                                <div class="file-upload-box" id="uploadBox">
                                    <span class="upload-icon">📤</span>
                                    <span class="upload-text">Glissez-déposez votre fichier SQL ici ou cliquez pour parcourir</span>
                                    <input type="file" name="sql_file" id="sqlFile" accept=".sql" required onchange="handleFileSelected(this)">
                                    <div class="selected-file-name" id="fileName"></div>
                                </div>
                            </div>
                            <button type="submit" class="btn" <?php echo !$db ? 'disabled' : ''; ?> style="background: linear-gradient(135deg, var(--primary), #059669); box-shadow: 0 4px 14px rgba(16, 185, 129, 0.2);">
                                🚀 Démarrer l'importation SQL
                            </button>
                        </form>
                    </div>
                </div>

                <!-- AFFICHAGE DES RÉSULTATS DE CONSOLE -->
                <?php if ($execution_results): ?>
                    <div class="results-console" id="resultsConsole">
                        <div class="console-header">
                            <h3 class="console-title">
                                <span>🖥️</span> <?php echo htmlspecialchars($execution_results['title'] ?? 'Résultats d\'exécution'); ?>
                            </h3>
                            <?php if (isset($execution_results['success_count'])): ?>
                                <span class="console-stats">
                                    <span style="color: var(--success); font-weight: bold;"><?php echo $execution_results['success_count']; ?> Réussies</span> | 
                                    <span style="color: var(--error); font-weight: bold;"><?php echo $execution_results['error_count']; ?> Échouées</span>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="console-log">
                            <?php if ($execution_results['type'] === 'error'): ?>
                                <div class="log-item log-error">
                                    <div class="log-message"><?php echo htmlspecialchars($execution_results['message']); ?></div>
                                </div>
                            <?php elseif (isset($execution_results['queries'])): ?>
                                <?php foreach ($execution_results['queries'] as $res): ?>
                                    <div class="log-item <?php echo $res['success'] ? 'log-success' : 'log-error'; ?>">
                                        <div class="log-query">> <?php echo htmlspecialchars($res['query']); ?></div>
                                        <div class="log-message"><?php echo htmlspecialchars($res['message']); ?></div>
                                        
                                        <?php if (isset($res['data']) && !empty($res['data'])): ?>
                                            <div class="log-table-wrapper">
                                                <table class="log-table">
                                                    <thead>
                                                        <tr>
                                                            <?php foreach (array_keys($res['data'][0]) as $colName): ?>
                                                                <th><?php echo htmlspecialchars($colName); ?></th>
                                                            <?php endforeach; ?>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($res['data'] as $row): ?>
                                                            <tr>
                                                                <?php foreach ($row as $val): ?>
                                                                    <td><?php echo $val === null ? '<em>NULL</em>' : htmlspecialchars($val); ?></td>
                                                                <?php endforeach; ?>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <?php if ($res['total_rows'] > 5): ?>
                                                <div style="font-size: 10px; color: var(--text-muted); margin-top: 5px; font-style: italic;">
                                                    Affichage limité aux 5 premières lignes sur <?php echo $res['total_rows']; ?> lignes au total.
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <script>
                        // Défilement automatique vers la console de résultat après exécution
                        document.addEventListener("DOMContentLoaded", function() {
                            document.getElementById("resultsConsole").scrollIntoView({ behavior: 'smooth' });
                        });
                    </script>
                <?php endif; ?>

                <!-- SECTION DES TABLES DE LA BASE DE DONNÉES -->
                <div class="tables-section">
                    <h2 class="panel-title" style="margin-bottom: 15px;">
                        <span>📊</span> Tables Présentes dans la Base de Données
                    </h2>
                    
                    <?php if (empty($db_tables)): ?>
                        <div class="alert-box" style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border); color: var(--text-muted);">
                            <span>ℹ️</span> Aucune table détectée dans cette base de données. Vous pouvez exécuter des requêtes pour en créer.
                        </div>
                    <?php else: ?>
                        <div class="tables-list-wrapper">
                            <table class="tables-table">
                                <thead>
                                    <tr>
                                        <th>Nom de la Table</th>
                                        <th>Moteur</th>
                                        <th>Lignes (Est.)</th>
                                        <th>Taille Données + Index</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($db_tables as $table): ?>
                                        <tr>
                                            <td class="table-name"><?php echo htmlspecialchars($table['name']); ?></td>
                                            <td><?php echo htmlspecialchars($table['engine']); ?></td>
                                            <td>
                                                <span class="badge-count"><?php echo number_format($table['rows'], 0, ',', ' '); ?></span>
                                            </td>
                                            <td><?php echo formatSize($table['size']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <a href="db_online_update.php?action=logout" class="logout-link">Se déconnecter de la session d'administration</a>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Fonction pour afficher le nom du fichier sélectionné
        function handleFileSelected(input) {
            const fileNameDiv = document.getElementById('fileName');
            const uploadBox = document.getElementById('uploadBox');
            if (input.files && input.files.length > 0) {
                const name = input.files[0].name;
                fileNameDiv.textContent = '📄 Fichier sélectionné : ' + name;
                fileNameDiv.style.display = 'block';
                uploadBox.style.borderColor = 'var(--success)';
            } else {
                fileNameDiv.textContent = '';
                fileNameDiv.style.display = 'none';
                uploadBox.style.borderColor = 'var(--border)';
            }
        }
    </script>
</body>
</html>
