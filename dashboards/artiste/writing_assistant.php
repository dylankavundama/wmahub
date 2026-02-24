<?php
require_once __DIR__ . '/auth_artist.php';

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
    <link rel="icon" type="image/jpeg" href="../../asset/placeholder.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; margin: 0; min-height: 100vh; overflow-x: hidden; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .sidebar { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .sidebar.active { transform: translateX(0); }
        @media (max-width: 1024px) { .sidebar { transform: translateX(-100%); position: fixed; z-index: 50; height: 100vh; } }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px); border: 1px border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 20px; transition: all 0.3s ease; }
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: #94a3b8; transition: all 0.3s ease; margin-bottom: 4px; }
        .nav-link:hover:not(.active) { background: rgba(255, 255, 255, 0.03); color: #fff; }
        .nav-link.active { background: linear-gradient(135deg, #ff6600 0%, #ff8c00 100%); color: #fff; box-shadow: 0 4px 15px rgba(255, 102, 0, 0.3); }
        .nav-link i { font-size: 1.1rem; }
        #wma-global-loader { position: fixed; inset: 0; background: #0a0a0c; z-index: 9999; display: flex; align-items: center; justify-content: center; transition: opacity 0.5s ease; }
        .loader-spin { width: 50px; height: 50px; border: 3px solid rgba(255, 102, 0, 0.1); border-top-color: #ff6600; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .input-glass { width: 100%; background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 16px; padding: 16px; color: #fff; transition: all 0.3s ease; }
        .input-glass:focus { outline: none; border-color: #ff6600; background: rgba(255, 255, 255, 0.05); box-shadow: 0 0 20px rgba(255, 102, 0, 0.1); }
        .btn-primary { display: flex; align-items: center; gap: 10px; padding: 16px 32px; background: linear-gradient(135deg, #ff6600 0%, #ff8c00 100%); color: #fff; border-radius: 16px; font-weight: 700; transition: all 0.3s ease; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(255, 102, 0, 0.2); }
        .result-box-glass { background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 20px; padding: 24px; color: #cbd5e1; line-height: 1.8; white-space: pre-line; font-size: 0.95rem; }
        .loader { width: 20px; height: 20px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.8s linear infinite; }
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
            <header class="mb-12 flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <a href="services.php" class="text-orange-500/60 hover:text-orange-500 font-bold text-[10px] uppercase tracking-widest transition-all mb-4 inline-block"><i class="fas fa-arrow-left mr-2"></i>Retour aux services</a>
                    <h2 class="text-4xl lg:text-5xl font-black mb-2 tracking-tighter text-white">Assistance <span class="text-orange-500 text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-orange-600">IA</span></h2>
                    <p class="text-gray-500 font-medium">Générez des paroles inspirantes en quelques secondes.</p>
                </div>
            </header>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Formulaire -->
                <div class="lg:col-span-1">
                    <form id="lyricsForm" class="glass-card p-6 md:p-8 space-y-6">
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-500 tracking-widest mb-2">Titre de la chanson</label>
                            <input type="text" name="title" required class="input-glass" placeholder="Ex: Coucher de soleil">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-500 tracking-widest mb-2">Thème détaillé</label>
                            <textarea name="theme" required class="input-glass h-32" placeholder="Décrivez l'story ou l'émotion..."></textarea>
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
                        <button type="submit" id="submitBtn" class="btn-primary w-full justify-center group uppercase tracking-widest text-xs">
                            <span id="btnText">Générer mes Paroles</span>
                            <div id="btnLoader" class="loader hidden"></div>
                        </button>
                    </form>
                </div>

                <!-- Résultats -->
                <div class="lg:col-span-2 space-y-8">
                    <div id="resultsArea" class="hidden animate-fade-in">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold tracking-tighter">Suggestions de l'IA</h3>
                            <button onclick="downloadPDF()" class="bg-white/5 hover:bg-white/10 text-white px-4 py-2 rounded-xl border border-white/10 text-[10px] font-bold uppercase transition-all flex items-center gap-2">
                                <i class="fas fa-file-pdf text-red-500"></i> Télécharger PDF
                            </button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="pdfContent">
                            <div class="space-y-4">
                                <span class="text-[10px] font-black uppercase tracking-widest text-orange-500">Version 1</span>
                                <div id="lyrics1" class="result-box-glass"></div>
                            </div>
                            <div class="space-y-4">
                                <span class="text-[10px] font-black uppercase tracking-widest text-blue-500">Version 2</span>
                                <div id="lyrics2" class="result-box-glass"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Historique -->
                    <section>
                        <h3 class="text-xl font-bold mb-6 tracking-tighter">Historique des créations</h3>
                        <div class="space-y-4">
                            <?php foreach ($history as $item): ?>
                                <button onclick="loadFromHistory(<?= $item['id'] ?>)" class="w-full text-left glass-card hover:bg-white/[0.05] transition-all px-6 py-5 flex items-center justify-between group border border-white/5">
                                    <div>
                                        <p class="font-bold text-white group-hover:text-orange-500 transition-colors uppercase tracking-tight text-sm"><?= htmlspecialchars($item['title'] ?: 'Sans titre') ?></p>
                                        <p class="text-[10px] text-gray-500 uppercase tracking-widest mt-1 font-medium"><?= date('d/m/Y H:i', strtotime($item['created_at'])) ?></p>
                                    </div>
                                    <div class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center group-hover:bg-orange-500/20 group-hover:text-orange-500 transition-all">
                                        <i class="fas fa-chevron-right text-xs"></i>
                                    </div>
                                </button>
                            <?php endforeach; ?>
                            <?php if (empty($history)): ?>
                                <div class="text-center py-12 glass-card border-dashed border-white/10">
                                    <p class="text-gray-500 text-[10px] uppercase font-black tracking-widest">Aucune création pour le moment</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
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
                    resultsArea.scrollIntoView({ behavior: 'smooth', block: 'start' });
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
            const response = await fetch('../../api/gemini.php?action=get&id=' + id);
            const data = await response.json();
            if (data.success) {
                lyrics1.textContent = data.lyrics_1;
                lyrics2.textContent = data.lyrics_2;
                resultsArea.classList.remove('hidden');
                resultsArea.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

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
