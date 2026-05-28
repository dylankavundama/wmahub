<?php
/**
 * Authentification mobile unifiée via Firebase Auth (ID token).
 * Remplace google_auth_mobile.php, apple_auth_mobile.php et auth_login.php pour l'app Flutter.
 */
error_reporting(0);
ini_set('display_errors', '0');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/firebase_verify.php';

if (ob_get_level() > 0) {
    ob_clean();
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $db = getDBConnection();

    $idToken = $_POST['firebaseIdToken'] ?? $_POST['idToken'] ?? '';
    $displayNameHint = trim($_POST['displayName'] ?? '');

    if ($idToken === '') {
        throw new Exception('Firebase ID token requis');
    }

    $identity = verifyFirebaseIdToken($idToken);
    $firebaseUid = $identity['uid'];
    $email = $identity['email'];
    $name = $displayNameHint !== '' ? $displayNameHint : $identity['name'];
    $googleId = $identity['google_id'];
    $appleId = $identity['apple_id'];

    $user = null;

    if (columnExists($db, 'users', 'firebase_uid')) {
        $stmt = $db->prepare(
            'SELECT id, name, email, role, is_active, google_id, apple_id, firebase_uid
             FROM users WHERE firebase_uid = ? LIMIT 1'
        );
        $stmt->execute([$firebaseUid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$user && $email !== '') {
        $stmt = $db->prepare(
            'SELECT id, name, email, role, is_active, google_id, apple_id
             FROM users WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$user && $googleId) {
        $stmt = $db->prepare(
            'SELECT id, name, email, role, is_active, google_id, apple_id
             FROM users WHERE google_id = ? LIMIT 1'
        );
        $stmt->execute([$googleId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$user && $appleId && columnExists($db, 'users', 'apple_id')) {
        $stmt = $db->prepare(
            'SELECT id, name, email, role, is_active, google_id, apple_id
             FROM users WHERE apple_id = ? LIMIT 1'
        );
        $stmt->execute([$appleId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$user) {
        $insertGoogleId = !empty($googleId) ? $googleId : null;
        $hasAppleCol = columnExists($db, 'users', 'apple_id');
        $hasFirebaseCol = columnExists($db, 'users', 'firebase_uid');

        if ($hasFirebaseCol && $hasAppleCol) {
            $stmt = $db->prepare(
                'INSERT INTO users (google_id, apple_id, firebase_uid, name, email, is_active, role)
                 VALUES (?, ?, ?, ?, ?, 0, NULL)'
            );
            $stmt->execute([$insertGoogleId, $appleId, $firebaseUid, $name, $email]);
        } elseif ($hasAppleCol) {
            $stmt = $db->prepare(
                'INSERT INTO users (google_id, apple_id, name, email, is_active, role)
                 VALUES (?, ?, ?, ?, 0, NULL)'
            );
            $stmt->execute([$insertGoogleId, $appleId, $name, $email]);
        } else {
            $stmt = $db->prepare(
                'INSERT INTO users (google_id, name, email, is_active, role)
                 VALUES (?, ?, ?, 0, NULL)'
            );
            $stmt->execute([$insertGoogleId, $name, $email]);
        }

        $userId = (int) $db->lastInsertId();
        $user = [
            'id'        => $userId,
            'name'      => $name,
            'email'     => $email,
            'role'      => null,
            'is_active' => 0,
        ];

        try {
            require_once __DIR__ . '/../includes/mailer.php';
            notifyAdmin('registration', 'Nouvel utilisateur (Firebase Mobile)', [
                'Nom'   => $name,
                'Email' => $email ?: '(non fourni)',
                'UID'   => $firebaseUid,
                'Date'  => date('d/m/Y H:i'),
            ], 'https://wmahub.com/dashboards/admin/users.php');
        } catch (Exception $e) {
            error_log('Notify admin firebase registration: ' . $e->getMessage());
        }
    } else {
        linkFirebaseIdentity($db, (int) $user['id'], $firebaseUid, $googleId, $appleId, $name, $email);
    }

    echo json_encode([
        'success' => true,
        'user'    => [
            'id'        => $user['id'],
            'name'      => $user['name'],
            'email'     => $user['email'],
            'role'      => $user['role'],
            'is_active' => (int) $user['is_active'],
        ],
    ]);
} catch (Exception $e) {
    if (ob_get_level() > 0) {
        ob_clean();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function columnExists(PDO $db, string $table, string $column): bool
{
    static $cache = [];
    $key = "$table.$column";
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    $cache[$key] = (int) $stmt->fetchColumn() > 0;
    return $cache[$key];
}

function linkFirebaseIdentity(
    PDO $db,
    int $userId,
    string $firebaseUid,
    ?string $googleId,
    ?string $appleId,
    string $name,
    string $email
): void {
    if (columnExists($db, 'users', 'firebase_uid')) {
        $db->prepare('UPDATE users SET firebase_uid = ? WHERE id = ? AND (firebase_uid IS NULL OR firebase_uid = \'\')')
            ->execute([$firebaseUid, $userId]);
    }
    if ($googleId) {
        $db->prepare('UPDATE users SET google_id = ? WHERE id = ? AND (google_id IS NULL OR google_id = \'\')')
            ->execute([$googleId, $userId]);
    }
    if ($appleId && columnExists($db, 'users', 'apple_id')) {
        $db->prepare('UPDATE users SET apple_id = ? WHERE id = ? AND (apple_id IS NULL OR apple_id = \'\')')
            ->execute([$appleId, $userId]);
    }
    if ($name !== '') {
        $db->prepare('UPDATE users SET name = ? WHERE id = ? AND (name IS NULL OR name = \'\')')
            ->execute([$name, $userId]);
    }
    if ($email !== '') {
        $db->prepare('UPDATE users SET email = ? WHERE id = ? AND (email IS NULL OR email = \'\')')
            ->execute([$email, $userId]);
    }
}
