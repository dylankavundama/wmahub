<?php
/**
 * Endpoint de logging des erreurs Flutter/Mobile
 * Reçoit les erreurs de l'application mobile et les enregistre
 * dans la table system_errors + envoi email au développeur
 */
error_reporting(0);
ini_set('display_errors', '0');

require_once __DIR__ . '/../includes/config.php';

if (ob_get_level() > 0) ob_clean();

header('Content-Type: application/json; charset=utf-8');

// Accepter uniquement les POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false]);
    exit;
}

$input    = json_decode(file_get_contents('php://input'), true);
$message  = trim($input['message'] ?? ($_POST['message'] ?? ''));
$file     = trim($input['file']    ?? ($_POST['file']    ?? 'flutter_app'));
$line     = intval($input['line']  ?? ($_POST['line']    ?? 0));
$level    = trim($input['level']   ?? ($_POST['level']   ?? 'ERROR')); // ERROR, WARNING, INFO
$context  = trim($input['context'] ?? ($_POST['context'] ?? ''));

if (empty($message)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Message requis"]);
    exit;
}

// Construire le message complet avec contexte
$fullMessage = "[MOBILE/$level] $message";
if (!empty($context)) {
    $fullMessage .= " | Context: $context";
}

try {
    $db = getDBConnection();

    // Insérer dans system_errors (même table que les erreurs PHP)
    $stmt = $db->prepare("INSERT INTO system_errors (message, file, line) VALUES (?, ?, ?)");
    $stmt->execute([$fullMessage, $file, $line]);

    // Envoyer un email pour les erreurs et les warnings (comme demandé : "erreurs ou logs")
    $levelUpper = strtoupper($level);
    if (in_array($levelUpper, ['ERROR', 'CRITICAL', 'FATAL', 'WARNING'])) {
        if (function_exists('sendMobileErrorAlert')) {
            // On essaie de récupérer l'ID utilisateur si fourni dans l'input
            $userId = $input['user_id'] ?? ($_POST['user_id'] ?? 'inconnu');
            sendMobileErrorAlert($level, $message, $file, $line, $context, $userId);
        }
    }

    echo json_encode(["success" => true]);

} catch (Exception $e) {
    if (ob_get_level() > 0) ob_clean();
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>
