<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint aux artistes
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'artiste') {
    header('Location: ../../auth/login.php');
    exit;
}

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
    <link rel="icon" type="image/png" href="../../asset/icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; min-height: 100vh; overflow: hidden; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 100% 100%, #1e1b4b 0%, #0a0a0c 100%); z-index: -1; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; display: flex; flex-direction: column; padding: 2rem 1.5rem; }
        .main-content { margin-left: 280px; padding: 2rem; height: 100vh; display: flex; flex-direction: column; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.5); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; }
        .editor-container { flex: 1; background: rgba(255, 255, 255, 0.02); border-radius: 2rem; border: 1px solid rgba(255, 255, 255, 0.05); overflow: hidden; display: flex; flex-direction: column; }
        textarea { flex: 1; background: transparent; border: none; color: #fff; padding: 3rem; font-size: 1.1rem; line-height: 1.8; outline: none; resize: none; width: 100%; font-family: 'Courier New', Courier, monospace; }
        .status-bar { padding: 1rem 3rem; background: rgba(255, 255, 255, 0.02); border-top: 1px solid rgba(255, 255, 255, 0.05); display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; color: #555; }
        .save-indicator { display: flex; items-center; gap: 0.5rem; }
        @media (max-width: 1024px) { .sidebar { display: none; } .main-content { margin-left: 0; } }
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
            <a href="catalogue.php" class="nav-link"><i class="fas fa-music"></i>Catalogue</a>
            <a href="stats.php" class="nav-link"><i class="fas fa-chart-line"></i>Stats</a>
            <a href="#" class="nav-link disabled opacity-50 cursor-not-allowed"><i class="fas fa-wallet"></i>Revenus</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="mb-8 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="notepad.php" class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-gray-500 hover:text-white transition-all"><i class="fas fa-arrow-left"></i></a>
                <div>
                    <input type="text" id="noteTitle" value="<?= htmlspecialchars($note['title']) ?>" class="bg-transparent border-none text-2xl font-black text-white p-0 focus:outline-none focus:ring-0">
                    <p class="text-[10px] text-gray-500 uppercase font-black tracking-widest mt-1">Note ID: #<?= $id ?> | Sauvegarde automatique</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <button onclick="downloadNote()" class="bg-white/5 hover:bg-white/10 text-white px-4 py-2 rounded-xl border border-white/10 text-xs font-bold uppercase transition-all">
                    <i class="fas fa-download mr-2"></i> Exporter (.txt)
                </button>
            </div>
        </header>

        <div class="editor-container">
            <textarea id="editor" placeholder="Commencez à écrire ici..."><?= htmlspecialchars($note['content']) ?></textarea>
            <div class="status-bar">
                <div class="save-indicator">
                    <i id="saveIcon" class="fas fa-check-circle text-green-500"></i>
                    <span id="saveText">Enregistré</span>
                </div>
                <div id="wordCount">0 mots</div>
            </div>
        </div>
    </main>

    <script>
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
    </script>
</body>
</html>
