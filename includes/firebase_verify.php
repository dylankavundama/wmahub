<?php
/**
 * Vérification des ID tokens Firebase (projet mobile uawma-70e70).
 * Ne pas confondre avec GOOGLE_CLIENT_ID (OAuth site web).
 */

if (!defined('FIREBASE_PROJECT_ID')) {
    define('FIREBASE_PROJECT_ID', getenv('FIREBASE_PROJECT_ID') ?: 'uawma-70e70');
}
if (!defined('FIREBASE_WEB_API_KEY')) {
    define('FIREBASE_WEB_API_KEY', getenv('FIREBASE_WEB_API_KEY') ?: 'AIzaSyDbSttnS1qfZ-C41OVlIGKABBnlAobfGzk');
}

/**
 * Valide un Firebase ID token via l'API Identity Toolkit.
 *
 * @return array{uid: string, email: string, name: string, google_id: ?string, apple_id: ?string}
 */
function verifyFirebaseIdToken(string $idToken): array
{
    $idToken = trim($idToken);
    if ($idToken === '') {
        throw new InvalidArgumentException('Firebase ID token requis');
    }

    $url = 'https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=' . urlencode(FIREBASE_WEB_API_KEY);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(['idToken' => $idToken]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 7,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $responseRaw = curl_exec($ch);
    $curlError   = curl_error($ch);
    $httpCode    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($responseRaw === false || $curlError !== '') {
        throw new RuntimeException("Impossible de joindre Firebase Auth : $curlError");
    }

    $payload = json_decode($responseRaw, true);
    if (!is_array($payload)) {
        throw new RuntimeException("Réponse Firebase invalide (HTTP $httpCode)");
    }

    if (isset($payload['error']['message'])) {
        throw new RuntimeException('Token Firebase invalide : ' . $payload['error']['message']);
    }

    $users = $payload['users'] ?? null;
    if (!is_array($users) || count($users) === 0) {
        throw new RuntimeException('Utilisateur Firebase introuvable pour ce token');
    }

    $firebaseUser = $users[0];
    $uid = $firebaseUser['localId'] ?? '';
    if ($uid === '') {
        throw new RuntimeException('UID Firebase manquant');
    }

    $email = $firebaseUser['email'] ?? '';
    $name = $firebaseUser['displayName'] ?? '';

    $googleId = null;
    $appleId = null;

    foreach ($firebaseUser['providerUserInfo'] ?? [] as $provider) {
        $providerId = $provider['providerId'] ?? '';
        $federatedId = $provider['federatedId'] ?? $provider['rawId'] ?? '';
        if ($providerId === 'google.com' && $federatedId !== '') {
            $googleId = $federatedId;
        }
        if ($providerId === 'apple.com' && $federatedId !== '') {
            $appleId = $federatedId;
        }
        if ($name === '' && !empty($provider['displayName'])) {
            $name = $provider['displayName'];
        }
        if ($email === '' && !empty($provider['email'])) {
            $email = $provider['email'];
        }
    }

    $photoUrl = $firebaseUser['photoUrl'] ?? '';
    if ($photoUrl === '') {
        foreach ($firebaseUser['providerUserInfo'] ?? [] as $provider) {
            if (!empty($provider['photoUrl'])) {
                $photoUrl = $provider['photoUrl'];
                break;
            }
        }
    }

    if ($name === '') {
        $name = $email !== '' ? explode('@', $email)[0] : 'Utilisateur';
    }

    return [
        'uid'       => $uid,
        'email'     => $email,
        'name'      => $name,
        'google_id' => $googleId,
        'apple_id'  => $appleId,
        'photo_url' => $photoUrl !== '' ? $photoUrl : null,
    ];
}
