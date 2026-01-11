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
    <title>Corriger mon texte - WMA HUB</title>
    <link rel="icon" type="image/png" href="../../asset/icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; min-height: 100vh; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; display: flex; flex-direction: column; padding: 2rem 1.5rem; }
        .main-content { margin-left: 280px; padding: 2rem; min-height: 100vh; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.5); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; }
        .glass-card { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 1.5rem; padding: 2rem; }
        textarea { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 1rem; color: #fff; padding: 1.5rem; width: 100%; outline: none; transition: 0.3s; min-height: 300px; resize: vertical; }
        textarea:focus { border-color: #ff6600; background: rgba(255, 102, 0, 0.05); }
        .btn-correct { background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%); color: #fff; font-weight: bold; padding: 1rem 2rem; border-radius: 1rem; transition: 0.3s; }
        .btn-correct:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 10px 20px -5px rgba(255, 102, 0, 0.4); }
        .btn-green:disabled { opacity: 0.5; cursor: not-allowed; }
        .loader { width: 20px; height: 20px; border: 2px solid #fff; border-bottom-color: transparent; border-radius: 50%; display: inline-block; animation: rotation 1s linear infinite; vertical-align: middle; margin-right: 10px; }
        @keyframes rotation { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        @media (max-width: 1024px) { .sidebar { display: none; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <div class="bg-glow"></div>
    <aside class="sidebar">
        <div class="flex items-center gap-4 mb-12 px-2"><img src="../../asset/trans.png" alt="Logo" class="h-10"><h1 class="text-xl font-bold bg-gradient-to-r from-orange-500 to-orange-300 bg-clip-text text-transparent">WMA HUB</h1></div>
        <nav class="flex-1">
            <a href="index.php" class="nav-link"><i class="fas fa-th-large"></i>Tableau de bord</a>
            <a href="services.php" class="nav-link active"><i class="fas fa-magic"></i>Services</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="mb-12">
            <a href="services.php" class="text-orange-500/60 hover:text-orange-500 font-bold text-xs uppercase tracking-widest transition-all mb-4 inline-block"><i class="fas fa-arrow-left mr-2"></i>Retour</a>
            <h2 class="text-4xl font-black mb-2">Correction <span class="text-orange-500">Intelligente</span></h2>
            <p class="text-gray-400">Polissez vos textes avec la précision d'un éditeur professionnel.</p>
        </header>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
            <div class="glass-card">
                <label class="block text-[10px] font-black uppercase text-gray-500 tracking-widest mb-4">Texte Original</label>
                <textarea id="originalText" placeholder="Collez votre texte ici..."></textarea>
                <div class="mt-6">
                    <button id="correctBtn" class="btn-correct w-full">
                        <span id="btnLoader" class="loader hidden"></span>
                        <span id="btnText">Corriger mon texte</span>
                    </button>
                </div>
            </div>

            <div class="glass-card">
                <label class="block text-[10px] font-black uppercase text-gray-500 tracking-widest mb-4">Version Corrigée</label>
                <div id="correctedResult" class="min-height-[300px] p-6 bg-white/[0.02] border border-white/5 rounded-2xl text-gray-300 leading-relaxed whitespace-pre-wrap italic">
                    Le résultat apparaîtra ici après correction...
                </div>
                <div class="mt-6 flex gap-4">
                    <button id="copyBtn" class="flex-1 py-4 bg-white/5 hover:bg-white/10 rounded-xl font-bold text-xs uppercase tracking-widest border border-white/10 transition-all hidden">
                        <i class="fas fa-copy mr-2"></i> Copier
                    </button>
                </div>
            </div>
        </div>
    </main>

    <script>
        const originalText = document.getElementById('originalText');
        const correctBtn = document.getElementById('correctBtn');
        const btnLoader = document.getElementById('btnLoader');
        const btnText = document.getElementById('btnText');
        const correctedResult = document.getElementById('correctedResult');
        const copyBtn = document.getElementById('copyBtn');

        correctBtn.onclick = async () => {
            const text = originalText.value.trim();
            if (!text) return alert('Veuillez entrer un texte.');

            btnLoader.classList.remove('hidden');
            btnText.textContent = 'Correction en cours...';
            correctBtn.disabled = true;

            try {
                const response = await fetch('../../api/gemini.php?action=correct_text', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ text: text })
                });
                const data = await response.json();
                if (data.success) {
                    correctedResult.textContent = data.corrected;
                    correctedResult.classList.remove('italic', 'text-gray-500');
                    correctedResult.classList.add('text-white');
                    copyBtn.classList.remove('hidden');
                } else {
                    alert(data.message || 'Erreur lors de la correction.');
                }
            } catch (err) {
                alert('Erreur réseau.');
            } finally {
                btnLoader.classList.add('hidden');
                btnText.textContent = 'Corriger mon texte';
                correctBtn.disabled = false;
            }
        };

        copyBtn.onclick = () => {
            navigator.clipboard.writeText(correctedResult.textContent);
            copyBtn.innerHTML = '<i class="fas fa-check mr-2"></i> Copié !';
            setTimeout(() => {
                copyBtn.innerHTML = '<i class="fas fa-copy mr-2"></i> Copier';
            }, 2000);
        };
    </script>
</body>
</html>
