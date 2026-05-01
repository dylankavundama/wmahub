<?php
require_once __DIR__ . '/../includes/config.php';

/**
 * Note: Cette implémentation est une structure de base. 
 * Pour une sécurité maximale en production, utilisez une bibliothèque JWT 
 * (comme firebase/php-jwt) pour vérifier la signature du id_token d'Apple.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$code = $_POST['code'] ?? null;
$id_token = $_POST['id_token'] ?? null;
$state = $_POST['state'] ?? null;
$user_data = $_POST['user'] ?? null; // JSON envoyé par Apple uniquement à la première connexion

if (!$id_token) {
    die("Erreur : Aucun jeton reçu d'Apple.");
}

// 1. Décoder le ID Token (JWT) pour obtenir l'email et le sub (identifiant unique)
// Un JWT est composé de 3 parties séparées par des points. La partie 2 contient les données.
$parts = explode('.', $id_token);
if (count($parts) < 2) {
    die("Erreur : Jeton Apple invalide.");
}

$payload = json_decode(base64_decode($parts[1]), true);
$apple_id = $payload['sub'] ?? null;
$email = $payload['email'] ?? null;

if (!$apple_id) {
    die("Erreur : Impossible de récupérer l'identifiant Apple.");
}

try {
    $db = getDBConnection();

    // 2. Vérifier si l'utilisateur existe déjà
    $stmt = $db->prepare("SELECT id, role, is_active FROM users WHERE apple_id = ? OR email = ?");
    $stmt->execute([$apple_id, $email]);
    $user = $stmt->fetch();

    if ($user) {
        // Mise à jour de l'apple_id si trouvé par email
        if (!$user['apple_id']) {
            $update = $db->prepare("UPDATE users SET apple_id = ? WHERE id = ?");
            $update->execute([$apple_id, $user['id']]);
        }
        
        // Connexion
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        
        // Redirection selon le rôle
        if (empty($user['role'])) {
            header('Location: select-role.php');
        } else {
            header('Location: ../dashboards/' . $user['role'] . '/index.php');
        }
        exit;
    } else {
        // 3. Nouvel utilisateur
        $name = "Artiste Apple";
        if ($user_data) {
            $user_obj = json_decode($user_data, true);
            $firstName = $user_obj['name']['firstName'] ?? '';
            $lastName = $user_obj['name']['lastName'] ?? '';
            $name = trim("$firstName $lastName") ?: "Artiste Apple";
        }

        $stmt = $db->prepare("INSERT INTO users (name, email, apple_id, is_active, role) VALUES (?, ?, ?, 1, NULL)");
        $stmt->execute([$name, $email, $apple_id]);
        
        $_SESSION['user_id'] = $db->lastInsertId();
        $_SESSION['role'] = null; // L'utilisateur devra choisir son rôle
        
        header('Location: select-role.php');
        exit;
    }

} catch (Exception $e) {
    die("Erreur base de données : " . $e->getMessage());
}
