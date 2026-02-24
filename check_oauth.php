<?php
require_once __DIR__ . '/includes/config.php';

// On s'assure que PHP affiche les erreurs pour ce script de diagnostic
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <title>Diagnostic Google OAuth - WMA Hub</title>
    <style>
        body { font-family: sans-serif; padding: 20px; line-height: 1.6; background: #f4f4f4; }
        .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); max-width: 800px; margin: auto; }
        h1 { color: #333; }
        code { background: #eee; padding: 2px 5px; border-radius: 4px; font-weight: bold; }
        .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .copy-box { background: #333; color: #fff; padding: 10px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; }
        .copy-btn { background: #ff6600; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; }
    </style>
</head>
<body>
<div class='container'>
    <h1>Diagnostic Google OAuth</h1>
    
    <div class='alert alert-info'>
        Pour corriger l'erreur <code>redirect_uri_mismatch</code>, l'URL ci-dessous doit être ajoutée exactement dans votre <strong>Console Google Cloud</strong> sous <strong>Identifiants > ID client OAuth 2.0 > URI de redirection autorisés</strong>.
    </div>

    <p><strong>URL de redirection actuelle générée :</strong></p>
    <div class='copy-box'>
        <code id='redirectUrl'>" . htmlspecialchars(GOOGLE_REDIRECT_URL) . "</code>
        <button class='copy-btn' onclick='copyUrl()'>Copier</button>
    </div>

    <hr>
    <h3>Détails techniques :</h3>
    <ul>
        <li><strong>Protocole détecté :</strong> " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'HTTPS' : 'HTTP') . "</li>
        <li><strong>Hôte :</strong> " . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'Inconnu') . "</li>
        <li><strong>Client ID :</strong> <code>" . htmlspecialchars(GOOGLE_CLIENT_ID) . "</code></li>
    </ul>

    <p><a href='auth/login.php'>&larr; Retour à la page de connexion</a></p>
</div>

<script>
function copyUrl() {
    var url = document.getElementById('redirectUrl').innerText;
    navigator.clipboard.writeText(url).then(function() {
        alert('URL copiée !');
    }, function() {
        alert('Erreur lors de la copie');
    });
}
</script>
</body>
</html>";
?>
