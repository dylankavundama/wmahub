<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint aux distributeurs
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'distributeur') {
    header('Location: ../../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../includes/subscription_check.php';
// Vérifier l'abonnement
if (!hasActiveSubscription($_SESSION['user_id'])) {
    header('Location: ../../auth/subscription.php');
    exit;
}

$db = getDBConnection();
$userId = $_SESSION['user_id'];

// Récupérer les infos du distributeur
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$success = '';
$error = '';
$success = isset($_GET['success']);

// Gérer l'ajout d'un artiste via AJAX ou POST
if (isset($_POST['add_artist_ajax'])) {
    $name = trim($_POST['artist_name'] ?? '');
    if ($name) {
        $stmt = $db->prepare("INSERT INTO distributor_artists (distributor_id, name) VALUES (?, ?)");
        $stmt->execute([$userId, $name]);
        $newId = $db->lastInsertId();
        echo json_encode(['success' => true, 'id' => $newId, 'name' => $name]);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Nom invalide']);
    exit;
}

// Gérer la soumission du projet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_project'])) {
    try {
        $audio_file = '';
        $cover_file = '';
        $upload_dir = __DIR__ . '/../artiste/uploads/'; // On utilise le même dossier d'uploads pour simplifier l'admin
        
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] === UPLOAD_ERR_OK) {
            $name = time() . '_audio_' . preg_replace("/[^a-zA-Z0-9._-]/", "_", $_FILES['audio_file']['name']);
            if (move_uploaded_file($_FILES['audio_file']['tmp_name'], $upload_dir . $name)) $audio_file = $name;
        }
        
        if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] === UPLOAD_ERR_OK) {
            $name = time() . '_cover_' . preg_replace("/[^a-zA-Z0-9._-]/", "_", $_FILES['cover_file']['name']);
            if (move_uploaded_file($_FILES['cover_file']['tmp_name'], $upload_dir . $name)) $cover_file = $name;
        }

        $artist_id = $_POST['artist_id'] ?? '';
        $artist_name = '';
        
        if ($artist_id) {
            $stmt = $db->prepare("SELECT name FROM distributor_artists WHERE id = ? AND distributor_id = ?");
            $stmt->execute([$artist_id, $userId]);
            $artist_name = $stmt->fetchColumn();
        }

        $stmt = $db->prepare("INSERT INTO projects 
            (user_id, title, artist_name, type, genre, date_sortie, details, phone, city, languages, provided_files, promo_pack, authorization, audio_file, cover_file) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $userId,
            $_POST['titre_projet'] ?? '',
            $artist_name ?: ($_POST['nom_artiste_manuel'] ?? ''),
            $_POST['type_projet'] ?? 'Single',
            $_POST['genre'] ?? '',
            $_POST['date_sortie'] ?? date('Y-m-d'),
            $_POST['details_morceaux'] ?? '',
            $_POST['telephone'] ?? '',
            $_POST['ville'] ?? '',
            $_POST['langues'] ?? '',
            isset($_POST['fichiers']) ? implode(', ', $_POST['fichiers']) : '',
            $_POST['pack_promo'] ?? 'Aucun',
            (($_POST['autorisation'] ?? 'Non') === 'Oui' ? 1 : 0),
            $audio_file,
            $cover_file
        ]);

        $projectId = $db->lastInsertId();
        
        // Notifications
        $admins = $db->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll();
        foreach ($admins as $admin) {
            createNotification($admin['id'], 'new_project', "Nouveau projet Distributeur : " . $_POST['titre_projet'], $projectId);
        }
        createNotification($userId, 'project_update', "Votre projet '" . $_POST['titre_projet'] . "' a été soumis avec succès.", $projectId);

        // Notifier l'équipe admin par email
        require_once __DIR__ . '/../../includes/mailer.php';
        $stmt_dist = $db->prepare("SELECT name, distributor_name FROM users WHERE id = ?");
        $stmt_dist->execute([$userId]);
        $distributor = $stmt_dist->fetch();
        $distName = $distributor['distributor_name'] ?: $distributor['name'];
        
        notifyAdmin('project', 'Nouveau Projet Distributeur', [
            'Distributeur' => $distName,
            'Projet' => $_POST['titre_projet'] ?? 'Sans titre',
            'Type' => $_POST['type_projet'] ?? 'Single',
            'Genre' => $_POST['genre'] ?? 'Non spécifié',
            'Date de sortie' => date('d/m/Y', strtotime($_POST['date_sortie'] ?? 'now'))
        ], 'https://wmahub.com/dashboards/admin/index.php?search=' . urlencode($_POST['titre_projet']));

        header('Location: submit.php?success=1');
        exit;
    } catch (Exception $e) {
        $error = "Erreur : " . $e->getMessage();
    }
}

// Récupérer les artistes du distributeur
$artists = $db->prepare("SELECT * FROM distributor_artists WHERE distributor_id = ? ORDER BY name ASC");
$artists->execute([$userId]);
$myArtists = $artists->fetchAll();

$pageTitle = 'Distribuer un Projet - WMA Hub';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/jpeg" href="../../asset/placeholder.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; min-height: 100vh; overflow-x: hidden; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 1.5rem; padding: 2rem; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; padding: 2rem 1.5rem; display: flex; flex-direction: column; }
        .main-content { margin-left: 280px; padding: 2rem; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; transform: translateX(5px); }
        .form-input { width: 100%; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 1rem; padding: 1rem; color: #fff; outline: none; transition: all 0.3s ease; }
        .form-input:focus { border-color: #ff6600; box-shadow: 0 0 15px rgba(255, 102, 0, 0.2); }
        .submit-btn { background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%); color: #fff; padding: 1.25rem; border-radius: 1.25rem; font-weight: 700; width: 100%; transition: all 0.3s ease; box-shadow: 0 10px 20px -5px rgba(255, 102, 0, 0.4); text-transform: uppercase; cursor: pointer; }
        .submit-btn:hover { transform: scale(1.02); filter: brightness(1.1); }
        #modal { position: fixed; inset: 0; background: rgba(0,0,0,0.8); display: none; align-items: center; justify-content: center; z-index: 1000; }
        #modal.active { display: flex; }
    </style>
</head>
<body>
    <div class="bg-glow"></div>

    <aside class="sidebar">
        <div class="flex items-center gap-4 mb-12 px-2">
            <img src="../../asset/trans.png" alt="Logo" class="h-10">
            <div>
                <h1 class="text-xl font-black bg-gradient-to-r from-orange-500 to-orange-300 bg-clip-text text-transparent tracking-tighter leading-tight">WMA HUB</h1>
                <p class="text-[8px] text-gray-500 font-bold uppercase tracking-[1px] -mt-1 flex items-center gap-1">
                    Distributeur
                    <?php if ($user['is_certified']): ?>
                        <i class="fas fa-check-decagram text-cyan-400 text-[10px]"></i>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <nav class="flex-1">
            <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>"><i class="fas fa-th-large"></i> Vue d'ensemble</a>
            <a href="artists.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'artists.php' ? 'active' : '' ?>"><i class="fas fa-users"></i> Mes Artistes</a>
            <a href="catalogue.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'catalogue.php' ? 'active' : '' ?>"><i class="fas fa-compact-disc"></i> Mon Catalogue</a>
            <a href="distributed_projects.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'distributed_projects.php' ? 'active' : '' ?>"><i class="fas fa-check-circle"></i> Projets Distribués</a>
            <a href="submit.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'submit.php' ? 'active' : '' ?>"><i class="fas fa-upload"></i> Distribuer</a>
            <a href="service_card.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'service_card.php' ? 'active' : '' ?>"><i class="fas fa-id-card"></i> Ma Carte Service</a>
            <a href="royalties.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'royalties.php' ? 'active' : '' ?>"><i class="fas fa-wallet"></i> Royalties</a>
            <a href="profile.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>"><i class="fas fa-user-circle"></i> Mon Profil</a>
        </nav>
        <div class="mt-auto pt-6 border-t border-white/5">
            <a href="../../auth/logout.php" class="nav-link !text-red-500"><i class="fas fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="mb-12">
            <h2 class="text-4xl font-black tracking-tighter">Distribuer un <span class="text-orange-500">Projet</span></h2>
            <p class="text-gray-400 mt-2">Envoyez la musique de vos artistes sur les plateformes mondiales.</p>
        </header>

        <?php if ($success): ?>
            <div class="glass-card border-green-500/50 bg-green-500/10 mb-8 flex items-center gap-4">
                <i class="fas fa-check-circle text-3xl text-green-500"></i>
                <div>
                    <h3 class="font-bold text-lg">Projet soumis avec succès !</h3>
                    <p class="text-sm text-gray-400">Le projet est maintenant en attente de validation par l'équipe WMA.</p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="glass-card border-red-500/50 bg-red-500/10 mb-8 flex items-center gap-4">
                <i class="fas fa-exclamation-triangle text-3xl text-red-500"></i>
                <p><?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-12 pb-24">
            <input type="hidden" name="submit_project" value="1">

            <!-- Section 1 : L'Artiste -->
            <section class="glass-card">
                <h3 class="text-xl font-bold mb-6 flex items-center gap-3 text-orange-500"><i class="fas fa-user-circle"></i> 1. Choisir l'Artiste</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-4">
                        <label class="text-sm font-bold text-gray-400 uppercase tracking-widest">Sélectionner un artiste existant</label>
                        <select name="artist_id" id="artistSelect" class="form-input">
                            <option value="">-- Choisissez un artiste --</option>
                            <?php foreach ($myArtists as $a): ?>
                                <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex flex-col justify-end">
                        <p class="text-sm text-gray-500 mb-4 italic">L'artiste ne figure pas dans la liste ?</p>
                        <button type="button" onclick="openModal()" class="px-6 py-4 bg-white/5 border border-white/10 rounded-xl hover:bg-orange-500/10 hover:border-orange-500/50 transition-all font-bold text-sm">
                            <i class="fas fa-plus mr-2"></i> Ajouter un nouvel artiste
                        </button>
                    </div>
                </div>
            </section>

            <!-- Section 2 : Infos Projet -->
            <section class="glass-card">
                <h3 class="text-xl font-bold mb-6 flex items-center gap-3 text-orange-500"><i class="fas fa-compact-disc"></i> 2. Informations du Projet</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-gray-400 uppercase tracking-widest pl-2">Titre du Projet</label>
                        <input type="text" name="titre_projet" required class="form-input" placeholder="Ex: Summer Vibes">
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-gray-400 uppercase tracking-widest pl-2">Type</label>
                        <select name="type_projet" class="form-input">
                            <option value="Single">Single</option>
                            <option value="EP">EP (Extended Play)</option>
                            <option value="Album">Album</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-gray-400 uppercase tracking-widest pl-2">Genre Musical</label>
                        <input type="text" name="genre" required class="form-input" placeholder="Ex: Afrobeats, Rumba...">
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-gray-400 uppercase tracking-widest pl-2">Date de sortie prévue</label>
                        <input type="date" name="date_sortie" class="form-input" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
            </section>

            <!-- Section 3 : Assets -->
            <section class="glass-card">
                <h3 class="text-xl font-bold mb-6 flex items-center gap-3 text-orange-500"><i class="fas fa-cloud-upload-alt"></i> 3. Audio & Pochette</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-gray-400 uppercase tracking-widest pl-2">Fichier Audio (MP3/WAV)</label>
                        <input type="file" name="audio_file" accept=".mp3,.wav" class="form-input">
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-bold text-gray-400 uppercase tracking-widest pl-2">Pochette (JPG/PNG)</label>
                        <input type="file" name="cover_file" accept="image/*" class="form-input">
                    </div>
                </div>
            </section>

            <button type="submit" class="submit-btn group py-6 text-xl">
                Lancer la distribution <i class="fas fa-paper-plane ml-3 group-hover:translate-x-2 transition-transform"></i>
            </button>
        </form>
    </main>

    <!-- Modal Ajout Artiste -->
    <div id="modal">
        <div class="glass-card w-full max-w-md mx-4 p-8 relative">
            <button onclick="closeModal()" class="absolute right-6 top-6 text-gray-500 hover:text-white"><i class="fas fa-times"></i></button>
            <h3 class="text-2xl font-black mb-6">Nouvel Artiste</h3>
            <div class="space-y-6">
                <div class="space-y-2">
                    <label class="text-xs font-bold text-gray-500 uppercase">Nom de l'Artiste</label>
                    <input type="text" id="newArtistName" class="form-input" placeholder="Ex: Young King">
                </div>
                <button type="button" onclick="saveNewArtist()" class="submit-btn py-4">
                    Enregistrer l'artiste
                </button>
            </div>
        </div>
    </div>

    <script>
        function openModal() { document.getElementById('modal').classList.add('active'); }
        function closeModal() { document.getElementById('modal').classList.remove('active'); }

        async function saveNewArtist() {
            const name = document.getElementById('newArtistName').value;
            if (!name) return alert('Veuillez entrer un nom');

            const formData = new FormData();
            formData.append('add_artist_ajax', '1');
            formData.append('artist_name', name);

            try {
                const response = await fetch('submit.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    const select = document.getElementById('artistSelect');
                    const option = new Option(data.name, data.id);
                    select.add(option);
                    select.value = data.id;
                    closeModal();
                    document.getElementById('newArtistName').value = '';
                } else {
                    alert(data.message);
                }
            } catch (e) {
                alert('Une erreur est survenue');
            }
        }
    </script>
</body>
</html>
