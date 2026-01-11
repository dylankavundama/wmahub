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
        body { 
            font-family: 'Poppins', sans-serif; 
            margin: 0;
            background: #0a0a0c;
            color: #fff;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Animated Background */
        .bg-glow {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%);
            z-index: -1;
        }

        .glow-spot {
            position: fixed;
            width: 60vw;
            height: 60vw;
            background: radial-gradient(circle, rgba(255, 102, 0, 0.05) 0%, transparent 70%);
            border-radius: 50%;
            z-index: -1;
            filter: blur(80px);
            pointer-events: none;
        }

        /* Glassmorphism */
        .glass-card {
            background: rgba(255, 255, 255, 0.02);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 2rem;
            box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.8);
        }

        .input-glass {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            color: #fff;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            width: 100%;
            padding: 1rem 1.25rem;
        }

        .input-glass:focus {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 102, 0, 0.5);
            box-shadow: 0 0 20px rgba(255, 102, 0, 0.1);
            outline: none;
        }

        .label-style {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: rgba(255, 255, 255, 0.4);
            margin-bottom: 0.75rem;
            display: block;
        }

        h3 {
            font-size: 1.25rem;
            font-weight: 800;
            color: #ff6600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-separator {
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            margin: 3rem 0;
        }

        /* Custom Radio/Checkbox */
        .custom-option {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 1rem;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .custom-option:hover {
            background: rgba(255, 102, 0, 0.05);
            border-color: rgba(255, 102, 0, 0.3);
        }

        input[type="radio"]:checked + .custom-option,
        input[type="checkbox"]:checked + .custom-option {
            background: rgba(255, 102, 0, 0.1);
            border-color: #ff6600;
            color: #ff6600;
        }

        .btn-submit {
            background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%);
            color: #fff;
            padding: 1.5rem;
            border-radius: 1.25rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 15px 35px -5px rgba(255, 102, 0, 0.4);
            width: 100%;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 45px -5px rgba(255, 102, 0, 0.6);
            filter: brightness(1.1);
        }

        .promo-tag {
            background: rgba(255, 102, 0, 0.2);
            color: #ff6600;
            padding: 0.2rem 0.5rem;
            border-radius: 0.5rem;
            font-size: 0.7rem;
            font-weight: 800;
        }

        /* File input styling */
        .file-input-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .file-input-label {
            display: block;
            padding: 1.25rem;
            background: rgba(255, 255, 255, 0.03);
            border: 2px dashed rgba(255, 255, 255, 0.1);
            border-radius: 1.25rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-input-label:hover {
            background: rgba(255, 102, 0, 0.05);
            border-color: rgba(255, 102, 0, 0.3);
        }

        .file-input-label i {
            display: block;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: rgba(255, 255, 255, 0.3);
        }

        .file-input-label span {
            font-size: 0.9rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.5);
        }

        /* Responsive grid */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 2rem;
        }

        @media (min-width: 768px) {
            .form-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>
</head>
<body class="p-6 md:p-12">
    <div class="bg-glow"></div>
    <div class="glow-spot" id="glow"></div>

    <div class="max-w-4xl mx-auto">
        <header class="mb-12 flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div>
                <a href="index.php" class="inline-flex items-center text-orange-500/60 hover:text-orange-500 font-bold text-xs uppercase tracking-widest transition-all group mb-4">
                    <i class="fas fa-arrow-left mr-2 group-hover:-translate-x-1 transition-transform"></i>
                    Tableau de Bord
                </a>
                <h1 class="text-4xl md:text-6xl font-black text-white tracking-tighter">Soumettre mon <span class="text-orange-500">Projet</span></h1>
                <p class="text-gray-400 text-lg mt-2">Préparez le lancement de votre prochaine pépite.</p>
            </div>
            <img src="../../asset/trans.png" alt="Logo" class="h-16 opacity-10">
        </header>

        <?php if ($success): 
            $pid = $_GET['pid'] ?? '0';
            $title = $_GET['title'] ?? 'Projet';
            $artist = $_GET['artist'] ?? 'Artiste';
            $projectLink = "https://wmahub.com/project/" . $pid; // Exemple de lien
            
            $whatsappMessage = "Bonjour WMA HUB, voici ma preuve de paiement pour mon projet :
- *Capture d'écran* : (Attachée)
- *ID Projet* : #$pid
- *Titre* : $title
- *Artiste* : $artist
- *Lien* : $projectLink";
            $whatsappUrl = "https://wa.me/243825555555?text=" . urlencode($whatsappMessage); // Remplacer par le vrai numéro
        ?>
            <div class="glass-card p-12 animate-bounce-in">
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
                            <p class="text-[9px] text-gray-500 mt-1">Nom: WMA HUB LTD</p>
                        </div>
                        <div class="bg-blue-600/10 border border-blue-600/30 p-4 rounded-2xl text-center">
                            <span class="text-[10px] font-black uppercase tracking-widest text-blue-500 block mb-2">M-Pesa</span>
                            <p class="text-lg font-bold text-white">0810000000</p>
                            <p class="text-[9px] text-gray-500 mt-1">Nom: WMA HUB LTD</p>
                        </div>
                        <div class="bg-orange-600/10 border border-orange-600/30 p-4 rounded-2xl text-center">
                            <span class="text-[10px] font-black uppercase tracking-widest text-orange-500 block mb-2">Orange Money</span>
                            <p class="text-lg font-bold text-white">0890000000</p>
                            <p class="text-[9px] text-gray-500 mt-1">Nom: WMA HUB LTD</p>
                        </div>
                    </div>

                    <div class="bg-orange-500/5 border border-orange-500/20 p-6 rounded-2xl mb-10">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-full bg-orange-500/20 flex items-center justify-center text-orange-500 flex-shrink-0 mt-1">
                                <i class="fab fa-whatsapp text-xl"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-white mb-1 uppercase text-xs tracking-widest">Action Requise</h4>
                                <p class="text-gray-400 text-sm leading-relaxed">
                                    Veuillez envoyer la <span class="text-white font-bold underline">preuve de paiement</span> (capture d'écran) sur WhatsApp en cliquant sur le bouton ci-dessous.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-4">
                        <a href="<?= $whatsappUrl ?>" target="_blank" class="btn-submit !bg-green-600 !shadow-green-600/40 text-center">
                            Envoyer la preuve via WhatsApp <i class="fab fa-whatsapp ml-2"></i>
                        </a>
                        <div class="flex gap-4">
                            <a href="index.php" class="flex-1 bg-white/5 hover:bg-white/10 text-white font-bold py-4 rounded-2xl transition-all text-center text-sm">
                                Voir mes projets
                            </a>
                            <button onclick="window.location.href='submit.php'" class="flex-1 bg-white/5 hover:bg-white/10 text-white font-bold py-4 rounded-2xl transition-all text-sm">
                                Nouvelle soumission
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="bg-red-500/10 border border-red-500/30 text-red-400 p-6 rounded-2xl mb-8 flex items-center gap-4">
                    <i class="fas fa-exclamation-triangle text-xl"></i>
                    <p class="font-bold"><?= $error ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="glass-card p-8 md:p-12 space-y-12">
                <!-- Section 1: Personal Info -->
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
                        <div class="md:col-span-2">
                            <label class="label-style">Ville et Pays *</label>
                            <input type="text" name="ville" required class="input-glass" placeholder="Kinshasa, RDC">
                        </div>
                    </div>
                </section>

                <div class="section-separator"></div>

                <!-- Section 2: Project Info -->
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
                                <label class="relative">
                                    <input type="radio" name="type_projet" value="Single" required class="sr-only">
                                    <div class="custom-option">Single</div>
                                </label>
                                <label class="relative">
                                    <input type="radio" name="type_projet" value="EP" class="sr-only">
                                    <div class="custom-option">EP</div>
                                </label>
                                <label class="relative">
                                    <input type="radio" name="type_projet" value="Album" class="sr-only">
                                    <div class="custom-option">Album</div>
                                </label>
                            </div>
                        </div>
                        <div class="space-y-6">
                            <div>
                                <label class="label-style">Genre Musical *</label>
                                <input type="text" name="genre" required class="input-glass" placeholder="Afro, Rap, R&B...">
                            </div>
                            <div>
                                <label class="label-style">Langue(s) utilisée(s)</label>
                                <input type="text" name="langues" class="input-glass" placeholder="Français, Lingala...">
                            </div>
                            <div>
                                <label class="label-style">Date de Sortie Souhaitée *</label>
                                <input type="date" name="date_sortie" required class="input-glass">
                            </div>
                        </div>
                    </div>
                </section>

                <div class="section-separator"></div>

                <!-- Section 3: Track Details -->
                <section>
                    <h3><i class="fas fa-list-ol"></i> 3. Détails des Morceaux</h3>
                    <p class="text-xs text-gray-500 mb-4">Obligatoire pour les EP et Albums. Listez le titre et la durée de chaque morceau.</p>
                    <textarea name="details_morceaux" rows="6" class="input-glass resize-none" placeholder="1. Intro - 02:30&#10;2. Hit song - 03:45..."></textarea>
                </section>

                <div class="section-separator"></div>

                <!-- Section 4: Elements to provide -->
                <section>
                    <h3><i class="fas fa-file-export"></i> 4. Fichiers et Éléments</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                        <div>
                            <label class="label-style">Fichier Audio (MP3/WAV) *</label>
                            <div class="file-input-wrapper">
                                <label for="audio_file" class="file-input-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span id="audio_name">Cliquez pour téléverser l'audio</span>
                                </label>
                                <input type="file" name="audio_file" id="audio_file" required class="hidden" accept=".mp3,.wav" onchange="updateFileName(this, 'audio_name')">
                            </div>
                        </div>
                        <div>
                            <label class="label-style">Pochette (JPG/PNG - 3000px) *</label>
                            <div class="file-input-wrapper">
                                <label for="cover_file" class="file-input-label">
                                    <i class="fas fa-image"></i>
                                    <span id="cover_name">Cliquez pour téléverser la pochette</span>
                                </label>
                                <input type="file" name="cover_file" id="cover_file" required class="hidden" accept=".jpg,.jpeg,.png" onchange="updateFileName(this, 'cover_name')">
                            </div>
                        </div>
                    </div>

                    <p class="label-style mb-4">Autres éléments fournis :</p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <label class="relative">
                            <input type="checkbox" name="fichiers[]" value="Paroles" class="sr-only">
                            <div class="custom-option"><i class="fas fa-align-left"></i> Paroles</div>
                        </label>
                        <label class="relative">
                            <input type="checkbox" name="fichiers[]" value="Credits" class="sr-only">
                            <div class="custom-option"><i class="fas fa-info-circle"></i> Crédits</div>
                        </label>
                        <label class="relative">
                            <input type="checkbox" name="fichiers[]" value="Visuels" class="sr-only">
                            <div class="custom-option"><i class="fas fa-camera"></i> Visuels</div>
                        </label>
                    </div>
                </section>

                <div class="section-separator"></div>

                <!-- Section 5: Promo Packs -->
                <section>
                    <h3><i class="fas fa-crown"></i> 5. Choix du Pack Promotionnel</h3>
                    <div class="space-y-4">
                        <label class="relative block">
                            <input type="radio" name="pack_promo" value="Starter" class="sr-only">
                            <div class="custom-option flex-col !items-start">
                                <div class="flex justify-between w-full items-center">
                                    <span class="font-bold">Pack Starter</span>
                                    <span class="promo-tag">50$</span>
                                </div>
                                <p class="text-xs opacity-60">Réseaux sociaux et diffusion basique.</p>
                            </div>
                        </label>
                        <label class="relative block">
                            <input type="radio" name="pack_promo" value="Standard" class="sr-only">
                            <div class="custom-option flex-col !items-start">
                                <div class="flex justify-between w-full items-center">
                                    <span class="font-bold">Pack Standard</span>
                                    <span class="promo-tag">90$</span>
                                </div>
                                <p class="text-xs opacity-60">Réseaux sociaux, presse locale et boost visuel.</p>
                            </div>
                        </label>
                        <label class="relative block">
                            <input type="radio" name="pack_promo" value="Pro" class="sr-only">
                            <div class="custom-option flex-col !items-start">
                                <div class="flex justify-between w-full items-center">
                                    <span class="font-bold">Pack Pro</span>
                                    <span class="promo-tag">150$</span>
                                </div>
                                <p class="text-xs opacity-60">Presse nationale et playlisting digital.</p>
                            </div>
                        </label>
                        <label class="relative block">
                            <input type="radio" name="pack_promo" value="Premium" class="sr-only">
                            <div class="custom-option flex-col !items-start">
                                <div class="flex justify-between w-full items-center">
                                    <span class="font-bold">Pack Premium</span>
                                    <span class="promo-tag">350$</span>
                                </div>
                                <p class="text-xs opacity-60">Accompagnement complet sur 6 mois (Com, suivi, formation).</p>
                            </div>
                        </label>
                    </div>
                </section>

                <div class="section-separator"></div>

                <!-- Section 6: Legal -->
                <section>
                    <h3><i class="fas fa-signature"></i> 6. Conditions & Autorisation</h3>
                    <div class="space-y-6">
                        <div>
                            <label class="label-style">J'autorise WMA Hub à distribuer mon projet *</label>
                            <div class="flex gap-4">
                                <label class="relative flex-1">
                                    <input type="radio" name="autorisation" value="Oui" required class="sr-only">
                                    <div class="custom-option justify-center">OUI</div>
                                </label>
                                <label class="relative flex-1">
                                    <input type="radio" name="autorisation" value="Non" class="sr-only">
                                    <div class="custom-option justify-center">NON</div>
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="label-style">Date de Validation *</label>
                            <input type="date" name="date_signature" required class="input-glass" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                </section>

                <div class="pt-10">
                    <button type="submit" class="btn-submit">
                        Valider la Soumission <i class="fas fa-paper-plane ml-2"></i>
                    </button>
                    <p class="text-center text-[10px] text-gray-600 font-bold uppercase tracking-widest mt-8">
                        En soumettant ce formulaire, vous acceptez nos conditions générales d'utilisation.
                    </p>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script>
        const glow = document.getElementById('glow');
        document.addEventListener('mousemove', (e) => {
            const x = e.clientX;
            const y = e.clientY;
            glow.style.left = (x - glow.offsetWidth / 2) + 'px';
            glow.style.top = (y - glow.offsetHeight / 2) + 'px';
        });

        function updateFileName(input, targetId) {
            const fileName = input.files[0] ? input.files[0].name : "Cliquez pour téléverser";
            const target = document.getElementById(targetId);
            target.textContent = fileName;
            target.parentElement.style.borderColor = "#ff6600";
            target.parentElement.style.color = "#ff6600";
        }
    </script>
</body>
</html>
