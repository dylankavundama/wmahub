<?php
// Configuration de la base de données
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'wmahub');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASSWORD') ?: '');

// Configuration Google OAuth
define('GOOGLE_CLIENT_ID', '547408646820-eedhgi415138ulb823mhh9uhln8i9f60.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');

// Détection dynamique de l'URL de base pour le redirect OAuth
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
// On détermine si on est dans un sous-dossier (comme /wmahub/ sur XAMPP)
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$path = str_replace(['/auth/login.php', '/index.php', '/auth/callback.php'], '', $scriptName);
$baseUrl = $protocol . $host . rtrim($path, '/');

define('GOOGLE_REDIRECT_URL', $baseUrl . '/auth/callback.php');

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
    session_start();
}

/**
 * Système de suivi analytique et erreurs
 */
if (php_sapi_name() !== 'cli') {
    $db_stats = getDBConnection();
    
    // Suivi des visites
    $current_page = $_SERVER['SCRIPT_NAME'];
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    // On n'enregistre que les pages principales, pas les API ou assets si possible
    if (strpos($current_page, '.php') !== false && strpos($current_page, '/api/') === false) {
        try {
            $stmt_visit = $db_stats->prepare("INSERT INTO site_visits (page, ip_address) VALUES (?, ?)");
            $stmt_visit->execute([$current_page, $ip]);
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
?>
