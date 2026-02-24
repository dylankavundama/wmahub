<?php
require_once __DIR__ . '/auth_artist.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMA HUB - Services Artiste</title>
    <link rel="icon" type="image/jpeg" href="../../asset/placeholder.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; margin: 0; min-height: 100vh; overflow-x: hidden; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .sidebar { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .sidebar.active { transform: translateX(0); }
        @media (max-width: 1024px) { .sidebar { transform: translateX(-100%); position: fixed; z-index: 50; height: 100vh; } }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px); border: 1px border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 20px; transition: all 0.3s ease; padding: 24px; height: 100%; display: flex; flex-direction: column; }
        .glass-card:hover { background: rgba(255, 255, 255, 0.05); border-color: rgba(255, 102, 0, 0.2); transform: translateY(-5px); }
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: #94a3b8; transition: all 0.3s ease; margin-bottom: 4px; }
        .nav-link:hover:not(.active) { background: rgba(255, 255, 255, 0.03); color: #fff; }
        .nav-link.active { background: linear-gradient(135deg, #ff6600 0%, #ff8c00 100%); color: #fff; box-shadow: 0 4px 15px rgba(255, 102, 0, 0.3); }
        .nav-link i { font-size: 1.1rem; }
        #wma-global-loader { position: fixed; inset: 0; background: #0a0a0c; z-index: 9999; display: flex; align-items: center; justify-content: center; transition: opacity 0.5s ease; }
        .loader-spin { width: 50px; height: 50px; border: 3px solid rgba(255, 102, 0, 0.1); border-top-color: #ff6600; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .icon-box { width: 64px; height: 64px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; transition: all 0.3s ease; }
    </style>
</head>
<body>
    <div id="wma-global-loader">
        <div class="loader-spin"></div>
    </div>
    <div class="bg-glow"></div>

    <!-- Mobile Header -->
    <div class="lg:hidden flex items-center justify-between p-4 bg-[#0a0a0c] border-b border-white/5 sticky top-0 z-40">
        <div class="flex items-center gap-3">
            <img src="../../asset/trans.png" alt="Logo" class="w-8 h-8 object-contain">
            <span class="text-lg font-bold tracking-tighter">WMA ARTISTE</span>
        </div>
        <button id="sidebarToggle" class="text-white text-2xl p-2"><i class="fas fa-bars"></i></button>
    </div>

    <!-- Sidebar Overlay -->
    <div id="overlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 hidden lg:hidden"></div>

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar w-72 bg-[#0d0d0f] border-r border-white/5 flex flex-col p-6 overflow-y-auto">
            <div class="flex items-center gap-4 mb-10 px-2">
                <img src="../../asset/trans.png" alt="Logo" class="w-10 h-10 object-contain">
                <div>
                    <h1 class="text-xl font-bold tracking-tighter bg-gradient-to-r from-white to-white/60 bg-clip-text text-transparent">WMA HUB</h1>
                    <p class="text-[10px] text-orange-500 font-bold uppercase tracking-widest">We move, WMAFam</p>
                </div>
            </div>

            <nav class="flex-1">
                <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
                    <i class="fas fa-th-large"></i>
                    <span class="font-medium">Tableau de bord</span>
                </a>
                <a href="submit.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'submit.php' ? 'active' : '' ?>">
                    <i class="fas fa-plus-circle"></i>
                    <span class="font-medium">Soumettre</span>
                </a>
                <a href="services.php" class="nav-link <?= strpos(basename($_SERVER['PHP_SELF']), 'services') !== false ? 'active' : '' ?>">
                    <i class="fas fa-magic"></i>
                    <span class="font-medium">Services</span>
                </a>
                <a href="notifications.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'notifications.php' ? 'active' : '' ?>">
                    <i class="fas fa-bell"></i>
                    <span class="font-medium">Notifications</span>
                </a>
                <a href="catalogue.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'catalogue.php' ? 'active' : '' ?>">
                    <i class="fas fa-music"></i>
                    <span class="font-medium">Catalogue</span>
                </a>
                <a href="stats.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'stats.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-line"></i>
                    <span class="font-medium">Stats</span>
                </a>
                <a href="reviews.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'reviews.php' ? 'active' : '' ?>">
                    <i class="fas fa-star"></i>
                    <span class="font-medium">Laisser un avis</span>
                </a>
                <a href="revenues.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'revenues.php' ? 'active' : '' ?>">
                    <i class="fas fa-wallet"></i>
                    <span class="font-medium">Revenus</span>
                </a>
                <a href="contrat.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'contrat.php' ? 'active' : '' ?>">
                    <i class="fas fa-file-contract"></i>
                    <span class="font-medium">Contrat</span>
                </a>
            </nav>

            <div class="mt-auto pt-6 border-t border-white/5">
                <div class="flex items-center gap-3 p-3 rounded-2xl bg-white/[0.03] border border-white/5 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-orange-500 flex items-center justify-center text-white shadow-lg shadow-orange-500/20">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="flex-1 overflow-hidden">
                        <p class="text-sm font-bold truncate"><?= htmlspecialchars($_SESSION['user_name']) ?></p>
                        <p class="text-[10px] text-gray-500 uppercase font-black">Artiste</p>
                    </div>
                </div>
                <a href="../../auth/logout.php" class="nav-link text-red-500 hover:bg-red-500/10 mb-0">
                    <i class="fas fa-power-off"></i>
                    <span class="font-medium">Déconnexion</span>
                </a>
            </div>
        </aside>

        <main class="flex-1 p-6 lg:p-12">
            <header class="mb-12">
                <h2 class="text-4xl font-black tracking-tighter mb-2">Services <span class="text-orange-500">Artiste</span></h2>
                <p class="text-gray-500 font-medium tracking-tight">Boostez votre créativité avec nos outils intelligents.</p>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Writing Assistant -->
                <a href="writing_assistant.php" class="block">
                    <div class="glass-card group">
                        <div class="icon-box bg-orange-500/10 text-orange-500 mb-6 group-hover:scale-110">
                            <i class="fas fa-pen-nib"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-4">Assistance en Écriture</h3>
                        <p class="text-gray-400 leading-relaxed mb-6">Utilisez la puissance de l'IA pour générer des paroles de chansons basées sur vos thèmes, styles et public cible.</p>
                        <div class="mt-auto flex items-center text-orange-500 font-bold uppercase text-[10px] tracking-widest">
                            Commencer <i class="fas fa-arrow-right ml-2"></i>
                        </div>
                    </div>
                </a>

                <!-- Notepad -->
                <a href="notepad.php" class="block">
                    <div class="glass-card group">
                        <div class="icon-box bg-blue-500/10 text-blue-500 mb-6 group-hover:scale-110">
                            <i class="fas fa-sticky-note"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-4">Bloc-Note</h3>
                        <p class="text-gray-400 leading-relaxed mb-6">Gardez une trace de toutes vos idées, mélodies et textes en cours de création. Accessible partout.</p>
                        <div class="mt-auto flex items-center text-blue-500 font-bold uppercase text-[10px] tracking-widest">
                            Ouvrir mes notes <i class="fas fa-arrow-right ml-2"></i>
                        </div>
                    </div>
                </a>

                <!-- Text Correction -->
                <a href="text_correction.php" class="block">
                    <div class="glass-card group">
                        <div class="icon-box bg-green-500/10 text-green-500 mb-6 group-hover:scale-110">
                            <i class="fas fa-wand-magic-sparkles"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-4">Corriger mon texte</h3>
                        <p class="text-gray-400 leading-relaxed mb-6">Optimisez vos écrits. Notre IA corrige les fautes et améliore le style tout en respectant votre intention.</p>
                        <div class="mt-auto flex items-center text-green-500 font-bold uppercase text-[10px] tracking-widest">
                            Lancer la correction <i class="fas fa-arrow-right ml-2"></i>
                        </div>
                    </div>
                </a>

                <!-- Chorus Generator -->
                <a href="chorus_generator.php" class="block">
                    <div class="glass-card group">
                        <div class="icon-box bg-purple-500/10 text-purple-500 mb-6 group-hover:scale-110">
                            <i class="fas fa-microphone-lines"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-4">Générer un Refrain</h3>
                        <p class="text-gray-400 leading-relaxed mb-6">Créez un refrain mémorable intégré à vos couplets. L'IA sublime votre morceau avec des lignes accrocheuses.</p>
                        <div class="mt-auto flex items-center text-purple-500 font-bold uppercase text-[10px] tracking-widest">
                            Générer maintenant <i class="fas fa-arrow-right ml-2"></i>
                        </div>
                    </div>
                </a>

                <!-- TikTok For Artist -->
                <a href="https://wa.me/243981559140?text=Bonjour%20WMA%20Hub,%20je%20souhaite%20demander%20un%20compte%20TikTok%20For%20Artiste%20(25$)." target="_blank" class="block">
                    <div class="glass-card group" style="border-color: rgba(236, 72, 153, 0.2);">
                        <div class="icon-box bg-pink-500/10 text-pink-500 mb-6 group-hover:scale-110">
                            <i class="fab fa-tiktok"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-4">TikTok For Artiste</h3>
                        <p class="text-gray-400 leading-relaxed mb-6">Optimisez votre présence sur TikTok avec un compte certifié Artiste pour booster votre visibilité.</p>
                        <div class="flex justify-between items-center mt-auto">
                            <span class="text-white font-black text-lg font-black tracking-tighter">25 $</span>
                            <div class="flex items-center text-pink-500 font-bold uppercase text-[10px] tracking-widest">
                                Envoyer la demande <i class="fab fa-whatsapp ml-2"></i>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- Audio Platform Certification -->
                <a href="https://wa.me/243981559140?text=Bonjour%20WMA%20Hub,%20je%20souhaite%20la%20certification%20sur%20toutes%20les%20plateformes%20audio%20(Gratuit)." target="_blank" class="block">
                    <div class="glass-card group" style="border-color: rgba(96, 165, 250, 0.2);">
                        <div class="icon-box bg-blue-400/10 text-blue-400 mb-6 group-hover:scale-110">
                            <i class="fas fa-check-decagram"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-4">Certification Plateformes</h3>
                        <p class="text-gray-400 leading-relaxed mb-6">Obtenez le badge de vérification sur toutes les plateformes de streaming audio mondiales.</p>
                        <div class="flex justify-between items-center mt-auto">
                            <span class="text-green-500 font-black text-lg uppercase font-black tracking-widest">Gratuit</span>
                            <div class="flex items-center text-blue-400 font-bold uppercase text-[10px] tracking-widest">
                                Envoyer la demande <i class="fab fa-whatsapp ml-2"></i>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- YouTube Certification -->
                <a href="https://wa.me/243981559140?text=Bonjour%20WMA%20Hub,%20je%20souhaite%20la%20certification%20et%20monétisation%20YouTube%20(450$)." target="_blank" class="block">
                    <div class="glass-card group" style="border-color: rgba(239, 68, 68, 0.2);">
                        <div class="icon-box bg-red-500/10 text-red-500 mb-6 group-hover:scale-110">
                            <i class="fab fa-youtube"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-4">YouTube Certification</h3>
                        <p class="text-gray-400 leading-relaxed mb-6">Passez au niveau supérieur avec une chaîne officielle d'artiste et la monétisation complète de vos vidéos.</p>
                        <div class="flex justify-between items-center mt-auto">
                            <span class="text-white font-black text-lg font-black tracking-tighter">450 $</span>
                            <div class="flex items-center text-red-500 font-bold uppercase text-[10px] tracking-widest">
                                Envoyer la demande <i class="fab fa-whatsapp ml-2"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </main>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const toggle = document.getElementById('sidebarToggle');

        function toggleSidebar() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('hidden');
        }

        if (toggle) toggle.addEventListener('click', toggleSidebar);
        if (overlay) overlay.addEventListener('click', toggleSidebar);

        window.addEventListener('load', () => {
            const loader = document.getElementById('wma-global-loader');
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(() => loader.style.display = 'none', 500);
            }
        });
    </script>
</body>
</html>
