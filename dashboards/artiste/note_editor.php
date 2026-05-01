<?php
require_once __DIR__ . '/auth_artist.php';

if (!isset($_GET['id'])) {
    header('Location: notepad.php');
    exit;
}

$db = getDBConnection();
$id = (int)$_GET['id'];

// Récupérer la note spécifique
$stmt = $db->prepare("SELECT * FROM artist_notes WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$note = $stmt->fetch();

if (!$note) {
    header('Location: notepad.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Édition Note - WMA HUB</title>
    <link rel="icon" type="image/png" href="/asset/icon.png"><link rel="apple-touch-icon" href="/asset/icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; margin: 0; min-height: 100vh; overflow-x: hidden; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .sidebar { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .sidebar.active { transform: translateX(0); }
        @media (max-width: 1024px) { .sidebar { transform: translateX(-100%); position: fixed; z-index: 50; height: 100vh; } }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 20px; transition: all 0.3s ease; }
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: #94a3b8; transition: all 0.3s ease; margin-bottom: 4px; }
        .nav-link:hover:not(.active) { background: rgba(255, 255, 255, 0.03); color: #fff; }
        .nav-link.active { background: linear-gradient(135deg, #ff6600 0%, #ff8c00 100%); color: #fff; box-shadow: 0 4px 15px rgba(255, 102, 0, 0.3); }
        .nav-link i { font-size: 1.1rem; }
        #wma-global-loader { position: fixed; inset: 0; background: #0a0a0c; z-index: 9999; display: flex; align-items: center; justify-content: center; transition: opacity 0.5s ease; }
        .loader-spin { width: 50px; height: 50px; border: 3px solid rgba(255, 102, 0, 0.1); border-top-color: #ff6600; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .editor-container { position: relative; background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 24px; overflow: hidden; height: calc(100vh - 200px); display: flex; flex-direction: column; }
        .editor-textarea { flex: 1; width: 100%; background: transparent; border: none; padding: 40px; color: #cbd5e1; font-size: 1.1rem; line-height: 1.8; resize: none; focus: outline-none; }
        .editor-textarea:focus { outline: none; }
        .status-bar { padding: 12px 24px; background: rgba(0, 0, 0, 0.2); border-top: 1px solid rgba(255, 255, 255, 0.05); display: flex; align-items: center; justify-content: space-between; font-size: 0.75rem; color: #64748b; }
        .save-indicator { display: flex; align-items: center; gap: 8px; }
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
            <header class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="flex items-center gap-4">
                    <a href="notepad.php" class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-gray-500 hover:text-white transition-all border border-white/5"><i class="fas fa-arrow-left"></i></a>
                    <div class="flex-1">
                        <input type="text" id="noteTitle" value="<?= htmlspecialchars($note['title']) ?>" class="bg-transparent border-none text-2xl lg:text-3xl font-black text-white p-0 focus:outline-none focus:ring-0 w-full lg:w-auto overflow-hidden truncate" placeholder="Titre de la note">
                        <p class="text-[10px] text-gray-500 uppercase font-black tracking-widest mt-1">Note ID: #<?= $id ?> | Sauvegarde automatique</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <button onclick="downloadNote()" class="bg-white/5 hover:bg-white/10 text-white px-4 py-2 rounded-xl border border-white/10 text-[10px] font-bold uppercase transition-all flex items-center gap-2">
                        <i class="fas fa-download text-orange-500"></i> Exporter (.txt)
                    </button>
                </div>
            </header>

            <div class="editor-container">
                <textarea id="editor" class="editor-textarea" placeholder="Commencez à écrire ici..."><?= htmlspecialchars($note['content']) ?></textarea>
                <div class="status-bar">
                    <div class="save-indicator">
                        <i id="saveIcon" class="fas fa-check-circle text-green-500"></i>
                        <span id="saveText" class="font-bold uppercase tracking-widest">Enregistré</span>
                    </div>
                    <div id="wordCount" class="font-bold uppercase tracking-widest text-orange-500/60">0 mots</div>
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

        const editor = document.getElementById('editor');
        const noteTitle = document.getElementById('noteTitle');
        const saveIcon = document.getElementById('saveIcon');
        const saveText = document.getElementById('saveText');
        const wordCount = document.getElementById('wordCount');
        const noteId = <?= $id ?>;
        let saveTimeout;

        function updateWordCount() {
            const text = editor.value.trim();
            const words = text ? text.split(/\s+/).length : 0;
            wordCount.textContent = `${words} mot${words > 1 ? 's' : ''}`;
        }

        async function saveNote() {
            saveIcon.className = 'fas fa-spinner fa-spin text-orange-500';
            saveText.textContent = 'Enregistrement...';

            try {
                const response = await fetch('../../api/gemini.php?action=save_note', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: noteId,
                        title: noteTitle.value,
                        content: editor.value
                    })
                });
                const data = await response.json();
                if (data.success) {
                    saveIcon.className = 'fas fa-check-circle text-green-500';
                    saveText.textContent = 'Enregistré';
                }
            } catch (err) {
                saveIcon.className = 'fas fa-exclamation-circle text-red-500';
                saveText.textContent = 'Erreur de sauvegarde';
            }
        }

        editor.addEventListener('input', () => {
            updateWordCount();
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(saveNote, 1000);
        });

        noteTitle.addEventListener('input', () => {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(saveNote, 1000);
        });

        function downloadNote() {
            const blob = new Blob([editor.value], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${noteTitle.value || 'note'}.txt`;
            a.click();
        }

        updateWordCount();

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
