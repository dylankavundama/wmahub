<?php
require_once __DIR__ . '/auth_artist.php';

$success = isset($_GET['success']);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check for post_max_size violation
    if (empty($_POST) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $max_size = ini_get('post_max_size');
        $error = "Le fichier est trop volumineux. La limite maximale autorisée par le serveur est de $max_size.";
    } else {
        $is_ajax = isset($_POST['is_ajax']);
        $required_fields = [
            'nom_complet' => 'Nom et Prénom',
            'nom_artiste' => "Nom d'Artiste",
            'email' => 'Adresse E-mail',
            'telephone' => 'WhatsApp (Numéro)',
            'titre_projet' => 'Titre du Projet',
            'details_morceaux' => 'Détails / Liste des titres',
            'type_projet' => 'Type de Projet',
            'genre' => 'Genre Musical',
            'date_sortie' => 'Date de Sortie Souhaitée',
            'ville' => 'Ville',
            'langues' => 'Langues chantées'
        ];

        foreach ($required_fields as $field => $label) {
            if (empty($_POST[$field])) {
                $error = "Le champ '$label' est obligatoire.";
                break;
            }
        }

        if (!$error && empty($_FILES['audio_file']['name'])) {
            $error = "Le Fichier Audio est obligatoire.";
        }
        if (!$error && empty($_FILES['cover_file']['name'])) {
            $error = "La Pochette est obligatoire.";
        }
        if (!$error && empty($_POST['autorisation'])) {
            $error = "Vous devez accepter l'autorisation de distribution.";
        }

        if (!$error) {
            $db = getDBConnection();
            try {
            // Handle file uploads
            $audio_file = '';
            $cover_file = '';
            $upload_dir = __DIR__ . '/uploads/';
            
            // Ensure upload directory exists
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
        
        // Audio Upload
        if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] === UPLOAD_ERR_OK) {
            $name = time() . '_audio_' . preg_replace("/[^a-zA-Z0-9._-]/", "_", $_FILES['audio_file']['name']);
            if (move_uploaded_file($_FILES['audio_file']['tmp_name'], $upload_dir . $name)) {
                $audio_file = $name;
            }
        }
        
        // Cover Upload
        if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] === UPLOAD_ERR_OK) {
            $name = time() . '_cover_' . preg_replace("/[^a-zA-Z0-9._-]/", "_", $_FILES['cover_file']['name']);
            if (move_uploaded_file($_FILES['cover_file']['tmp_name'], $upload_dir . $name)) {
                $cover_file = $name;
            }
        }

        $stmt = $db->prepare("INSERT INTO projects 
            (user_id, title, artist_name, full_name, email, type, genre, date_sortie, details, phone, city, languages, provided_files, promo_pack, authorization, audio_file, cover_file) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $_POST['titre_projet'] ?? '',
            $_POST['nom_artiste'] ?? '',
            $_POST['nom_complet'] ?? '',
            $_POST['email'] ?? '',
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

        $titreProjet = $_POST['titre_projet'] ?? 'Projet sans titre';
        $nomArtiste = $_POST['nom_artiste'] ?? $_SESSION['user_name'];

        // Notifier tous les admins
        $admins = $db->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll();
        foreach ($admins as $admin) {
            createNotification($admin['id'], 'new_project', "Nouveau projet soumis : " . $titreProjet, $projectId);
        }
        
        // Notifier l'artiste
        $status_msg = ($_POST['pack_promo'] ?? 'Aucun') === 'Aucun' ? "Votre projet '" . $titreProjet . "' a été soumis. En attente de validation." : "Votre projet '" . $titreProjet . "' a été soumis. En attente de paiement.";
        createNotification($_SESSION['user_id'], 'project_update', $status_msg, $projectId);

        // Envoyer un email à l'équipe WMA
        require_once __DIR__ . '/../../includes/mailer.php';
        notifyNewProject(
            $projectId,
            $titreProjet,
            $nomArtiste,
            $_POST['type_projet'] ?? 'Single',
            $_POST['date_sortie'] ?? date('Y-m-d')
        );

        $redirect_url = 'submit.php?success=1&pid=' . $projectId . '&title=' . urlencode($titreProjet) . '&artist=' . urlencode($nomArtiste) . '&pack=' . urlencode($_POST['pack_promo'] ?? 'Aucun');
        
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'redirect' => $redirect_url]);
            exit;
        }

        header('Location: ' . $redirect_url);
        exit;
    } catch (Exception $e) {
        $error = "Une erreur est survenue : " . $e->getMessage();
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $error]);
            exit;
        }
    }
    }
    
    if ($is_ajax && $error) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $error]);
        exit;
    }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soumettre un projet - WMA Hub</title>
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
        .btn-submit { width: 100%; padding: 16px; background: linear-gradient(135deg, #ff6600 0%, #ff8c00 100%); color: #fff; border-radius: 16px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; transition: all 0.3s ease; box-shadow: 0 10px 20px rgba(255, 102, 0, 0.2); }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(255, 102, 0, 0.3); }
        .custom-option { padding: 16px; border-radius: 16px; background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.05); color: #94a3b8; font-weight: 600; transition: all 0.3s ease; text-align: center; }
        .file-input-label { display: block; padding: 32px; border: 2px dashed rgba(255, 255, 255, 0.1); border-radius: 20px; text-align: center; color: #94a3b8; cursor: pointer; transition: all 0.3s ease; }
        .file-input-label:hover { border-color: #ff6600; color: #fff; background: rgba(102, 255, 0, 0.05); }
        
        /* Upload Progress Loader Styles */
        #upload-loader { display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.85); z-index: 10000; flex-direction: column; align-items: center; justify-content: center; backdrop-filter: blur(8px); }
        .progress-container { width: 90%; max-width: 400px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 30px; height: 16px; overflow: hidden; margin-top: 24px; position: relative; }
        .progress-bar { height: 100%; width: 0%; background: linear-gradient(90deg, #ff6600, #ff8c00); transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 0 20px rgba(255, 102, 0, 0.5); }
        .progress-text { margin-top: 16px; font-weight: 700; color: #fff; letter-spacing: 1px; font-size: 1.1rem; }
        .loader-info { margin-top: 8px; color: #94a3b8; font-size: 0.8rem; font-weight: 500; }
    </style>
</head>
<body>
    <div id="wma-global-loader">
        <div class="loader-spin"></div>
    </div>
    
    <!-- Upload Progress Loader -->
    <div id="upload-loader">
        <div class="loader-spin mb-4" style="width: 60px; height: 60px;"></div>
        <h3 class="text-2xl font-black tracking-tighter text-white">Traitement du Projet</h3>
        <div class="progress-container">
            <div class="progress-bar"></div>
        </div>
        <p class="progress-text">Téléchargement : 0%</p>
        <p class="loader-info">Veuillez ne pas fermer cette page...</p>
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
                <a href="contrat.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'contrat.php' ? 'active' : '' ?>">
                    <i class="fas fa-file-contract"></i>
                    <span class="font-medium">Contrat</span>
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
            <header class="mb-12">
                <h1 class="text-4xl lg:text-5xl font-black tracking-tighter mb-2">Soumettre mon <span class="text-orange-500">Projet</span></h1>
                <p class="text-gray-500 font-medium tracking-tight">Préparez le lancement de votre prochaine pépite.</p>
            </header>

            <?php if ($error): ?>
                <div id="error-container" class="mb-8 p-6 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-500 flex items-center gap-4">
                    <i class="fas fa-exclamation-circle text-2xl"></i>
                    <p class="font-bold error-message"><?= htmlspecialchars($error) ?></p>
                </div>
            <?php else: ?>
                <div id="error-container" class="hidden mb-8 p-6 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-500 flex items-center gap-4">
                    <i class="fas fa-exclamation-circle text-2xl"></i>
                    <p class="font-bold error-message"></p>
                </div>
            <?php endif; ?>

            <?php if ($success): 
                $pid = $_GET['pid'] ?? '0';
                $title = $_GET['title'] ?? 'Projet';
                $artist = $_GET['artist'] ?? 'Artiste';
                $pack = $_GET['pack'] ?? 'Aucun';
                $projectLink = "https://wmahub.com/project/" . $pid;
                $whatsappMessage = "Bonjour WMA HUB, voici ma preuve de paiement pour mon projet :\n- *Capture d'écran* : (Attachée)\n- *ID Projet* : #$pid\n- *Titre* : $title\n- *Artiste* : $artist\n- *Lien* : $projectLink";
                $whatsappUrl = "https://wa.me/256743297668?text=" . urlencode($whatsappMessage);
            ?>
                <div class="glass-card p-12 text-center">
                    <div class="max-w-2xl mx-auto">
                        <div class="w-20 h-20 bg-green-500/20 text-green-500 rounded-full flex items-center justify-center mx-auto mb-6 border border-green-500/30">
                            <i class="fas fa-check text-3xl"></i>
                        </div>
                        <h2 class="text-3xl font-black text-white mb-2">Projet Enregistré !</h2>
                        
                        <?php if ($pack === 'Aucun'): ?>
                            <p class="text-gray-400 mb-10">Votre ID Projet est <span class="text-white font-bold">#<?= $pid ?></span>. Votre projet est en cours de traitement et sera validé prochainement. Accès 100% Gratuit.</p>
                        <?php else: ?>
                            <p class="text-gray-400 mb-10">Votre ID Projet est <span class="text-white font-bold">#<?= $pid ?></span>. Veuillez finaliser le paiement pour activer le pack <span class="text-orange-500 font-bold"><?= htmlspecialchars($pack) ?></span>.</p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-10">
                                <div class="bg-red-600/10 border border-red-600/30 p-4 rounded-2xl">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-red-500 block mb-2">Airtel Money</span>
                                    <p class="text-lg font-bold text-white">0970000000</p>
                                </div>
                                <div class="bg-blue-600/10 border border-blue-600/30 p-4 rounded-2xl">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-blue-500 block mb-2">M-Pesa</span>
                                    <p class="text-lg font-bold text-white">0810000000</p>
                                </div>
                                <div class="bg-orange-600/10 border border-orange-600/30 p-4 rounded-2xl">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-orange-500 block mb-2">Orange Money</span>
                                    <p class="text-lg font-bold text-white">0890000000</p>
                                </div>
                            </div>

                            <a href="<?= $whatsappUrl ?>" target="_blank" class="btn-submit !bg-green-600 shadow-green-600/40 flex items-center justify-center gap-2 mb-6">
                                <i class="fab fa-whatsapp"></i> Envoyer la preuve via WhatsApp
                            </a>
                        <?php endif; ?>

                        <div class="flex gap-4">
                            <a href="catalogue.php" class="flex-1 bg-white/5 hover:bg-white/10 text-white font-bold py-4 rounded-2xl transition-all text-sm uppercase tracking-widest">Voir mes projets</a>
                            <button onclick="window.location.href='submit.php'" class="flex-1 bg-white/5 hover:bg-white/10 text-white font-bold py-4 rounded-2xl transition-all text-sm uppercase tracking-widest">Nouvelle soumission</button>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <form id="projectSubmitForm" method="POST" enctype="multipart/form-data" class="glass-card p-8 md:p-12 space-y-12">
                    <input type="hidden" name="is_ajax" value="1">
                     <section>
                        <h3 class="text-xl font-bold mb-8 flex items-center gap-3 uppercase tracking-tighter"><i class="fas fa-user-circle text-orange-500"></i> 1. Informations Personnelles</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Nom et Prénom *</label>
                                <input type="text" name="nom_complet" required class="input-glass" placeholder="Votre identité réelle">
                            </div>
                             <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Nom d'Artiste *</label>
                                <input type="text" name="nom_artiste" required class="input-glass" placeholder="Votre pseudonyme">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Adresse E-mail *</label>
                                <input type="email" name="email" required class="input-glass" placeholder="contact@votre-musique.com">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">WhatsApp (Numéro) *</label>
                                <input type="tel" name="telephone" required class="input-glass" placeholder="+243 ...">
                            </div>
                        </div>
                    </section>
                    
                    <div class="h-px bg-white/5"></div>
                    
                    <section>
                        <h3 class="text-xl font-bold mb-8 flex items-center gap-3 uppercase tracking-tighter"><i class="fas fa-compact-disc text-orange-500"></i> 2. Informations sur le Projet</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Titre du Projet *</label>
                                <input type="text" name="titre_projet" required class="input-glass" placeholder="Nom de l'album, du single...">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Détails / Liste des titres *</label>
                                <textarea name="details_morceaux" required class="input-glass" rows="3" placeholder="Si c'est un EP ou Album, listez les titres ici..."></textarea>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Type de Projet *</label>
                                <div class="grid grid-cols-3 gap-3">
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="type_projet" value="Single" required class="sr-only peer">
                                        <div class="custom-option peer-checked:bg-orange-500/10 peer-checked:border-orange-500 peer-checked:text-orange-500 text-xs">Single</div>
                                    </label>
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="type_projet" value="EP" class="sr-only peer">
                                        <div class="custom-option peer-checked:bg-orange-500/10 peer-checked:border-orange-500 peer-checked:text-orange-500 text-xs">EP</div>
                                    </label>
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="type_projet" value="Album" class="sr-only peer">
                                        <div class="custom-option peer-checked:bg-orange-500/10 peer-checked:border-orange-500 peer-checked:text-orange-500 text-xs">Album</div>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Genre Musical *</label>
                                <input type="text" name="genre" required class="input-glass" placeholder="Afro, Rap, R&B...">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Date de Sortie Souhaitée *</label>
                                <input type="date" name="date_sortie" required class="input-glass">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Ville *</label>
                                <input type="text" name="ville" required class="input-glass" placeholder="Votre ville actuelle">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Langues chantées *</label>
                                <input type="text" name="langues" required class="input-glass" placeholder="Français, Lingala, Swahili...">
                            </div>
                        </div>
                    </section>
                    
                    <div class="h-px bg-white/5"></div>
                    
                    <section>
                        <h3 class="text-xl font-bold mb-8 flex items-center gap-3 uppercase tracking-tighter"><i class="fas fa-file-contract text-orange-500"></i> 5. Autorisation de Distribution</h3>
                        <div class="glass-card p-6 border-orange-500/20 bg-orange-500/5">
                            <label class="flex items-start gap-4 cursor-pointer">
                                <input type="checkbox" name="autorisation" value="Oui" required class="mt-1 w-5 h-5 rounded border-white/10 bg-white/5 text-orange-500 focus:ring-orange-500">
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-white mb-1">J'autorise WMA HUB à distribuer ce projet *</p>
                                    <p class="text-[10px] text-gray-500 leading-relaxed font-medium">En cochant cette case, je confirme détenir tous les droits sur les œuvres soumises et j'autorise WMA HUB à procéder à leur distribution sur les plateformes numériques.</p>
                                </div>
                            </label>
                        </div>
                    </section>
                    
                    <div class="h-px bg-white/5"></div>
                    
                    <section>
                        <h3 class="text-xl font-bold mb-8 flex items-center gap-3 uppercase tracking-tighter"><i class="fas fa-file-export text-orange-500"></i> 3. Fichiers et Éléments</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Fichier Audio (MP3/WAV) *</label>
                                <label for="audio_file" class="file-input-label">
                                    <i class="fas fa-cloud-upload-alt text-2xl mb-2 block text-orange-500"></i>
                                    <span id="audio_name" class="text-[10px] font-black uppercase tracking-widest">Cliquez pour téléverser</span>
                                </label>
                                <input type="file" name="audio_file" id="audio_file" required class="sr-only" accept=".mp3,.wav" onchange="updateFileName(this, 'audio_name')">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Pochette (JPG/PNG - 3000px) *</label>
                                <label for="cover_file" class="file-input-label">
                                    <i class="fas fa-image text-2xl mb-2 block text-orange-500"></i>
                                    <span id="cover_name" class="text-[10px] font-black uppercase tracking-widest">Cliquez pour téléverser</span>
                                </label>
                                <input type="file" name="cover_file" id="cover_file" required class="sr-only" accept=".jpg,.jpeg,.png" onchange="updateFileName(this, 'cover_name')">
                            </div>
                        </div>
                    </section>
                    
                    <div class="h-px bg-white/5"></div>
                    
                    <section>
                        <h3 class="text-xl font-bold mb-4 flex items-center gap-3 uppercase tracking-tighter"><i class="fas fa-gift text-orange-500"></i> 4. Choix du Pack Promo (Optionnel)</h3>
                        <p class="text-gray-500 font-medium text-sm mb-8">Sélectionnez le pack de promotion qui correspond à vos besoins.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="pack_promo" value="Aucun" checked class="sr-only peer">
                                <div class="h-full p-6 bg-white/[0.02] border border-white/5 rounded-2xl transition-all peer-checked:bg-white/[0.05] peer-checked:border-gray-500 flex flex-col">
                                    <span class="text-sm font-bold mb-2">⚪ Aucun</span>
                                    <span class="text-[10px] text-gray-500 font-medium mb-4 flex-1">Pas de pack promotionnel.</span>
                                    <span class="text-white font-black text-2xl mt-auto">$0</span>
                                </div>
                            </label>
                            <label class="relative cursor-pointer">
                                <input type="radio" name="pack_promo" value="Starter" class="sr-only peer">
                                <div class="h-full p-6 bg-white/[0.02] border border-white/5 rounded-2xl transition-all peer-checked:bg-orange-500/10 peer-checked:border-orange-500 flex flex-col">
                                    <span class="text-sm font-bold mb-2 text-orange-500">🚀 Starter</span>
                                    <span class="text-[10px] text-gray-500 font-medium mb-4 flex-1">Distribution basique sur toutes les plateformes.</span>
                                    <span class="text-white font-black text-2xl mt-auto">$<?= getSetting('pack_starter_usd', 15) ?></span>
                                </div>
                            </label>
                            <label class="relative cursor-pointer">
                                <input type="radio" name="pack_promo" value="Pro" class="sr-only peer">
                                <div class="h-full p-6 bg-white/[0.02] border border-white/5 rounded-2xl transition-all peer-checked:bg-orange-500/10 peer-checked:border-orange-500 flex flex-col">
                                    <span class="text-sm font-bold mb-2 text-orange-500">⭐ Pro</span>
                                    <span class="text-[10px] text-gray-500 font-medium mb-4 flex-1">Distribution + Promo réseaux sociaux.</span>
                                    <span class="text-white font-black text-2xl mt-auto">$<?= getSetting('pack_pro_usd', 35) ?></span>
                                </div>
                            </label>
                            <label class="relative cursor-pointer">
                                <input type="radio" name="pack_promo" value="Premium" class="sr-only peer">
                                <div class="h-full p-6 bg-white/[0.02] border border-white/5 rounded-2xl transition-all peer-checked:bg-orange-500/10 peer-checked:border-orange-500 flex flex-col">
                                    <span class="text-sm font-bold mb-2 text-orange-500">👑 Premium</span>
                                    <span class="text-[10px] text-gray-500 font-medium mb-4 flex-1">Distribution + Promo complète + Clip YouTube.</span>
                                    <span class="text-white font-black text-2xl mt-auto">$<?= getSetting('pack_premium_usd', 75) ?></span>
                                </div>
                            </label>
                        </div>
                    </section>

                    <div class="pt-10">
                        <button type="submit" class="btn-submit">Valider la Soumission <i class="fas fa-paper-plane ml-2"></i></button>
                    </div>
                </form>
            <?php endif; ?>
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

        function updateFileName(input, targetId) {
            const fileName = input.files[0] ? input.files[0].name : "Cliquez pour téléverser";
            const target = document.getElementById(targetId);
            target.textContent = fileName;
        }

        window.addEventListener('load', () => {
            const loader = document.getElementById('wma-global-loader');
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(() => loader.style.display = 'none', 500);
            }
        });

        // AJAX Form Submission with Progress
        const form = document.getElementById('projectSubmitForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const xhr = new XMLHttpRequest();
                
                const uploadLoader = document.getElementById('upload-loader');
                const progressBar = uploadLoader.querySelector('.progress-bar');
                const progressText = uploadLoader.querySelector('.progress-text');
                const errorContainer = document.getElementById('error-container');
                const errorMessage = errorContainer.querySelector('.error-message');
                
                // Reset error container
                errorContainer.classList.add('hidden');
                
                // Show loader
                uploadLoader.style.display = 'flex';
                progressBar.style.width = '0%';
                progressText.textContent = 'Téléchargement : 0%';
                
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percent = Math.round((e.loaded / e.total) * 100);
                        progressBar.style.width = percent + '%';
                        progressText.textContent = 'Téléchargement : ' + percent + '%';
                        
                        if (percent === 100) {
                            progressText.textContent = 'Traitement final en cours...';
                        }
                    }
                });
                
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                window.location.href = response.redirect;
                            } else {
                                uploadLoader.style.display = 'none';
                                errorMessage.textContent = response.error;
                                errorContainer.classList.remove('hidden');
                                window.scrollTo({ top: 0, behavior: 'smooth' });
                            }
                        } catch (e) {
                            console.error('Response error:', xhr.responseText);
                            uploadLoader.style.display = 'none';
                            alert('Une erreur inattendue est survenue.');
                        }
                    } else {
                        uploadLoader.style.display = 'none';
                        alert('Erreur serveur : ' + xhr.status);
                    }
                };
                
                xhr.onerror = function() {
                    uploadLoader.style.display = 'none';
                    alert('Erreur réseau. Veuillez vérifier votre connexion.');
                };
                
                xhr.open('POST', window.location.href);
                xhr.send(formData);
            });
        }
    </script>
</body>
</html>
