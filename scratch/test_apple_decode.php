<?php
/**
 * Test decoding of Apple Identity Token (JWT)
 * Ce script démontre pourquoi le décodage actuel de l'identité Apple dans l'API peut échouer.
 */

// Simulation d'un payload JWT d'Apple (contenant des caractères base64url)
// Un vrai JWT Apple contient des caractères '-' et '_' et n'a aucun padding '=' à la fin.
$sample_payload = [
    "iss" => "https://appleid.apple.com",
    "sub" => "000123.abcde_fgh-ijklmop.4567", // sub avec '-' et '_'
    "aud" => "com.ua.wmahub.service",
    "exp" => time() + 3600,
    "email" => "user-test@example.com"
];

$json_payload = json_encode($sample_payload);

// Encodage en base64url (norme JWT)
$base64url = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($json_payload));

echo "--- DÉBUT DU TEST DE DÉCODAGE JWT APPLE ---\n\n";
echo "Payload original encode en Base64URL : \n" . $base64url . "\n\n";

// --- MÉTHODE ACTUELLE (Dans votre apple_auth_mobile.php) ---
$method_current_decoded = base64_decode($base64url);
$method_current_json = json_decode($method_current_decoded, true);

echo "1. Méthode actuelle (base64_decode simple) :\n";
if ($method_current_json) {
    echo "✅ Succès (dans ce cas particulier)\n";
    print_r($method_current_json);
} else {
    echo "❌ ÉCHEC : Le décodage a retourné FALSE ou un JSON invalide.\n";
    echo "Contenu brut decodé : " . var_export($method_current_decoded, true) . "\n";
}
echo "\n";

// --- MÉTHODE CORRIGÉE (100% robuste pour JWT) ---
$base64 = str_replace(['-', '_'], ['+', '/'], $base64url);
$padding = strlen($base64) % 4;
if ($padding) {
    $base64 .= str_repeat('=', 4 - $padding);
}
$method_robust_decoded = base64_decode($base64);
$method_robust_json = json_decode($method_robust_decoded, true);

echo "2. Méthode robuste corrigée :\n";
if ($method_robust_json) {
    echo "✅ SUCCÈS À 100%\n";
    print_r($method_robust_json);
} else {
    echo "❌ Échec de la méthode robuste\n";
}
?>
