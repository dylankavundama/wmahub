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
?>
