<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint aux artistes
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'artiste') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = getDBConnection();

// Récupérer toutes les notes de l'artiste
$stmt = $db->prepare("SELECT * FROM artist_notes WHERE user_id = ? ORDER BY updated_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$notes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Notes - WMA HUB</title>
    <link rel="icon" type="image/png" href="../../asset/icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; min-height: 100vh; overflow-x: hidden; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 100% 100%, #1e1b4b 0%, #0a0a0c 100%); z-index: -1; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; display: flex; flex-direction: column; padding: 2rem 1.5rem; }
        .main-content { margin-left: 280px; padding: 2rem; min-height: 100vh; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.5); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; transform: translateX(5px); }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 1.5rem; padding: 1.5rem; transition: all 0.3s ease; }
        .glass-card:hover { transform: translateY(-5px); background: rgba(255, 255, 255, 0.05); border-color: rgba(255, 102, 0, 0.2); }
        .btn-primary { background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%); color: #fff; font-weight: bold; padding: 1rem 2rem; border-radius: 1rem; transition: all 0.3s ease; display: inline-flex; items-center; gap: 0.5rem; }
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
            <a href="notifications.php" class="nav-link"><i class="fas fa-bell"></i>Notifications</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="mb-12 flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div>
                <a href="services.php" class="text-orange-500/60 hover:text-orange-500 font-bold text-xs uppercase tracking-widest transition-all mb-4 inline-block"><i class="fas fa-arrow-left mr-2"></i>Retour aux services</a>
                <h2 class="text-4xl font-black mb-2">Bloc <span class="text-orange-500">Note</span></h2>
                <p class="text-gray-400">Gérez vos lyrics, idées et inspirations en un seul endroit.</p>
            </div>
            <button onclick="createNewNote()" class="btn-primary">
                <i class="fas fa-plus"></i> Nouvelle Note
            </button>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($notes as $note): ?>
                <div class="glass-card flex flex-col">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-orange-500/10 flex items-center justify-center text-orange-500">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <button onclick="deleteNote(<?= $note['id'] ?>)" class="text-gray-600 hover:text-red-500 transition-colors p-2">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                    <h3 class="font-bold text-lg mb-2 truncate"><?= htmlspecialchars($note['title'] ?: 'Sans Titre') ?></h3>
                    <p class="text-gray-500 text-sm line-clamp-3 mb-6 flex-1">
                        <?= $note['content'] ? htmlspecialchars(substr($note['content'], 0, 150)) . (strlen($note['content']) > 150 ? '...' : '') : '<i>Aucun contenu...</i>' ?>
                    </p>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] uppercase font-black text-gray-700 tracking-widest">
                            <?= date('d/m/Y', strtotime($note['updated_at'])) ?>
                        </span>
                        <a href="note_editor.php?id=<?= $note['id'] ?>" class="text-orange-500 font-bold text-xs uppercase tracking-widest flex items-center gap-2 hover:gap-3 transition-all">
                            Modifier <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($notes)): ?>
                <div class="col-span-full py-20 text-center glass-card border-dashed">
                    <p class="text-gray-500 uppercase font-black tracking-widest text-xs mb-6">Vous n'avez pas encore de notes</p>
                    <button onclick="createNewNote()" class="text-orange-500 font-bold hover:underline">Créer ma première note</button>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        async function createNewNote() {
            try {
                const response = await fetch('../../api/gemini.php?action=create_note', { method: 'POST' });
                const data = await response.json();
                if (data.success) {
                    window.location.href = 'note_editor.php?id=' + data.id;
                }
            } catch (err) {
                alert('Erreur lors de la création de la note.');
            }
        }

        async function deleteNote(id) {
            if (!confirm('Voulez-vous vraiment supprimer cette note ?')) return;

            try {
                const response = await fetch('../../api/gemini.php?action=delete_note', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                }
            } catch (err) {
                alert('Erreur lors de la suppression.');
            }
        }
    </script>
</body>
</html>
