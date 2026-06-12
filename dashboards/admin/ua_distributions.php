<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint à l'admin et superadmin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$db = getDBConnection();
$message = '';
$error = '';

if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}
if (isset($_SESSION['flash_error'])) {
    $error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

// Traitement des actions (Ajout, Modification, Suppression, Toggle Status)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_distribution'])) {
        $title = $_POST['title'] ?? '';
        $artist_id = !empty($_POST['artist_id']) ? (int)$_POST['artist_id'] : null;
        $type = $_POST['type'] ?? 'Single';
        $release_date = !empty($_POST['release_date']) ? $_POST['release_date'] : null;
        $link = $_POST['link'] ?? '';
        
        // Obtenir le nom d'artiste textuel (soit choisi via dropdown, soit écrit manuellement)
        $artist_name = '';
        if ($artist_id) {
            $stmt_art = $db->prepare("SELECT name FROM ua_artists WHERE id = ?");
            $stmt_art->execute([$artist_id]);
            $art_row = $stmt_art->fetch();
            $artist_name = $art_row ? $art_row['name'] : '';
        } else {
            $artist_name = $_POST['artist_text'] ?? '';
        }

        $image_url = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'dist_' . time() . '.' . $ext;
            if (!is_dir('../../uploads')) {
                mkdir('../../uploads', 0777, true);
            }
            if (move_uploaded_file($_FILES['image']['tmp_name'], '../../uploads/' . $filename)) {
                $image_url = 'uploads/' . $filename;
            } else {
                $error = "Erreur lors de l'upload de l'image.";
            }
        } elseif (!empty($_POST['image_url'])) {
            $image_url = $_POST['image_url'];
        }

        if ($title && $artist_name && $image_url && !$error) {
            $stmt = $db->prepare("INSERT INTO distributions (title, artist, artist_id, type, release_date, image_url, link, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$title, $artist_name, $artist_id, $type, $release_date, $image_url, $link]);
            $_SESSION['flash_message'] = "Projet de distribution ajouté avec succès !";
            header('Location: ua_distributions.php');
            exit;
        } elseif (!$error) {
            $error = "Veuillez remplir le titre, l'artiste et fournir une image de couverture.";
        }
    } elseif (isset($_POST['edit_distribution'])) {
        $id = (int)$_POST['dist_id'];
        $title = $_POST['title'] ?? '';
        $artist_id = !empty($_POST['artist_id']) ? (int)$_POST['artist_id'] : null;
        $type = $_POST['type'] ?? 'Single';
        $release_date = !empty($_POST['release_date']) ? $_POST['release_date'] : null;
        $link = $_POST['link'] ?? '';
        $status = $_POST['status'] ?? 'active';

        $artist_name = '';
        if ($artist_id) {
            $stmt_art = $db->prepare("SELECT name FROM ua_artists WHERE id = ?");
            $stmt_art->execute([$artist_id]);
            $art_row = $stmt_art->fetch();
            $artist_name = $art_row ? $art_row['name'] : '';
        } else {
            $artist_name = $_POST['artist_text'] ?? '';
        }

        // Récupérer l'image actuelle
        $stmt_img = $db->prepare("SELECT image_url FROM distributions WHERE id = ?");
        $stmt_img->execute([$id]);
        $current = $stmt_img->fetch();
        $image_url = $current ? $current['image_url'] : '';

        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'dist_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], '../../uploads/' . $filename)) {
                $image_url = 'uploads/' . $filename;
            } else {
                $error = "Erreur lors de l'upload de la nouvelle image.";
            }
        } elseif (isset($_POST['image_url']) && !empty($_POST['image_url'])) {
            $image_url = $_POST['image_url'];
        }

        if ($title && $artist_name && $image_url && !$error) {
            $stmt = $db->prepare("UPDATE distributions SET title = ?, artist = ?, artist_id = ?, type = ?, release_date = ?, image_url = ?, link = ?, status = ? WHERE id = ?");
            $stmt->execute([$title, $artist_name, $artist_id, $type, $release_date, $image_url, $link, $status, $id]);
            $_SESSION['flash_message'] = "Projet de distribution mis à jour avec succès !";
            header('Location: ua_distributions.php');
            exit;
        } elseif (!$error) {
            $error = "Veuillez remplir le titre, l'artiste et fournir une image de couverture.";
        }
    } elseif (isset($_POST['delete_dist'])) {
        $id = (int)$_POST['dist_id'];
        $stmt = $db->prepare("DELETE FROM distributions WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['flash_message'] = "Distribution supprimée avec succès.";
        header('Location: ua_distributions.php');
        exit;
    } elseif (isset($_POST['toggle_status'])) {
        $id = (int)$_POST['dist_id'];
        $status = $_POST['status'];
        $stmt = $db->prepare("UPDATE distributions SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        $_SESSION['flash_message'] = "Statut mis à jour.";
        header('Location: ua_distributions.php');
        exit;
    }
}

// Récupérer les distributions avec le nom de l'artiste UA lié
$distributions = $db->query("SELECT d.*, a.name as ua_artist_name 
                             FROM distributions d 
                             LEFT JOIN ua_artists a ON d.artist_id = a.id 
                             ORDER BY d.release_date DESC, d.created_at DESC")->fetchAll();

// Récupérer la liste des artistes pour le dropdown
$artists = $db->query("SELECT id, name FROM ua_artists ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMA HUB (UA) - Gestion des Distributions</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; padding: 2rem 1.5rem; display: flex; flex-direction: column; }
        .main-content { margin-left: 280px; padding: 2rem; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; text-decoration: none; margin-bottom: 0.5rem; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 1.5rem; padding: 1.5rem; }
        .custom-input { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 0.75rem; padding: 0.75rem 1rem; color: white; width: 100%; outline: none; }
        .custom-input:focus { border-color: #ff6600; }
        .btn-primary { background: #ff6600; color: white; padding: 0.75rem 1.5rem; border-radius: 0.75rem; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary:hover { background: #e65c00; }
        select option { background: #1a1a2e; color: #fff; }
    </style>
</head>
<body>
    <div class="bg-glow"></div>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <header class="flex justify-between items-center mb-12">
            <div>
                <h2 class="text-4xl font-black tracking-tighter">Distributions <span class="text-orange-500">United Africa</span></h2>
                <p class="text-gray-400 mt-2">Gérez et associez les projets musicaux distribués officiellement.</p>
            </div>
            <button onclick="showAddModal()" class="btn-primary">
                <i class="fas fa-plus"></i> Nouveau Projet
            </button>
        </header>

        <?php if ($message): ?>
            <div class="mb-8 p-4 bg-green-500/10 border border-green-500/20 text-green-500 rounded-xl flex items-center gap-3">
                <i class="fas fa-check-circle"></i> <span><?= $message ?></span>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="mb-8 p-4 bg-red-500/10 border border-red-500/20 text-red-500 rounded-xl flex items-center gap-3">
                <i class="fas fa-exclamation-circle"></i> <span><?= $error ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (empty($distributions)): ?>
                <div class="glass-card col-span-full text-center py-12 text-gray-500">
                    <i class="fas fa-music text-4xl mb-4 block"></i>
                    Aucun projet de distribution enregistré pour le moment.
                </div>
            <?php else: ?>
                <?php foreach ($distributions as $dist): ?>
                    <div class="glass-card flex flex-col justify-between">
                        <div>
                            <div class="w-full h-48 bg-gray-900/50 rounded-xl overflow-hidden border border-white/5 mb-4 relative">
                                <img src="../../<?= htmlspecialchars($dist['image_url']) ?>" alt="<?= htmlspecialchars($dist['title']) ?>" class="w-full h-full object-cover" onerror="this.src='../../asset/aspi.jpg';">
                                <span class="absolute top-3 right-3 bg-black/60 px-3 py-1 rounded-full text-[10px] font-bold text-orange-500 border border-orange-500/20 uppercase">
                                    <?= htmlspecialchars($dist['type']) ?>
                                </span>
                            </div>
                            <h3 class="font-bold text-lg leading-tight mb-1"><?= htmlspecialchars($dist['title']) ?></h3>
                            <p class="text-orange-500 text-sm font-semibold mb-2">
                                <?= htmlspecialchars($dist['ua_artist_name'] ?: $dist['artist']) ?>
                            </p>
                            <div class="flex items-center justify-between text-xs text-gray-400 mb-4">
                                <span>Sortie : <?= $dist['release_date'] ? date('d/m/Y', strtotime($dist['release_date'])) : 'Non spécifiée' ?></span>
                                <span class="flex items-center gap-1">
                                    <span class="w-2 h-2 rounded-full <?= $dist['status'] === 'active' ? 'bg-green-500' : 'bg-gray-500' ?>"></span>
                                    <?= $dist['status'] === 'active' ? 'Actif' : 'Inactif' ?>
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2 pt-4 border-t border-white/5">
                            <a href="<?= htmlspecialchars($dist['link']) ?>" target="_blank" class="text-xs text-gray-400 hover:text-white underline truncate flex items-center gap-1">
                                <i class="fab fa-spotify"></i> Lien de stream
                            </a>
                            <div class="flex justify-between items-center mt-2">
                                <button onclick="showEditModal(<?= htmlspecialchars(json_encode($dist)) ?>)" class="text-xs text-blue-400 hover:text-blue-300 flex items-center gap-1">
                                    <i class="fas fa-edit"></i> Modifier
                                </button>
                                <div class="flex gap-3">
                                    <form method="POST">
                                        <input type="hidden" name="dist_id" value="<?= $dist['id'] ?>">
                                        <input type="hidden" name="status" value="<?= $dist['status'] === 'active' ? 'inactive' : 'active' ?>">
                                        <button type="submit" name="toggle_status" class="text-xs <?= $dist['status'] === 'active' ? 'text-amber-500' : 'text-green-500' ?> hover:underline">
                                            <?= $dist['status'] === 'active' ? 'Désactiver' : 'Activer' ?>
                                        </button>
                                    </form>
                                    <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette distribution ?')">
                                        <input type="hidden" name="dist_id" value="<?= $dist['id'] ?>">
                                        <button type="submit" name="delete_dist" class="text-xs text-red-500 hover:text-red-400 flex items-center gap-1">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Add/Edit Modal -->
        <div id="distModal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-[1000] backdrop-blur-sm">
            <div class="glass-card w-full max-w-md">
                <h3 id="modalTitle" class="text-2xl font-black mb-6">Ajouter une <span class="text-orange-500">Distribution UA</span></h3>
                <form id="modalForm" method="POST" enctype="multipart/form-data" class="flex flex-col gap-4">
                    <input type="hidden" name="dist_id" id="dist_id">
                    
                    <div>
                        <label class="text-xs text-gray-400 mb-1 block">Titre du projet</label>
                        <input type="text" name="title" id="title" required placeholder="Ex: Album, Single, EP Title" class="custom-input">
                    </div>

                    <div>
                        <label class="text-xs text-gray-400 mb-1 block">Associer à un artiste UA</label>
                        <select name="artist_id" id="artist_id" class="custom-input cursor-pointer" onchange="toggleArtistInput(this.value)">
                            <option value="">-- Écrire le nom manuellement --</option>
                            <?php foreach ($artists as $a): ?>
                                <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="artist_text_container">
                        <label class="text-xs text-gray-400 mb-1 block">Nom de l'artiste (Saisie manuelle)</label>
                        <input type="text" name="artist_text" id="artist_text" placeholder="Ex: Bob Marley" class="custom-input">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs text-gray-400 mb-1 block">Type de projet</label>
                            <select name="type" id="type" class="custom-input cursor-pointer">
                                <option value="Single">Single</option>
                                <option value="EP">EP</option>
                                <option value="Album">Album</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs text-gray-400 mb-1 block">Date de sortie</label>
                            <input type="date" name="release_date" id="release_date" class="custom-input">
                        </div>
                    </div>

                    <div>
                        <label class="text-xs text-gray-400 mb-1 block">Lien de streaming (Spotify/Audiomack/etc.)</label>
                        <input type="url" name="link" id="link" required placeholder="https://..." class="custom-input">
                    </div>

                    <div id="status_container" class="hidden">
                        <label class="text-xs text-gray-400 mb-1 block">Statut du projet</label>
                        <select name="status" id="status" class="custom-input cursor-pointer">
                            <option value="active">Actif</option>
                            <option value="inactive">Inactif</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-xs text-gray-400 mb-1 block">Image de couverture</label>
                        <input type="file" name="image" class="custom-input" accept="image/*">
                        <p class="text-[10px] text-gray-400 mt-1">OU URL de l'image existante :</p>
                        <input type="text" name="image_url" id="image_url" placeholder="https://..." class="custom-input mt-1">
                    </div>

                    <div class="flex gap-2 mt-4">
                        <button type="button" onclick="hideModal()" class="bg-white/10 px-6 py-2 rounded-xl flex-1 hover:bg-white/15">Annuler</button>
                        <button type="submit" name="add_distribution" id="submitBtn" class="btn-primary flex-1 justify-center">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        const modal = document.getElementById('distModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalForm = document.getElementById('modalForm');
        const submitBtn = document.getElementById('submitBtn');
        const distIdInput = document.getElementById('dist_id');
        const titleInput = document.getElementById('title');
        const artistIdSelect = document.getElementById('artist_id');
        const artistTextInput = document.getElementById('artist_text');
        const artistTextContainer = document.getElementById('artist_text_container');
        const typeSelect = document.getElementById('type');
        const releaseDateInput = document.getElementById('release_date');
        const linkInput = document.getElementById('link');
        const statusSelect = document.getElementById('status');
        const statusContainer = document.getElementById('status_container');
        const imageUrlInput = document.getElementById('image_url');

        function toggleArtistInput(val) {
            if (val) {
                artistTextContainer.classList.add('hidden');
                artistTextInput.required = false;
            } else {
                artistTextContainer.classList.remove('hidden');
                artistTextInput.required = true;
            }
        }

        function showAddModal() {
            modalTitle.innerHTML = 'Ajouter une <span class="text-orange-500">Distribution UA</span>';
            modalForm.reset();
            distIdInput.value = '';
            toggleArtistInput('');
            statusContainer.classList.add('hidden');
            submitBtn.name = 'add_distribution';
            submitBtn.innerText = 'Ajouter';
            modal.classList.remove('hidden');
        }

        function showEditModal(dist) {
            modalTitle.innerHTML = 'Modifier la <span class="text-orange-500">Distribution UA</span>';
            distIdInput.value = dist.id;
            titleInput.value = dist.title;
            artistIdSelect.value = dist.artist_id || '';
            artistTextInput.value = dist.artist || '';
            toggleArtistInput(dist.artist_id);
            typeSelect.value = dist.type || 'Single';
            releaseDateInput.value = dist.release_date || '';
            linkInput.value = dist.link || '';
            statusSelect.value = dist.status || 'active';
            statusContainer.classList.remove('hidden');
            imageUrlInput.value = dist.image_url || '';
            submitBtn.name = 'edit_distribution';
            submitBtn.innerText = 'Enregistrer';
            modal.classList.remove('hidden');
        }

        function hideModal() {
            modal.classList.add('hidden');
        }
    </script>
</body>
</html>
