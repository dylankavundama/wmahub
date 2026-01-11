<?php
require_once __DIR__ . '/../../includes/config.php';

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
    <title>Générateur de Refrain - WMA HUB</title>
    <link rel="icon" type="image/png" href="../../asset/icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; min-height: 100vh; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 100% 0%, #3b0764 0%, #0a0a0c 100%); z-index: -1; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; display: flex; flex-direction: column; padding: 2rem 1.5rem; }
        .main-content { margin-left: 280px; padding: 2rem; min-height: 100vh; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.5); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: rgba(168, 85, 247, 0.1); color: #a855f7; }
        .glass-card { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 1.5rem; padding: 2rem; }
        .input-glass { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 1rem; color: #fff; padding: 1rem; width: 100%; outline: none; transition: 0.3s; }
        .input-glass:focus { border-color: #a855f7; background: rgba(168, 85, 247, 0.05); }
        .btn-purple { background: linear-gradient(135deg, #7e22ce 0%, #a855f7 100%); color: #fff; font-weight: bold; padding: 1rem 2rem; border-radius: 1rem; transition: 0.3s; }
        .btn-purple:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 10px 20px -5px rgba(168, 85, 247, 0.4); }
        .loader { width: 20px; height: 20px; border: 2px solid #fff; border-bottom-color: transparent; border-radius: 50%; display: inline-block; animation: rotation 1s linear infinite; vertical-align: middle; margin-right: 10px; }
        @keyframes rotation { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .result-box { background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 2rem; padding: 3rem; font-family: 'Courier New', Courier, monospace; line-height: 2; white-space: pre-wrap; font-size: 1rem; }
        @media (max-width: 1024px) { .sidebar { display: none; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <div class="bg-glow"></div>
    <aside class="sidebar">
        <div class="flex items-center gap-4 mb-12 px-2"><img src="../../asset/trans.png" alt="Logo" class="h-10"><h1 class="text-xl font-bold text-purple-500">WMA HUB</h1></div>
        <nav class="flex-1">
            <a href="index.php" class="nav-link"><i class="fas fa-th-large"></i>Tableau de bord</a>
            <a href="services.php" class="nav-link active"><i class="fas fa-magic"></i>Services</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="mb-12">
            <a href="services.php" class="text-purple-500/60 hover:text-purple-500 font-bold text-xs uppercase tracking-widest transition-all mb-4 inline-block"><i class="fas fa-arrow-left mr-2"></i>Retour</a>
            <h2 class="text-4xl font-black mb-2">Générateur de <span class="text-purple-500">Refrain</span></h2>
            <p class="text-gray-400">Transformez vos couplets en succès avec un refrain mémorable.</p>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <form id="chorusForm" class="glass-card space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black uppercase text-gray-500 mb-2">Titre</label>
                        <input type="text" name="title" required class="input-glass" placeholder="Nom de la chanson">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase text-gray-500 mb-2">Style</label>
                        <input type="text" name="style" required class="input-glass" placeholder="Afrobeat, Pop, Rap...">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase text-gray-500 mb-2">Thème et Cible</label>
                    <input type="text" name="theme" required class="input-glass" placeholder="Amour, persévérance, club...">
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase text-gray-500 mb-2">Votre Couplet (existant)</label>
                    <textarea name="verse" required class="input-glass h-64" placeholder="Collez votre couplet ici..."></textarea>
                </div>
                <button type="submit" id="submitBtn" class="btn-purple w-full">
                    <span id="btnLoader" class="loader hidden"></span>
                    <span id="btnText">Générer le Refrain Idéal</span>
                </button>
            </form>

            <div id="resultArea" class="hidden animate-fade-in">
                <div class="flex items-center justify-between mb-6 px-4">
                    <h3 class="font-bold text-lg">Composition avec Refrain</h3>
                    <button id="copyResult" class="text-xs font-bold uppercase tracking-widest bg-white/5 hover:bg-white/10 px-4 py-2 rounded-xl transition-all">
                        <i class="fas fa-copy mr-2 text-purple-500"></i> Copier tout
                    </button>
                </div>
                <div id="lyricsResult" class="result-box text-white"></div>
            </div>
        </div>
    </main>

    <script>
        const form = document.getElementById('chorusForm');
        const submitBtn = document.getElementById('submitBtn');
        const btnLoader = document.getElementById('btnLoader');
        const btnText = document.getElementById('btnText');
        const resultArea = document.getElementById('resultArea');
        const lyricsResult = document.getElementById('lyricsResult');
        const copyResult = document.getElementById('copyResult');

        form.onsubmit = async (e) => {
            e.preventDefault();
            btnLoader.classList.remove('hidden');
            btnText.textContent = 'Génération...';
            submitBtn.disabled = true;

            const formData = new FormData(form);

            try {
                const response = await fetch('../../api/gemini.php?action=generate_chorus', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    lyricsResult.innerHTML = data.result;
                    resultArea.classList.remove('hidden');
                    window.scrollTo({ top: resultArea.offsetTop - 50, behavior: 'smooth' });
                } else {
                    alert(data.message || 'Une erreur est survenue.');
                }
            } catch (err) {
                alert('Erreur réseau.');
            } finally {
                btnLoader.classList.add('hidden');
                btnText.textContent = 'Générer le Refrain Idéal';
                submitBtn.disabled = false;
            }
        };

        copyResult.onclick = () => {
             // On utilise innerText pour garder les sauts de ligne mais ignorer le marquage HTML si besoin, 
             // ou on copie avec HTML si on veut garder la couleur ailleurs (pas possible en presse-papier text simple)
             navigator.clipboard.writeText(lyricsResult.innerText);
             copyResult.innerHTML = '<i class="fas fa-check mr-2"></i> Copié !';
             setTimeout(() => {
                 copyResult.innerHTML = '<i class="fas fa-copy mr-2"></i> Copier tout';
             }, 2000);
        };
    </script>
</body>
</html>
