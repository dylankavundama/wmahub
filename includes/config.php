<?php
ob_start();
 
// define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
// define('DB_NAME', getenv('DB_NAME') ?: 'wmahub');
// define('DB_USER', getenv('DB_USER') ?: 'root');
// define('DB_PASS', getenv('DB_PASSWORD') ?: '');


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

    // Gestionnaire d'erreurs personnalisé
    set_error_handler(function($errno, $errstr, $errfile, $errline) use ($db_stats) {
        if (!(error_reporting() & $errno)) return false;
        try {
            $stmt_err = $db_stats->prepare("INSERT INTO system_errors (message, file, line) VALUES (?, ?, ?)");
            $stmt_err->execute([$errstr, $errfile, $errline]);
        } catch (Exception $e) {}
        return false; // Continuer la gestion d'erreur normale de PHP
    });

    // Capturer les erreurs fatales lors de l'arrêt du script
    register_shutdown_function(function() use ($db_stats) {
        $error = error_get_last();
        if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            try {
                $stmt_err = $db_stats->prepare("INSERT INTO system_errors (message, file, line) VALUES (?, ?, ?)");
                $stmt_err->execute(["FATAL: " . $error['message'], $error['file'], $error['line']]);
            } catch (Exception $e) {}
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
