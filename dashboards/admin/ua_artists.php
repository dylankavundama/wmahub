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

// Actions CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_artist'])) {
        $name = $_POST['name'] ?? '';
        $onerpm_link = $_POST['onerpm_link'] ?? '';
        $bio = $_POST['bio'] ?? '';
        $is_ua = isset($_POST['is_ua']) ? 1 : 0;
        
        $photo_url = '';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = 'art_' . time() . '.' . $ext;
            $upload_path = '../../uploads/' . $filename;
            if (!is_dir('../../uploads')) {
                mkdir('../../uploads', 0777, true);
            }
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                $photo_url = 'uploads/' . $filename;
            } else {
                $error = "Erreur lors de l'upload de la photo.";
            }
        } elseif (!empty($_POST['photo_url'])) {
            $photo_url = $_POST['photo_url'];
        }

        if ($name && !$error) {
            $stmt = $db->prepare("INSERT INTO ua_artists (name, photo_url, onerpm_link, bio, is_ua) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $photo_url, $onerpm_link, $bio, $is_ua]);
            $_SESSION['flash_message'] = "Artiste UA ajouté avec succès !";
            header('Location: ua_artists.php');
            exit;
        } elseif (!$error) {
            $error = "Le nom de l'artiste est obligatoire.";
        }
    } elseif (isset($_POST['edit_artist'])) {
        $id = (int)$_POST['artist_id'];
        $name = $_POST['name'] ?? '';
        $onerpm_link = $_POST['onerpm_link'] ?? '';
        $bio = $_POST['bio'] ?? '';
        $is_ua = isset($_POST['is_ua']) ? 1 : 0;
        
        // Récupérer la photo actuelle
        $stmt = $db->prepare("SELECT photo_url FROM ua_artists WHERE id = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetch();
        $photo_url = $current ? $current['photo_url'] : '';

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = 'art_' . time() . '.' . $ext;
            $upload_path = '../../uploads/' . $filename;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                $photo_url = 'uploads/' . $filename;
            } else {
                $error = "Erreur lors de l'upload de la nouvelle photo.";
            }
        } elseif (isset($_POST['photo_url']) && !empty($_POST['photo_url'])) {
            $photo_url = $_POST['photo_url'];
        }

        if ($name && !$error) {
            $stmt = $db->prepare("UPDATE ua_artists SET name = ?, photo_url = ?, onerpm_link = ?, bio = ?, is_ua = ? WHERE id = ?");
            $stmt->execute([$name, $photo_url, $onerpm_link, $bio, $is_ua, $id]);
            $_SESSION['flash_message'] = "Artiste UA modifié avec succès !";
            header('Location: ua_artists.php');
            exit;
        } elseif (!$error) {
            $error = "Le nom de l'artiste est obligatoire.";
        }
    } elseif (isset($_POST['delete_artist'])) {
        $id = (int)$_POST['artist_id'];
        
        // Dissocier les projets dans la table distributions
        $stmt_proj = $db->prepare("UPDATE distributions SET artist_id = NULL WHERE artist_id = ?");
        $stmt_proj->execute([$id]);

        // Supprimer l'artiste
        $stmt = $db->prepare("DELETE FROM ua_artists WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['flash_message'] = "Artiste supprimé avec succès et ses projets ont été dissociés.";
        header('Location: ua_artists.php');
        exit;
    }
}

// Récupérer les artistes de WMA United Africa avec photo d'utilisateur jointe
$artists = $db->query("SELECT a.*, u.photo_url as user_photo, (SELECT COUNT(*) FROM distributions WHERE artist_id = a.id) as project_count 
                       FROM ua_artists a 
                       LEFT JOIN users u ON a.user_id = u.id
                       ORDER BY a.name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMA HUB (UA) - Gestion des Artistes</title>
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
    </style>
</head>
<body>
    <div class="bg-glow"></div>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <header class="flex justify-between items-center mb-12">
            <div>
                <h2 class="text-4xl font-black tracking-tighter">Artistes <span class="text-orange-500">United Africa</span></h2>
                <p class="text-gray-400 mt-2">Gérez le catalogue des artistes officiels de WMA United Africa.</p>
            </div>
            <button onclick="showAddModal()" class="btn-primary">
                <i class="fas fa-user-plus"></i> Nouvel Artiste
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
            <?php if (empty($artists)): ?>
                <div class="glass-card col-span-full text-center py-12 text-gray-500">
                    <i class="fas fa-users text-4xl mb-4 block"></i>
                    Aucun artiste UA enregistré pour le moment.
                </div>
            <?php else: ?>
                <?php foreach ($artists as $art): ?>
                    <div class="glass-card flex flex-col justify-between">
                        <div>
                            <div class="w-full h-48 bg-gray-900/50 rounded-xl overflow-hidden border border-white/5 mb-4 relative">
                                <?php 
                                $photo = !empty($art['photo_url']) ? $art['photo_url'] : ($art['user_photo'] ?? '');
                                if (!empty($photo)): 
                                    $photoSrc = filter_var($photo, FILTER_VALIDATE_URL) ? $photo : '../../' . ltrim($photo, '/');
                                ?>
                                    <img src="<?= htmlspecialchars($photoSrc) ?>" alt="<?= htmlspecialchars($art['name']) ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-gray-600 text-5xl">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                                <span class="absolute top-3 right-3 bg-black/60 px-3 py-1 rounded-full text-xs font-bold text-orange-500 border border-orange-500/20">
                                    <?= $art['project_count'] ?> Sortie<?= $art['project_count'] > 1 ? 's' : '' ?>
                                </span>
                            </div>
                             <h3 class="font-bold text-xl mb-1 flex items-center gap-2">
                                 <?= htmlspecialchars($art['name']) ?>
                                 <?php if ($art['is_ua']): ?>
                                     <span class="text-[10px] bg-orange-500/20 text-orange-500 px-2 py-0.5 rounded-full font-medium">Artiste UA</span>
                                 <?php endif; ?>
                             </h3>
                            <p class="text-xs text-gray-400 line-clamp-3 mb-4"><?= htmlspecialchars($art['bio'] ?: "Aucune biographie fournie.") ?></p>
                        </div>
                        <div class="flex flex-col gap-2 pt-4 border-t border-white/5">
                            <?php if (!empty($art['onerpm_link'])): ?>
                                <a href="<?= htmlspecialchars($art['onerpm_link']) ?>" target="_blank" class="text-xs text-orange-500 hover:underline flex items-center gap-1">
                                    <i class="fas fa-external-link-alt"></i> Lien ONErpm
                                </a>
                            <?php else: ?>
                                <span class="text-xs text-gray-600 italic">Aucun lien ONErpm</span>
                            <?php endif; ?>
                            <div class="flex justify-between items-center mt-2">
                                <button onclick="showEditModal(<?= htmlspecialchars(json_encode($art)) ?>)" class="text-xs text-blue-400 hover:text-blue-300 flex items-center gap-1">
                                    <i class="fas fa-edit"></i> Modifier
                                </button>
                                <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet artiste ? Tous ses projets seront dissociés.')">
                                    <input type="hidden" name="artist_id" value="<?= $art['id'] ?>">
                                    <button type="submit" name="delete_artist" class="text-xs text-red-500 hover:text-red-400 flex items-center gap-1">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Add/Edit Modal -->
        <div id="artistModal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-[1000] backdrop-blur-sm">
            <div class="glass-card w-full max-w-md">
                <h3 id="modalTitle" class="text-2xl font-black mb-6">Ajouter un <span class="text-orange-500">Artiste</span></h3>
                <form id="modalForm" method="POST" enctype="multipart/form-data" class="flex flex-col gap-4">
                    <input type="hidden" name="artist_id" id="artist_id">
                    
                    <div>
                        <label class="text-xs text-gray-400 mb-1 block">Nom de l'artiste</label>
                        <input type="text" name="name" id="name" required placeholder="Nom de scène" class="custom-input">
                    </div>

                    <div>
                        <label class="text-xs text-gray-400 mb-1 block">Lien ONErpm</label>
                        <input type="url" name="onerpm_link" id="onerpm_link" placeholder="https://onerpm.link/..." class="custom-input">
                    </div>

                    <div>
                        <label class="text-xs text-gray-400 mb-1 block">Biographie</label>
                        <textarea name="bio" id="bio" rows="3" placeholder="Présentation de l'artiste..." class="custom-input resize-none"></textarea>
                    </div>

                    <div>
                        <label class="text-xs text-gray-400 mb-1 block">Photo de profil</label>
                        <input type="file" name="photo" class="custom-input" accept="image/*">
                        <p class="text-[10px] text-gray-400 mt-1">OU URL de l'image existante :</p>
                        <input type="text" name="photo_url" id="photo_url" placeholder="https://..." class="custom-input mt-1">
                    </div>

                    <div class="flex items-center gap-2 mt-2">
                        <input type="checkbox" name="is_ua" id="is_ua" value="1" class="rounded border-white/10 bg-white/5 text-orange-500 focus:ring-orange-500 w-4 h-4 cursor-pointer">
                        <label for="is_ua" class="text-xs text-gray-400 cursor-pointer select-none">Artiste UA</label>
                    </div>

                    <div class="flex gap-2 mt-4">
                        <button type="button" onclick="hideModal()" class="bg-white/10 px-6 py-2 rounded-xl flex-1 hover:bg-white/15">Annuler</button>
                        <button type="submit" name="add_artist" id="submitBtn" class="btn-primary flex-1 justify-center">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        const modal = document.getElementById('artistModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalForm = document.getElementById('modalForm');
        const submitBtn = document.getElementById('submitBtn');
        const artistIdInput = document.getElementById('artist_id');
        const nameInput = document.getElementById('name');
        const linkInput = document.getElementById('onerpm_link');
        const bioInput = document.getElementById('bio');
        const photoUrlInput = document.getElementById('photo_url');
        const isUaInput = document.getElementById('is_ua');

        function showAddModal() {
            modalTitle.innerHTML = 'Ajouter un <span class="text-orange-500">Artiste UA</span>';
            modalForm.reset();
            artistIdInput.value = '';
            isUaInput.checked = false;
            submitBtn.name = 'add_artist';
            submitBtn.innerText = 'Ajouter';
            modal.classList.remove('hidden');
        }

        function showEditModal(artist) {
            modalTitle.innerHTML = 'Modifier l\'<span class="text-orange-500">Artiste UA</span>';
            artistIdInput.value = artist.id;
            nameInput.value = artist.name;
            linkInput.value = artist.onerpm_link || '';
            bioInput.value = artist.bio || '';
            photoUrlInput.value = artist.photo_url || '';
            isUaInput.checked = artist.is_ua == 1;
            submitBtn.name = 'edit_artist';
            submitBtn.innerText = 'Enregistrer';
            modal.classList.remove('hidden');
        }

        function hideModal() {
            modal.classList.add('hidden');
        }
    </script>
</body>
</html>
