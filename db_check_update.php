<?php
/**
 * DB CHECK & UPDATE - WMA Hub
 * Outil d'administration premium pour vérifier l'intégrité de la base de données
 * et appliquer automatiquement les migrations manquantes (tables, colonnes, index).
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
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $passcode === 'wmahub2026') {
    $is_authenticated = true;
    $_SESSION['db_auth'] = true;
}

// Déconnexion
if ($action === 'logout') {
    $_SESSION['db_auth'] = false;
    unset($_SESSION['db_auth']);
    header('Location: db_check_update.php');
    exit;
}

// Liste des vérifications requises
$schema_requirements = [
    'tables' => [
        'distributions' => "CREATE TABLE `distributions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(255) NOT NULL,
            `artist` VARCHAR(255) NOT NULL,
            `image_url` VARCHAR(255),
            `link` VARCHAR(255),
            `status` ENUM('active', 'inactive') DEFAULT 'active',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",
        
        'hero_slides' => "CREATE TABLE `hero_slides` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(255) NOT NULL,
            `subtitle` VARCHAR(255),
            `image_path` VARCHAR(255) NOT NULL,
            `button_text` VARCHAR(50) DEFAULT 'En savoir plus',
            `button_link` VARCHAR(255) DEFAULT '#',
            `display_order` INT DEFAULT 0,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
    ],
    'columns' => [
        'users' => [
            'password' => [
                'type' => 'VARCHAR(255) NULL DEFAULT NULL',
                'after' => 'email',
                'sql' => "ALTER TABLE `users` ADD COLUMN `password` VARCHAR(255) NULL DEFAULT NULL AFTER `email`;"
            ],
            'apple_id' => [
                'type' => 'VARCHAR(255) NULL DEFAULT NULL',
                'after' => 'google_id',
                'sql' => "ALTER TABLE `users` ADD COLUMN `apple_id` VARCHAR(255) NULL DEFAULT NULL AFTER `google_id`;"
            ]
        ],
        'projects' => [
            'provided_files' => [
                'type' => 'TEXT NULL DEFAULT NULL',
                'after' => 'languages',
                'sql' => "ALTER TABLE `projects` ADD COLUMN `provided_files` TEXT NULL DEFAULT NULL AFTER `languages`;"
            ]
        ],
        'tasks' => [
            'is_archived' => [
                'type' => 'TINYINT(1) DEFAULT 0',
                'after' => 'rating',
                'sql' => "ALTER TABLE `tasks` ADD COLUMN `is_archived` TINYINT(1) DEFAULT 0 AFTER `rating`;"
            ]
        ]
    ]
];

// Fonction pour récupérer les tables existantes
function getExistingTables($db) {
    $tables = [];
    $stmt = $db->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    return $tables;
}

// Fonction pour vérifier si une colonne existe dans une table
function columnExists($db, $table, $column) {
    try {
        $stmt = $db->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $stmt->execute([$table, $column]);
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        return false;
    }
}

// Traitement des mises à jour
$update_results = [];
$db_error = null;

try {
    $db = getDBConnection();
    
    if ($is_authenticated && $action === 'update') {
        // 1. Création des tables manquantes
        foreach ($schema_requirements['tables'] as $tableName => $sql) {
            $existing = getExistingTables($db);
            if (!in_array($tableName, $existing)) {
                try {
                    $db->exec($sql);
                    $update_results[] = [
                        'type' => 'table_create',
                        'target' => $tableName,
                        'success' => true,
                        'message' => "Table '{$tableName}' créée avec succès."
                    ];
                } catch (PDOException $e) {
                    $update_results[] = [
                        'type' => 'table_create',
                        'target' => $tableName,
                        'success' => false,
                        'message' => "Erreur lors de la création de la table '{$tableName}' : " . $e->getMessage()
                    ];
                }
            }
        }
        
        // 2. Ajout des colonnes manquantes
        foreach ($schema_requirements['columns'] as $tableName => $columns) {
            $existingTables = getExistingTables($db);
            if (in_array($tableName, $existingTables)) {
                foreach ($columns as $columnName => $config) {
                    if (!columnExists($db, $tableName, $columnName)) {
                        try {
                            $db->exec($config['sql']);
                            $update_results[] = [
                                'type' => 'column_add',
                                'target' => "{$tableName}.{$columnName}",
                                'success' => true,
                                'message' => "Colonne '{$columnName}' ajoutée à la table '{$tableName}'."
                            ];
                        } catch (PDOException $e) {
                            $update_results[] = [
                                'type' => 'column_add',
                                'target' => "{$tableName}.{$columnName}",
                                'success' => false,
                                'message' => "Erreur sur '{$tableName}.{$columnName}' : " . $e->getMessage()
                            ];
                        }
                    }
                }
            } else {
                $update_results[] = [
                    'type' => 'column_add',
                    'target' => $tableName,
                    'success' => false,
                    'message' => "Impossible d'ajouter des colonnes, la table '{$tableName}' n'existe pas."
                ];
            }
        }

        // 3. Correction des contraintes (rendre google_id nullable pour l'inscription Apple)
        try {
            $db->exec("ALTER TABLE `users` MODIFY `google_id` VARCHAR(255) NULL DEFAULT NULL");
            $update_results[] = [
                'type' => 'constraint_fix',
                'target' => "users.google_id",
                'success' => true,
                'message' => "Contrainte corrigée pour autoriser NULL sur 'google_id'."
            ];
        } catch (PDOException $e) {
            $update_results[] = [
                'type' => 'constraint_fix',
                'target' => "users.google_id",
                'success' => false,
                'message' => "Erreur contrainte 'google_id' : " . $e->getMessage()
            ];
        }
    }
} catch (PDOException $e) {
    $db_error = $e->getMessage();
}

// Phase d'analyse pour l'affichage
$analysis = [
    'tables' => [],
    'columns' => [],
    'missing_count' => 0
];

if ($is_authenticated && !$db_error) {
    $existing_tables = getExistingTables($db);
    
    // Analyse des tables
    foreach ($schema_requirements['tables'] as $tableName => $sql) {
        $exists = in_array($tableName, $existing_tables);
        $analysis['tables'][$tableName] = $exists;
        if (!$exists) $analysis['missing_count']++;
    }
    
    // Analyse des colonnes
    foreach ($schema_requirements['columns'] as $tableName => $columns) {
        if (in_array($tableName, $existing_tables)) {
            foreach ($columns as $columnName => $config) {
                $exists = columnExists($db, $tableName, $columnName);
                $analysis['columns']["{$tableName}.{$columnName}"] = [
                    'table' => $tableName,
                    'column' => $columnName,
                    'exists' => $exists
                ];
                if (!$exists) $analysis['missing_count']++;
            }
        } else {
            foreach ($columns as $columnName => $config) {
                $analysis['columns']["{$tableName}.{$columnName}"] = [
                    'table' => $tableName,
                    'column' => $columnName,
                    'exists' => false,
                    'error' => 'Table parente absente'
                ];
                $analysis['missing_count']++;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMA Hub — Diagnostic & Synchronisation de Base de Données</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0b0b0f;
            --card-bg: rgba(20, 20, 27, 0.65);
            --primary: #ff6600;
            --primary-glow: rgba(255, 102, 0, 0.15);
            --text-main: #e2e8f0;
            --text-muted: #94a3b8;
            --success: #10b981;
            --success-glow: rgba(16, 185, 129, 0.1);
            --error: #ef4444;
            --error-glow: rgba(239, 68, 68, 0.1);
            --border: rgba(255, 255, 255, 0.06);
            --font: 'Outfit', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: var(--font);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(255, 102, 0, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(16, 185, 129, 0.02) 0%, transparent 40%);
        }

        .container {
            width: 100%;
            max-width: 850px;
            padding: 40px 20px;
            box-sizing: border-box;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 40px;
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
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
            background: linear-gradient(90deg, var(--primary), var(--success));
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo {
            font-size: 32px;
            font-weight: 700;
            color: #fff;
            margin: 0 0 10px;
            letter-spacing: -0.5px;
        }

        .logo span {
            color: var(--primary);
        }

        .subtitle {
            color: var(--text-muted);
            margin: 0;
            font-size: 16px;
            font-weight: 300;
        }

        /* Formulaire d'authentification */
        .auth-form {
            max-width: 400px;
            margin: 0 auto;
            text-align: center;
        }

        .input-group {
            margin-bottom: 24px;
            text-align: left;
        }

        .input-label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-muted);
            font-size: 14px;
        }

        .input-field {
            width: 100%;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border);
            padding: 14px 18px;
            border-radius: 12px;
            color: #fff;
            font-family: var(--font);
            font-size: 16px;
            box-sizing: border-box;
            transition: all 0.3s;
        }

        .input-field:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-glow);
            background: rgba(255, 255, 255, 0.05);
        }

        .btn {
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 14px 28px;
            font-family: var(--font);
            font-size: 16px;
            font-weight: 600;
            border-radius: 12px;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(255, 102, 0, 0.2);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 102, 0, 0.35);
            background: #ff771a;
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-main);
            border: 1px solid var(--border);
            box-shadow: none;
            margin-top: 12px;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            box-shadow: none;
        }

        /* Tableau de Diagnostic */
        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin: 30px 0 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
            padding-bottom: 8px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }

        .status-box {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s;
        }

        .status-box:hover {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .status-info {
            display: flex;
            flex-direction: column;
        }

        .status-name {
            font-weight: 600;
            font-size: 15px;
            color: #fff;
            margin-bottom: 4px;
        }

        .status-desc {
            font-size: 13px;
            color: var(--text-muted);
        }

        .badge {
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
        }

        .badge-success {
            background: var(--success-glow);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .badge-error {
            background: var(--error-glow);
            color: var(--error);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .badge::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            margin-right: 6px;
            background: currentColor;
        }

        /* Résultats de mise à jour */
        .results-card {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 30px;
        }

        .result-item {
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
            display: flex;
            align-items: flex-start;
            font-size: 14px;
        }

        .result-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .result-icon {
            margin-right: 12px;
            font-size: 16px;
        }

        .result-success {
            color: var(--success);
        }

        .result-error {
            color: var(--error);
        }

        .action-bar {
            display: flex;
            gap: 15px;
            margin-top: 40px;
        }

        .logout-link {
            text-align: center;
            display: block;
            margin-top: 24px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }

        .logout-link:hover {
            color: var(--error);
        }

        .alert-box {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            line-height: 1.5;
        }

        .alert-danger {
            background: var(--error-glow);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: var(--error);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h1 class="logo">WMA<span>HUB</span></h1>
                <p class="subtitle">Diagnostic & Synchronisation de Base de Données</p>
            </div>

            <?php if ($db_error): ?>
                <div class="alert-box alert-danger">
                    <strong>Erreur de connexion :</strong> <?php echo htmlspecialchars($db_error); ?><br>
                    Veuillez vérifier les informations de connexion à la base de données dans votre fichier <code>includes/config.php</code>.
                </div>
            <?php endif; ?>

            <?php if (!$is_authenticated): ?>
                <!-- Formulaire d'Authentification Sécurisé -->
                <div class="auth-form">
                    <form method="POST">
                        <div class="input-group">
                            <label class="input-label" for="passcode">Code d'accès administrateur</label>
                            <input class="input-field" type="password" id="passcode" name="passcode" placeholder="Saisir le code d'accès" required autocomplete="current-password">
                        </div>
                        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $passcode !== 'wmahub2026'): ?>
                            <div style="color: var(--error); font-size: 14px; margin-bottom: 15px; text-align: left;">
                                ⚠️ Code d'accès incorrect.
                            </div>
                        <?php endif; ?>
                        <button type="submit" class="btn">S'authentifier</button>
                    </form>
                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 20px;">
                        Note: Ce script permet de mettre à jour le schéma de la base de données et nécessite une authentification stricte pour des raisons de sécurité.
                    </p>
                </div>
            <?php else: ?>
                <!-- Interface Principale du Diagnostic -->
                
                <?php if (!empty($update_results)): ?>
                    <div class="results-card">
                        <h3 style="margin-top: 0; color: #fff; font-size: 16px; margin-bottom: 18px;">Rapport d'exécution des mises à jour :</h3>
                        <?php foreach ($update_results as $res): ?>
                            <div class="result-item">
                                <span class="result-icon <?php echo $res['success'] ? 'result-success' : 'result-error'; ?>">
                                    <?php echo $res['success'] ? '🟢' : '🔴'; ?>
                                </span>
                                <div>
                                    <strong><?php echo $res['target']; ?></strong><br>
                                    <span style="color: var(--text-muted); font-size: 13px;"><?php echo htmlspecialchars($res['message']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="section-title">
                    <span>Vérification des Tables Principales</span>
                    <span style="font-size: 13px; color: var(--text-muted); font-weight: normal;">
                        <?php 
                        $total_tables = count($analysis['tables']);
                        $ok_tables = count(array_filter($analysis['tables']));
                        echo "{$ok_tables} / {$total_tables} OK";
                        ?>
                    </span>
                </div>

                <div class="grid">
                    <?php foreach ($analysis['tables'] as $table => $exists): ?>
                        <div class="status-box">
                            <div class="status-info">
                                <span class="status-name"><?php echo $table; ?></span>
                                <span class="status-desc">
                                    <?php echo $table === 'distributions' ? 'Table de gestion des sorties musicales' : 'Table du carrousel public'; ?>
                                </span>
                            </div>
                            <span class="badge <?php echo $exists ? 'badge-success' : 'badge-error'; ?>">
                                <?php echo $exists ? 'Présente' : 'Absente'; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="section-title">
                    <span>Vérification des Colonnes Spécifiques</span>
                    <span style="font-size: 13px; color: var(--text-muted); font-weight: normal;">
                        <?php 
                        $total_cols = count($analysis['columns']);
                        $ok_cols = count(array_filter($analysis['columns'], function($c) { return $c['exists']; }));
                        echo "{$ok_cols} / {$total_cols} OK";
                        ?>
                    </span>
                </div>

                <div class="grid">
                    <?php foreach ($analysis['columns'] as $keyName => $info): ?>
                        <div class="status-box">
                            <div class="status-info">
                                <span class="status-name"><?php echo $info['column']; ?></span>
                                <span class="status-desc">Dans la table <code><?php echo $info['table']; ?></code></span>
                            </div>
                            <span class="badge <?php echo $info['exists'] ? 'badge-success' : 'badge-error'; ?>">
                                <?php echo $info['exists'] ? 'Présente' : 'Absente'; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="action-bar">
                    <?php if ($analysis['missing_count'] > 0): ?>
                        <a href="db_check_update.php?action=update" class="btn" style="text-align: center; text-decoration: none; display: block; flex: 1;">
                            🚀 Appliquer la mise à jour (<?php echo $analysis['missing_count']; ?> correction<?php echo $analysis['missing_count'] > 1 ? 's' : ''; ?>)
                        </a>
                    <?php else: ?>
                        <div class="btn" style="background: rgba(16, 185, 129, 0.2); border: 1px solid var(--success); color: var(--success); box-shadow: none; cursor: default; text-align: center; flex: 1;">
                            ✅ Base de données parfaitement à jour !
                        </div>
                    <?php endif; ?>
                    <a href="db_check_update.php" class="btn btn-secondary" style="width: auto; text-align: center; text-decoration: none; margin-top: 0;">
                        🔄 Rafraîchir
                    </a>
                </div>

                <a href="db_check_update.php?action=logout" class="logout-link">Se déconnecter</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
