<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint aux artistes
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'artiste') {
    header('Location: ../../auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMA HUB - Services Artiste</title>
    <link rel="icon" type="image/png" href="../../asset/icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; min-height: 100vh; overflow-x: hidden; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .glow-spot { position: fixed; width: 40vw; height: 40vw; background: radial-gradient(circle, rgba(255, 102, 0, 0.05) 0%, transparent 70%); border-radius: 50%; z-index: -1; filter: blur(80px); pointer-events: none; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; display: flex; flex-direction: column; padding: 2rem 1.5rem; }
        .main-content { margin-left: 280px; padding: 2rem; min-height: 100vh; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.5); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; transform: translateX(5px); }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 1.5rem; padding: 2rem; transition: all 0.3s ease; height: 100%; display: flex; flex-direction: column; }
        .glass-card:hover { transform: translateY(-10px); background: rgba(255, 255, 255, 0.05); border-color: rgba(255, 102, 0, 0.3); }
        @media (max-width: 1024px) { .sidebar { transform: translateX(-100%); width: 0; padding: 0; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <div class="bg-glow"></div>
    <aside class="sidebar">
            <div class="flex items-center gap-4 mb-10 px-2">
                <img src="../../asset/trans.png" alt="Logo" class="h-10">
                <div>
                    <h1 class="text-xl font-bold bg-gradient-to-r from-orange-500 to-orange-300 bg-clip-text text-transparent leading-none">WMA HUB</h1>
                    <p class="text-[8px] text-gray-500 font-bold uppercase tracking-[1px] mt-1">We Farm Your Talent</p>
                </div>
            </div>
        <nav class="flex-1">
            <a href="index.php" class="nav-link"><i class="fas fa-th-large"></i>Tableau de bord</a>
            <a href="submit.php" class="nav-link"><i class="fas fa-plus-circle"></i>Soumettre</a>
            <a href="services.php" class="nav-link active"><i class="fas fa-magic"></i>Services</a>
            <a href="notifications.php" class="nav-link"><i class="fas fa-bell"></i>Notifications</a>
        </nav>
        <div class="mt-auto pt-6 border-t border-white/5">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-full bg-orange-500/20 flex items-center justify-center text-orange-500 border border-orange-500/20"><i class="fas fa-user"></i></div>
                <div class="overflow-hidden"><p class="text-sm font-bold truncate"><?= htmlspecialchars($_SESSION['user_name']) ?></p></div>
            </div>
            <a href="../../auth/logout.php" class="nav-link !text-red-500 hover:!bg-red-500/10"><i class="fas fa-power-off"></i>Déconnexion</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="mb-12">
            <h2 class="text-4xl font-black mb-2">Services <span class="text-orange-500">Artiste</span></h2>
            <p class="text-gray-400">Boostez votre créativité avec nos outils intelligents.</p>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Writing Assistant -->
            <a href="writing_assistant.php" class="block group">
                <div class="glass-card">
                    <div class="w-16 h-16 rounded-2xl bg-orange-500/10 flex items-center justify-center text-orange-500 text-3xl mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-pen-nib"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Assistance en Écriture</h3>
                    <p class="text-gray-400 leading-relaxed mb-6">Utilisez la puissance de l'IA pour générer des paroles de chansons basées sur vos thèmes, styles et public cible.</p>
                    <div class="mt-auto flex items-center text-orange-500 font-bold uppercase text-xs tracking-widest">
                        Commencer <i class="fas fa-arrow-right ml-2"></i>
                    </div>
                </div>
            </a>

            <!-- Notepad -->
            <a href="notepad.php" class="block group">
                <div class="glass-card">
                    <div class="w-16 h-16 rounded-2xl bg-blue-500/10 flex items-center justify-center text-blue-500 text-3xl mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-sticky-note"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Bloc-Note</h3>
                    <p class="text-gray-400 leading-relaxed mb-6">Gardez une trace de toutes vos idées, mélodies et textes en cours de création. Accessible partout.</p>
                    <div class="mt-auto flex items-center text-blue-500 font-bold uppercase text-xs tracking-widest">
                        Ouvrir mes notes <i class="fas fa-arrow-right ml-2"></i>
                    </div>
                </div>
            </a>

            <!-- Text Correction -->
            <a href="text_correction.php" class="block group">
                <div class="glass-card">
                    <div class="w-16 h-16 rounded-2xl bg-green-500/10 flex items-center justify-center text-green-500 text-3xl mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-wand-magic-sparkles"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Corriger mon texte</h3>
                    <p class="text-gray-400 leading-relaxed mb-6">Optimisez vos écrits. Notre IA corrige les fautes et améliore le style tout en respectant votre intention.</p>
                    <div class="mt-auto flex items-center text-green-500 font-bold uppercase text-xs tracking-widest">
                        Lancer la correction <i class="fas fa-arrow-right ml-2"></i>
                    </div>
                </div>
            </a>

            <!-- Chorus Generator -->
            <a href="chorus_generator.php" class="block group">
                <div class="glass-card">
                    <div class="w-16 h-16 rounded-2xl bg-purple-500/10 flex items-center justify-center text-purple-500 text-3xl mb-6 group-hover:scale-110 transition-transform">
                        <i class="fas fa-microphone-lines"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Générer un Refrain</h3>
                    <p class="text-gray-400 leading-relaxed mb-6">Créez un refrain mémorable intégré à vos couplets. L'IA sublime votre morceau avec des lignes accrocheuses.</p>
                    <div class="mt-auto flex items-center text-purple-500 font-bold uppercase text-xs tracking-widest">
                        Générer maintenant <i class="fas fa-arrow-right ml-2"></i>
                    </div>
                </div>
            </a>
        </div>
    </main>
</body>
</html>
