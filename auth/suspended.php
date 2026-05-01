<?php
require_once __DIR__ . '/../includes/config.php';

// If account is somehow active, redirect away
if (isset($_SESSION['user_id'])) {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT is_active FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    if ($stmt->fetchColumn()) {
        header('Location: ../index.php');
        exit;
    }
} else {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compte Suspendu - WMA Hub</title>
    <link rel="icon" type="image/png" href="/asset/icon.png">
    <link rel="apple-touch-icon" href="/asset/icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 2rem; padding: 4rem; width: 100%; max-width: 600px; text-align: center; }
    </style>
</head>
<body>
    <div class="bg-glow"></div>
    <div class="glass-card">
        <div class="w-24 h-24 bg-red-500/10 rounded-full flex items-center justify-center text-red-500 text-5xl mx-auto mb-8 border border-red-500/20">
            <i class="fas fa-user-slash"></i>
        </div>
        <h1 class="text-4xl font-black mb-4">Accès <span class="text-red-500">Suspendu</span></h1>
        <p class="text-gray-400 text-lg mb-10">Votre compte a été temporairement désactivé par l'administration. Veuillez contacter le support pour plus d'informations.</p>
        
        <div class="space-y-4">
            <a href="https://wa.me/243..." class="flex items-center justify-center gap-3 w-full py-4 bg-green-500/10 hover:bg-green-500/20 text-green-500 rounded-xl font-bold transition-all border border-green-500/20">
                <i class="fab fa-whatsapp"></i> Contacter le Support
            </a>
            <a href="logout.php" class="block text-gray-500 hover:text-white transition-colors text-sm font-medium">Se déconnecter</a>
        </div>
    </div>
</body>
</html>
