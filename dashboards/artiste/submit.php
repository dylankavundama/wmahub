<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint aux artistes
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'artiste') {
    header('Location: ../../auth/login.php');
    exit;
}

$success = isset($_GET['success']);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            (user_id, title, artist_name, type, genre, date_sortie, details, phone, city, languages, provided_files, promo_pack, authorization, audio_file, cover_file) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $_POST['titre_projet'],
            $_POST['nom_artiste'] ?? '',
            $_POST['type_projet'],
            $_POST['genre'],
            $_POST['date_sortie'],
            $_POST['details_morceaux'] ?? '',
            $_POST['telephone'],
            $_POST['ville'],
            $_POST['langues'] ?? '',
            isset($_POST['fichiers']) ? implode(', ', $_POST['fichiers']) : '',
            $_POST['pack_promo'] ?? 'Aucun',
            ($_POST['autorisation'] === 'Oui' ? 1 : 0),
            $audio_file,
            $cover_file
        ]);

        $projectId = $db->lastInsertId();

        // Notifier tous les admins
        $admins = $db->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll();
        foreach ($admins as $admin) {
            createNotification($admin['id'], 'new_project', "Nouveau projet soumis : " . $_POST['titre_projet'], $projectId);
        }
        
        // Notifier l'artiste
        createNotification($_SESSION['user_id'], 'project_update', "Votre projet '" . $_POST['titre_projet'] . "' a été soumis. En attente de paiement.", $projectId);

        header('Location: submit.php?success=1&pid=' . $projectId . '&title=' . urlencode($_POST['titre_projet']) . '&artist=' . urlencode($_POST['nom_artiste'] ?: $_SESSION['user_name']));
        exit;
    } catch (PDOException $e) {
        $error = "Une erreur est survenue lors de l'enregistrement : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soumettre un projet - WMA Hub</title>
    <link rel="icon" type="image/png" href="../../asset/icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; margin: 0; min-height: 100vh; overflow-x: hidden; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .glow-spot { position: fixed; width: 40vw; height: 40vw; background: radial-gradient(circle, rgba(255, 102, 0, 0.05) 0%, transparent 70%); border-radius: 50%; z-index: -1; filter: blur(80px); pointer-events: none; }
        .dashboard-wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; transition: all 0.3s ease; display: flex; flex-direction: column; padding: 2rem 1.5rem; }
        .main-content { flex: 1; margin-left: 280px; padding: 2rem; transition: all 0.3s ease; }
        @media (max-width: 1024px) { .sidebar { transform: translateX(-100%); } .sidebar.active { transform: translateX(0); } .main-content { margin-left: 0; padding: 1.5rem; } .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 90; backdrop-filter: blur(4px); } .sidebar-overlay.active { display: block; } .mobile-header { display: flex; } }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.5); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; transform: translateX(5px); }
        .glass-card { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 2rem; box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.8); }
        .input-glass { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 1rem; color: #fff; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); width: 100%; padding: 1rem 1.25rem; }
        .input-glass:focus { background: rgba(255, 255, 255, 0.06); border-color: rgba(255, 102, 0, 0.5); outline: none; }
        .label-style { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: rgba(255, 255, 255, 0.4); margin-bottom: 0.75rem; display: block; }
        h3 { font-size: 1.25rem; font-weight: 800; color: #ff6600; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; }
        .section-separator { border-top: 1px solid rgba(255, 255, 255, 0.05); margin: 3rem 0; }
        .custom-option { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 1rem; padding: 1rem; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 1rem; }
        .custom-option:hover { background: rgba(255, 102, 0, 0.05); border-color: rgba(255, 102, 0, 0.3); }
        input[type="radio"]:checked + .custom-option, input[type="checkbox"]:checked + .custom-option { background: rgba(255, 102, 0, 0.1); border-color: #ff6600; color: #ff6600; }
        .btn-submit { background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%); color: #fff; padding: 1.5rem; border-radius: 1.25rem; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 15px 35px -5px rgba(255, 102, 0, 0.4); width: 100%; }
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 20px 45px -5px rgba(255, 102, 0, 0.6); }
        .mobile-header { display: none; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; background: rgba(10, 10, 12, 0.8); backdrop-filter: blur(10px); position: sticky; top: 0; z-index: 80; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .file-input-label { display: block; padding: 1.25rem; background: rgba(255, 255, 255, 0.03); border: 2px dashed rgba(255, 255, 255, 0.1); border-radius: 1.25rem; text-align: center; cursor: pointer; transition: all 0.3s ease; }
        .file-input-label:hover { background: rgba(255, 102, 0, 0.05); border-color: rgba(255, 102, 0, 0.3); }
        .form-grid { display: grid; grid-template-columns: repeat(1, minmax(0, 1fr)); gap: 2rem; }
        @media (min-width: 768px) { .form-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    </style>
</head>
<body>
    <div class="bg-glow"></div>
    <div class="glow-spot" id="glow"></div>
    <div class="mobile-header">
        <div class="flex items-center gap-3"><img src="../../asset/trans.png" alt="Logo" class="h-8"><span class="font-bold">WMA HUB</span></div>
        <button id="sidebarToggle" class="text-white text-2xl"><i class="fas fa-bars"></i></button>
    </div>
    <div class="sidebar-overlay" id="overlay"></div>
    <div class="dashboard-wrapper">
        <aside class="sidebar" id="sidebar">
            <div class="flex items-center gap-4 mb-10 px-2">
                <img src="../../asset/trans.png" alt="Logo" class="h-10">
                <div>
                    <h1 class="text-xl font-bold bg-gradient-to-r from-orange-500 to-orange-300 bg-clip-text text-transparent leading-none">WMA HUB</h1>
                    <p class="text-[8px] text-gray-500 font-bold uppercase tracking-[1px] mt-1">We Farm Your Talent</p>
                </div>
            </div>
            <nav class="flex-1">
                <a href="index.php" class="nav-link"><i class="fas fa-th-large"></i>Tableau de bord</a>
                <a href="submit.php" class="nav-link active"><i class="fas fa-plus-circle"></i>Soumettre</a>
                <a href="services.php" class="nav-link"><i class="fas fa-magic"></i>Services</a>
                <a href="notifications.php" class="nav-link"><i class="fas fa-bell"></i>Notifications</a>
                <a href="catalogue.php" class="nav-link"><i class="fas fa-music"></i>Catalogue</a>
                <a href="stats.php" class="nav-link"><i class="fas fa-chart-line"></i>Stats</a>
                <a href="#" class="nav-link disabled opacity-50 cursor-not-allowed"><i class="fas fa-wallet"></i>Revenus</a>
            </nav>
            <div class="mt-auto pt-6 border-t border-white/5">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-full bg-orange-500/20 flex items-center justify-center text-orange-500 border border-orange-500/20"><i class="fas fa-user"></i></div>
                    <div class="overflow-hidden"><p class="text-sm font-bold truncate"><?= htmlspecialchars($_SESSION['user_name']) ?></p></div>
                </div>
                <a href="../../auth/logout.php" class="nav-link !text-red-500 hover:!bg-red-500/10"><i class="fas fa-power-off"></i>Déconnexion</a>
            </div>
        </aside>
        <main class="main-content">
            <header class="mb-12">
                <h1 class="text-4xl md:text-6xl font-black text-white tracking-tighter">Soumettre mon <span class="text-orange-500">Projet</span></h1>
                <p class="text-gray-400 text-lg mt-2">Préparez le lancement de votre prochaine pépite.</p>
            </header>

            <?php if ($success): 
                $pid = $_GET['pid'] ?? '0';
                $title = $_GET['title'] ?? 'Projet';
                $artist = $_GET['artist'] ?? 'Artiste';
                $projectLink = "https://wmahub.com/project/" . $pid;
                $whatsappMessage = "Bonjour WMA HUB, voici ma preuve de paiement pour mon projet :\n- *Capture d'écran* : (Attachée)\n- *ID Projet* : #$pid\n- *Titre* : $title\n- *Artiste* : $artist\n- *Lien* : $projectLink";
                $whatsappUrl = "https://wa.me/243825555555?text=" . urlencode($whatsappMessage);
            ?>
                <div class="glass-card p-12">
                    <div class="max-w-2xl mx-auto">
                        <div class="w-20 h-20 bg-green-500/20 text-green-500 rounded-full flex items-center justify-center mx-auto mb-6 border border-green-500/30">
                            <i class="fas fa-check text-3xl"></i>
                        </div>
                        <h2 class="text-3xl font-black text-white text-center mb-2">Projet Enregistré !</h2>
                        <p class="text-gray-400 text-center mb-10">Votre ID Projet est <span class="text-white font-bold">#<?= $pid ?></span>. Veuillez finaliser le paiement pour activer la distribution.</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-10">
                            <div class="bg-red-600/10 border border-red-600/30 p-4 rounded-2xl text-center">
                                <span class="text-[10px] font-black uppercase tracking-widest text-red-500 block mb-2">Airtel Money</span>
                                <p class="text-lg font-bold text-white">0970000000</p>
                            </div>
                            <div class="bg-blue-600/10 border border-blue-600/30 p-4 rounded-2xl text-center">
                                <span class="text-[10px] font-black uppercase tracking-widest text-blue-500 block mb-2">M-Pesa</span>
                                <p class="text-lg font-bold text-white">0810000000</p>
                            </div>
                            <div class="bg-orange-600/10 border border-orange-600/30 p-4 rounded-2xl text-center">
                                <span class="text-[10px] font-black uppercase tracking-widest text-orange-500 block mb-2">Orange Money</span>
                                <p class="text-lg font-bold text-white">0890000000</p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-4">
                            <a href="<?= $whatsappUrl ?>" target="_blank" class="btn-submit !bg-green-600 shadow-green-600/40 text-center">
                                Envoyer la preuve via WhatsApp <i class="fab fa-whatsapp ml-2"></i>
                            </a>
                            <div class="flex gap-4">
                                <a href="catalogue.php" class="flex-1 bg-white/5 hover:bg-white/10 text-white font-bold py-4 rounded-2xl transition-all text-center text-sm">Voir mes projets</a>
                                <button onclick="window.location.href='submit.php'" class="flex-1 bg-white/5 hover:bg-white/10 text-white font-bold py-4 rounded-2xl transition-all text-sm">Nouvelle soumission</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <form method="POST" enctype="multipart/form-data" class="glass-card p-8 md:p-12 space-y-12">
                     <section>
                        <h3><i class="fas fa-user-circle"></i> 1. Informations Personnelles</h3>
                        <div class="form-grid">
                            <div>
                                <label class="label-style">Nom et Prénom *</label>
                                <input type="text" name="nom_complet" required class="input-glass" placeholder="Votre identité réelle">
                            </div>
                            <div>
                                <label class="label-style">Nom d'Artiste</label>
                                <input type="text" name="nom_artiste" class="input-glass" placeholder="Votre pseudonyme">
                            </div>
                            <div>
                                <label class="label-style">Adresse E-mail *</label>
                                <input type="email" name="email" required class="input-glass" placeholder="contact@votre-musique.com">
                            </div>
                            <div>
                                <label class="label-style">WhatsApp (Numéro) *</label>
                                <input type="tel" name="telephone" required class="input-glass" placeholder="+243 ...">
                            </div>
                        </div>
                    </section>
                    <div class="section-separator"></div>
                    <section>
                        <h3><i class="fas fa-compact-disc"></i> 2. Informations sur le Projet</h3>
                        <div class="form-grid">
                            <div class="md:col-span-2">
                                <label class="label-style">Titre du Projet *</label>
                                <input type="text" name="titre_projet" required class="input-glass" placeholder="Nom de l'album, du single...">
                            </div>
                            <div>
                                <label class="label-style">Type de Projet *</label>
                                <div class="flex flex-col gap-3">
                                    <label class="relative"><input type="radio" name="type_projet" value="Single" required class="sr-only"><div class="custom-option">Single</div></label>
                                    <label class="relative"><input type="radio" name="type_projet" value="EP" class="sr-only"><div class="custom-option">EP</div></label>
                                    <label class="relative"><input type="radio" name="type_projet" value="Album" class="sr-only"><div class="custom-option">Album</div></label>
                                </div>
                            </div>
                            <div class="space-y-6">
                                <div><label class="label-style">Genre Musical *</label><input type="text" name="genre" required class="input-glass" placeholder="Afro, Rap, R&B..."></div>
                                <div><label class="label-style">Date de Sortie Souhaitée *</label><input type="date" name="date_sortie" required class="input-glass"></div>
                            </div>
                        </div>
                    </section>
                    <div class="section-separator"></div>
                    <section>
                        <h3><i class="fas fa-file-export"></i> 3. Fichiers et Éléments</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                            <div>
                                <label class="label-style">Fichier Audio (MP3/WAV) *</label>
                                <label for="audio_file" class="file-input-label"><i class="fas fa-cloud-upload-alt mb-2 block"></i><span id="audio_name" class="text-xs">Cliquez pour téléverser</span></label>
                                <input type="file" name="audio_file" id="audio_file" required class="hidden" accept=".mp3,.wav" onchange="updateFileName(this, 'audio_name')">
                            </div>
                            <div>
                                <label class="label-style">Pochette (JPG/PNG - 3000px) *</label>
                                <label for="cover_file" class="file-input-label"><i class="fas fa-image mb-2 block"></i><span id="cover_name" class="text-xs">Cliquez pour téléverser</span></label>
                                <input type="file" name="cover_file" id="cover_file" required class="hidden" accept=".jpg,.jpeg,.png" onchange="updateFileName(this, 'cover_name')">
                            </div>
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
        const s = document.getElementById('sidebar'), o = document.getElementById('overlay'), t = document.getElementById('sidebarToggle'), g = document.getElementById('glow');
        function ts() { s.classList.toggle('active'); o.classList.toggle('active'); }
        if(t) t.onclick = ts; if(o) o.onclick = ts;
        document.onmousemove = (e) => { g.style.left = (e.clientX - g.offsetWidth / 2) + 'px'; g.style.top = (e.clientY - g.offsetHeight / 2) + 'px'; };
        function updateFileName(input, targetId) {
            const fileName = input.files[0] ? input.files[0].name : "Cliquez pour téléverser";
            const target = document.getElementById(targetId);
            target.textContent = fileName;
        }
    </script>
</body>
</html>
