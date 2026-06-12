<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');
header('Access-Control-Allow-Methods: POST, OPTIONS');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Méthode non autorisée. Seul POST est accepté.");
    }

    // Read POST payload
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? '';

    if (empty($email)) {
        throw new Exception("L'adresse email est requise.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Format d'adresse email invalide.");
    }

    // Path to text file
    $dir = __DIR__ . '/../uploads';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $file = $dir . '/waitlist_emails.txt';

    // Format line: Date | Email
    $line = date('Y-m-d H:i:s') . ' | ' . $email . PHP_EOL;

    // Append to file
    if (file_put_contents($file, $line, FILE_APPEND | LOCK_EX) === false) {
        throw new Exception("Impossible d'enregistrer l'email.");
    }

    echo json_encode([
        "success" => true,
        "message" => "Email enregistré avec succès."
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
