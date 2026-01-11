<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint aux artistes
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'artiste') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = getDBConnection();

// Historique des paroles générées
$stmt = $db->prepare("SELECT * FROM generated_lyrics WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$_SESSION['user_id']]);
$history = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assistance Écriture IA - WMA HUB</title>
    <link rel="icon" type="image/png" href="../../asset/icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; min-height: 100vh; overflow-x: hidden; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; display: flex; flex-direction: column; padding: 2rem 1.5rem; }
        .main-content { margin-left: 280px; padding: 2rem; min-height: 100vh; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.5); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; transform: translateX(5px); }
        .glass-card { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 1.5rem; padding: 2rem; }
        .input-glass { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 1rem; color: #fff; padding: 1rem; width: 100%; outline: none; transition: all 0.3s ease; }
        .input-glass:focus { border-color: #ff6600; background: rgba(255, 102, 0, 0.05); }
        .btn-primary { background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%); color: #fff; font-weight: bold; padding: 1rem 2rem; border-radius: 1rem; transition: all 0.3s ease; display: inline-flex; items-center; gap: 0.5rem; }
        .btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 10px 20px -5px rgba(255, 102, 0, 0.4); }
        .btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }
        @media (max-width: 1024px) { .sidebar { transform: translateX(-100%); width: 0; padding: 0; } .main-content { margin-left: 0; } }
        .lyrics-box { background: rgba(255, 255, 255, 0.03); border-radius: 1.5rem; padding: 2rem; min-height: 400px; font-family: 'Courier New', Courier, monospace; line-height: 1.8; white-space: pre-wrap; font-size: 0.9rem; }
        .loader { width: 24px; height: 24px; border: 3px solid #fff; border-bottom-color: transparent; border-radius: 50%; display: inline-block; animation: rotation 1s linear infinite; }
        @keyframes rotation { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="bg-glow"></div>
    <aside class="sidebar">
        <div class="flex items-center gap-4 mb-12 px-2"><img src="../../asset/trans.png" alt="Logo" class="h-10"><h1 class="text-xl font-bold bg-gradient-to-r from-orange-500 to-orange-300 bg-clip-text text-transparent">WMA HUB</h1></div>
        <nav class="flex-1">
            <a href="index.php" class="nav-link"><i class="fas fa-th-large"></i>Tableau de bord</a>
            <a href="submit.php" class="nav-link"><i class="fas fa-plus-circle"></i>Soumettre</a>
            <a href="services.php" class="nav-link active"><i class="fas fa-magic"></i>Services</a>
            <a href="notifications.php" class="nav-link"><i class="fas fa-bell"></i>Notifications</a>
        </nav>
        <div class="mt-auto pt-6 border-t border-white/5">
            <a href="../../auth/logout.php" class="nav-link !text-red-500 hover:!bg-red-500/10"><i class="fas fa-power-off"></i>Déconnexion</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="mb-12 flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div>
                <a href="services.php" class="text-orange-500/60 hover:text-orange-500 font-bold text-xs uppercase tracking-widest transition-all mb-4 inline-block"><i class="fas fa-arrow-left mr-2"></i>Retour aux services</a>
                <h2 class="text-4xl font-black mb-2">Assistance <span class="text-orange-500">IA</span></h2>
                <p class="text-gray-400">Générez des paroles inspirantes en quelques secondes.</p>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Formulaire -->
            <div class="lg:col-span-1">
                <form id="lyricsForm" class="glass-card space-y-6">
                    <div>
                        <label class="block text-[10px] font-black uppercase text-gray-500 tracking-widest mb-2">Titre de la chanson</label>
                        <input type="text" name="title" required class="input-glass" placeholder="Ex: Coucher de soleil">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase text-gray-500 tracking-widest mb-2">Thème détaillé</label>
                        <textarea name="theme" required class="input-glass h-32" placeholder="Décrivez l'histoire ou l'émotion..."></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-500 tracking-widest mb-2">Langue</label>
                            <select name="language" class="input-glass">
                                <option value="fr">Français</option>
                                <option value="en">Anglais</option>
                                <option value="lingala">Lingala</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-500 tracking-widest mb-2">Genre musical</label>
                            <input type="text" name="genre" class="input-glass" placeholder="Ex: Afrobeat, Rap">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase text-gray-500 tracking-widest mb-2">Public ciblé</label>
                        <input type="text" name="audience" class="input-glass" placeholder="Ex: Jeunesse, Amoureux">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase text-gray-500 tracking-widest mb-2">Durée estimée</label>
                        <input type="text" name="duration" class="input-glass" placeholder="Ex: 3:00">
                    </div>
                    <button type="submit" id="submitBtn" class="btn-primary w-full justify-center">
                        <span id="btnText">Générer mes Paroles</span>
                        <div id="btnLoader" class="loader hidden"></div>
                    </button>
                </form>
            </div>

            <!-- Résultats -->
            <div class="lg:col-span-2 space-y-8">
                <div id="resultsArea" class="hidden animate-fade-in">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold">Suggestions de l'IA</h3>
                        <button onclick="downloadPDF()" class="bg-white/5 hover:bg-white/10 text-white px-4 py-2 rounded-xl border border-white/10 text-xs font-bold uppercase transition-all">
                            <i class="fas fa-file-pdf mr-2 text-red-500"></i> Télécharger PDF
                        </button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="pdfContent">
                        <div class="space-y-4">
                            <span class="text-[10px] font-black uppercase text-orange-500 tracking-widest">Version 1</span>
                            <div id="lyrics1" class="lyrics-box"></div>
                        </div>
                        <div class="space-y-4">
                            <span class="text-[10px] font-black uppercase text-blue-500 tracking-widest">Version 2</span>
                            <div id="lyrics2" class="lyrics-box"></div>
                        </div>
                    </div>
                </div>

                <!-- Historique -->
                <section>
                    <h3 class="text-xl font-bold mb-6">Historique des créations</h3>
                    <div class="space-y-4">
                        <?php foreach ($history as $item): ?>
                            <button onclick="loadFromHistory(<?= $item['id'] ?>)" class="w-full text-left glass-card hover:border-orange-500/30 transition-all px-6 py-4 flex items-center justify-between group">
                                <div>
                                    <p class="font-bold text-white group-hover:text-orange-500 transition-colors"><?= htmlspecialchars($item['title'] ?: 'Sans titre') ?></p>
                                    <p class="text-[10px] text-gray-500 uppercase tracking-widest"><?= date('d/m/Y H:i', strtotime($item['created_at'])) ?></p>
                                </div>
                                <i class="fas fa-chevron-right text-gray-700 group-hover:text-orange-500"></i>
                            </button>
                        <?php endforeach; ?>
                        <?php if (empty($history)): ?>
                            <div class="text-center py-12 glass-card border-dashed">
                                <p class="text-gray-500 text-xs uppercase font-black tracking-widest">Aucune création pour le moment</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <!-- Modal Historique (Simplifié par inject direct dans l'UI) -->

    <script>
        const form = document.getElementById('lyricsForm');
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const btnLoader = document.getElementById('btnLoader');
        const resultsArea = document.getElementById('resultsArea');
        const lyrics1 = document.getElementById('lyrics1');
        const lyrics2 = document.getElementById('lyrics2');

        form.onsubmit = async (e) => {
            e.preventDefault();
            btnText.classList.add('hidden');
            btnLoader.classList.remove('hidden');
            submitBtn.disabled = true;

            const formData = new FormData(form);
            
            try {
                const response = await fetch('../../api/gemini.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    lyrics1.textContent = data.lyrics_1;
                    lyrics2.textContent = data.lyrics_2;
                    resultsArea.classList.remove('hidden');
                    window.scrollTo({ top: resultsArea.offsetTop - 50, behavior: 'smooth' });
                } else {
                    alert(data.message || 'Une erreur est survenue.');
                }
            } catch (err) {
                alert('Erreur réseau ou serveur.');
            } finally {
                btnText.classList.remove('hidden');
                btnLoader.classList.add('hidden');
                submitBtn.disabled = false;
            }
        };

        function downloadPDF() {
            const element = document.getElementById('pdfContent');
            const title = document.querySelector('input[name="title"]').value || 'Paroles';
            const opt = {
                margin: 1,
                filename: `WMAHUB_Lyrics_${title}.pdf`,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, backgroundColor: '#0a0a0c' },
                jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }

        async function loadFromHistory(id) {
            // Re-fetch direct ou on pourrait stocker en data-attr
            const response = await fetch('../../api/gemini.php?action=get&id=' + id);
            const data = await response.json();
            if (data.success) {
                lyrics1.textContent = data.lyrics_1;
                lyrics2.textContent = data.lyrics_2;
                resultsArea.classList.remove('hidden');
                window.scrollTo({ top: resultsArea.offsetTop - 50, behavior: 'smooth' });
            }
        }
    </script>
</body>
</html>
