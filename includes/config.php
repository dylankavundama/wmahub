<?php
ob_start();
 
// define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
// define('DB_NAME', getenv('DB_NAME') ?: 'wmahubco_hub');
// define('DB_USER', getenv('DB_USER') ?: 'wmahubco_hub');
// define('DB_PASS', getenv('DB_PASSWORD') ?: 'Y3OS;W-)bsQR6*D6');


define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'wmahubco_hub');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASSWORD') ?: '');

// Configuration Google OAuth
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '547408646820-eedhgi415138ulb823mhh9uhln8i9f60.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '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');

// Configuration Gemini API
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: 'AIzaSyA_KgjNanXy09Hh2GMI-3pust2XjUqLgEA');

// Configuration Business
define('WHATSAPP_NUMBER', getenv('WHATSAPP_NUMBER') ?: '243825555555');
define('SUPPORT_EMAIL', getenv('SUPPORT_EMAIL') ?: 'info@wmahub.com');

// Configuration FlexPay
define('FLEXPAY_MERCHANT', 'STC_SARL');
define('FLEXPAY_TOKEN', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJcL2xvZ2luIiwicm9sZXMiOlsiTUVSQ0hBTlQiXSwiZXhwIjoxODIyODE0NjM1LCJzdWIiOiI0NjEwYmVkZjg5YTdhNjQ5MjdlMDFkYzg4Yjk2MGZlOCJ9.siqrnMclrfpi6XbdIvTulvyLp8PoSrQhw5JPCbRuflE');
define('FLEXPAY_API_URL', 'http://backend.flexpay.cd/api/rest/v1/paymentService');

// Détection dynamique de l'URL de base pour le redirect OAuth
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
// On détermine l'URL de base dynamiquement
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$physicalPath = str_replace('\\', '/', dirname(__DIR__));
$documentRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$webPath = str_replace($documentRoot, '', $physicalPath);
$baseUrl = $protocol . $host . '/' . ltrim($webPath, '/');
$baseUrl = rtrim($baseUrl, '/');

// On permet de forcer l'URL de redirection via variable d'environnement si la détection automatique échoue
define('GOOGLE_REDIRECT_URL', getenv('GOOGLE_REDIRECT_URL') ?: $baseUrl . '/auth/callback.php');

/**
 * Retourne une instance de connexion PDO
 */
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        die("Erreur de connexion : " . $e->getMessage());
    }
}

// Initialisation de la session
if (session_status() === PHP_SESSION_NONE) {
    if (!session_start()) {
        error_log("Failed to start session in config.php");
    }
}

// Vérification de l'état du compte (Actif/Suspendu)
if (isset($_SESSION['user_id'])) {
    $currentScript = $_SERVER['SCRIPT_NAME'];
    // Liste des pages qui ne doivent pas déclencher de redirection de suspension
    $excludedPages = [
        'auth/suspended.php', 
        'auth/logout.php', 
        'auth/select-role.php', 
        'auth/pending.php',
        'auth/callback.php'
    ];
    
    $isExcluded = false;
    foreach ($excludedPages as $page) {
        if (str_contains($currentScript, $page)) {
            $isExcluded = true;
            break;
        }
    }

    if (!$isExcluded) {
        try {
            $db_check = getDBConnection();
            $stmt_active = $db_check->prepare("SELECT is_active, role FROM users WHERE id = ?");
            $stmt_active->execute([$_SESSION['user_id']]);
            $userData = $stmt_active->fetch();
            
            if ($userData && $userData['is_active'] === 0) {
                // Redirection intelligente si le compte est inactif
                if ($userData['role'] === null) {
                    header('Location: ' . $baseUrl . '/auth/select-role.php');
                } elseif ($userData['role'] === 'employe') {
                    header('Location: ' . $baseUrl . '/auth/pending.php');
                } else {
                    header('Location: ' . $baseUrl . '/auth/suspended.php');
                }
                exit;
            }
        } catch (Exception $e) {}
    }
}

/**
 * Système de suivi analytique et erreurs
 */
if (php_sapi_name() !== 'cli') {
    $db_stats = getDBConnection();
    
    // Suivi des visites
    $current_page = $_SERVER['SCRIPT_NAME'];
    
    // On n'enregistre que les pages principales, pas les API ou assets si possible
    if (strpos($current_page, '.php') !== false && strpos($current_page, '/api/') === false) {
        // Log de la visite individuelle
        try {
            $stmt_visit = $db_stats->prepare("INSERT INTO site_visits (page) VALUES (?)");
            $stmt_visit->execute([$current_page]);
        } catch (Exception $e) {}

        // Incrémenter le compteur de visiteurs global (Indépendant du log)
        try {
            // S'assurer que le compteur id=1 existe, sinon l'initialiser
            $db_stats->query("INSERT IGNORE INTO visitor_stats (id, count) VALUES (1, 0)");
            $db_stats->query("UPDATE visitor_stats SET count = count + 1 WHERE id = 1");
        } catch (Exception $e) {}
    }

    // Email d'alerte développeur
    define('DEV_ALERT_EMAIL', 'dylankavundama@gmail.com');

    /**
     * Envoie un email d'alerte d'erreur au développeur.
     * Anti-spam : max 1 email par erreur identique toutes les 5 minutes.
     */
    function sendErrorAlert($errno, $errstr, $errfile, $errline, $isFatal = false) {
        // Ne pas alerter pour les notices bénignes (undefined variable, etc.) sauf si fatal
        if (!$isFatal && in_array($errno, [E_NOTICE, E_DEPRECATED, E_USER_NOTICE, E_USER_DEPRECATED])) {
            return;
        }

        // Throttling : éviter le flood d'emails pour la même erreur
        $throttleKey = 'err_alert_' . md5($errfile . $errline . $errstr);
        if (isset($_SESSION[$throttleKey]) && (time() - $_SESSION[$throttleKey]) < 300) {
            return; // Déjà alerté il y a moins de 5 min pour cette erreur
        }
        $_SESSION[$throttleKey] = time();

        // Types d'erreurs lisibles
        $errorTypes = [
            E_ERROR => '🔴 ERREUR FATALE', E_WARNING => '🟠 AVERTISSEMENT',
            E_PARSE => '🔴 ERREUR PARSE', E_NOTICE => '🔵 NOTICE',
            E_CORE_ERROR => '🔴 ERREUR CORE', E_COMPILE_ERROR => '🔴 ERREUR COMPILE',
            E_USER_ERROR => '🔴 ERREUR USER', E_USER_WARNING => '🟠 AVERTISSEMENT USER',
            E_USER_NOTICE => '🔵 NOTICE USER',
        ];
        $errorLabel = $isFatal ? '🔴 ERREUR FATALE' : ($errorTypes[$errno] ?? "Erreur #$errno");

        $url     = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/');
        $userId  = $_SESSION['user_id'] ?? 'non connecté';
        $time    = date('d/m/Y H:i:s');

        // Raccourcir le chemin du fichier pour lisibilité
        $shortFile = str_replace(['/home/wmahubco/public_html/', __DIR__ . '/../'], '', $errfile);

        $subject = "[WMA Hub] $errorLabel — $shortFile:$errline";
        $body = "
        <div style='font-family:Arial,sans-serif;max-width:680px;margin:auto;background:#0f0f0f;color:#e2e8f0;border-radius:12px;overflow:hidden;'>
            <div style='background:linear-gradient(135deg,#dc2626,#b91c1c);padding:24px 32px;'>
                <h1 style='margin:0;font-size:20px;color:#fff;'>🚨 Alerte Erreur — WMA Hub</h1>
                <p style='margin:6px 0 0;font-size:13px;color:rgba(255,255,255,0.7);'>Détectée automatiquement le $time</p>
            </div>
            <div style='padding:32px;'>
                <table style='width:100%;border-collapse:collapse;font-size:14px;'>
                    <tr><td style='padding:10px;background:#1a1a1a;border-radius:6px;font-weight:bold;color:#f97316;width:140px;'>Type</td>
                        <td style='padding:10px;'>$errorLabel</td></tr>
                    <tr><td style='padding:10px;color:#f97316;font-weight:bold;'>Message</td>
                        <td style='padding:10px;font-family:monospace;background:#1a1a1a;border-radius:6px;word-break:break-all;'>" . htmlspecialchars($errstr) . "</td></tr>
                    <tr><td style='padding:10px;color:#f97316;font-weight:bold;'>Fichier</td>
                        <td style='padding:10px;font-family:monospace;'>" . htmlspecialchars($shortFile) . "</td></tr>
                    <tr><td style='padding:10px;color:#f97316;font-weight:bold;'>Ligne</td>
                        <td style='padding:10px;font-family:monospace;'>$errline</td></tr>
                    <tr><td style='padding:10px;color:#f97316;font-weight:bold;'>URL</td>
                        <td style='padding:10px;font-family:monospace;word-break:break-all;'>" . htmlspecialchars($url) . "</td></tr>
                    <tr><td style='padding:10px;color:#f97316;font-weight:bold;'>Utilisateur</td>
                        <td style='padding:10px;'>ID : $userId</td></tr>
                </table>
            </div>
            <div style='background:#1a1a1a;padding:16px 32px;text-align:center;font-size:11px;color:#64748b;'>
                WMA Hub — Système d'alertes automatiques
            </div>
        </div>";

        try {
            require_once __DIR__ . '/mailer.php';
            sendEmail(DEV_ALERT_EMAIL, $subject, $body);
        } catch (Exception $e) {
            error_log("sendErrorAlert failed: " . $e->getMessage());
        }
    }

    // Gestionnaire d'erreurs personnalisé
    set_error_handler(function($errno, $errstr, $errfile, $errline) use ($db_stats) {
        if (!(error_reporting() & $errno)) return false;
        // Enregistrement en DB
        try {
            $stmt_err = $db_stats->prepare("INSERT INTO system_errors (message, file, line) VALUES (?, ?, ?)");
            $stmt_err->execute([$errstr, $errfile, $errline]);
        } catch (Exception $e) {}
        // Alerte email développeur
        sendErrorAlert($errno, $errstr, $errfile, $errline);
        return false; // Continuer la gestion d'erreur normale de PHP
    });

    // Capturer les erreurs fatales lors de l'arrêt du script
    register_shutdown_function(function() use ($db_stats) {
        $error = error_get_last();
        if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            // Enregistrement en DB
            try {
                $stmt_err = $db_stats->prepare("INSERT INTO system_errors (message, file, line) VALUES (?, ?, ?)");
                $stmt_err->execute(["FATAL: " . $error['message'], $error['file'], $error['line']]);
            } catch (Exception $e) {}
            // Alerte email pour les erreurs fatales (toujours envoyée)
            sendErrorAlert($error['type'], $error['message'], $error['file'], $error['line'], true);
        }
    });
}

/**
 * Crée une notification pour un utilisateur
 */
function createNotification($userId, $type, $message, $referenceId = null) {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("INSERT INTO notifications (user_id, type, message, reference_id) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$userId, $type, $message, $referenceId]);
    } catch (Exception $e) {
        error_log("Notification Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère un paramètre du site depuis la base de données
 */
function getSetting($key, $default = null) {
    static $settings = [];
    if (isset($settings[$key])) return $settings[$key];

    try {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        
        $settings[$key] = ($val !== false) ? $val : $default;
        return $settings[$key];
    } catch (Exception $e) {
        return $default;
    }
}
?>
