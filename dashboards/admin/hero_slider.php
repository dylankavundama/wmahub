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

// Traitement des actions (Ajout, Modif, Suppr)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cas spécial : si $_POST est vide mais que la requête est POST, 
    // c'est souvent un dépassement de post_max_size
    if (empty($_POST) && empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $error = "Le fichier est trop volumineux. Limite serveur atteinte.";
    } elseif (isset($_POST['add_slide'])) {
        $title = $_POST['title'];
        $subtitle = $_POST['subtitle'];
        $button_text = $_POST['button_text'];
        $button_link = $_POST['button_link'];
        $display_order = (int)$_POST['display_order'];
        
        $image_path = '';
        if (isset($_FILES['image'])) {
            if ($_FILES['image']['error'] === 0) {
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = 'hero_' . time() . '.' . $ext;
                if (!is_writable('../../asset/')) {
                    $error = "Le dossier 'asset/' n'est pas accessible en écriture.";
                } elseif (move_uploaded_file($_FILES['image']['tmp_name'], '../../asset/' . $filename)) {
                    $image_path = 'asset/' . $filename;
                } else {
                    $error = "Erreur lors du déplacement du fichier.";
                }
            } else {
                // Mapping des erreurs PHP
                switch ($_FILES['image']['error']) {
                    case 1: // UPLOAD_ERR_INI_SIZE
                        $error = "Le fichier dépasse la limite upload_max_filesize (" . ini_get('upload_max_filesize') . ")";
                        break;
                    case 2: // UPLOAD_ERR_FORM_SIZE
                        $error = "Le fichier dépasse la limite MAX_FILE_SIZE du formulaire.";
                        break;
                    case 3: // UPLOAD_ERR_PARTIAL
                        $error = "Le fichier n'a été que partiellement téléchargé.";
                        break;
                    case 4: // UPLOAD_ERR_NO_FILE
                        $error = "Aucun fichier n'a été téléchargé.";
                        break;
                    default:
                        $error = "Erreur d'upload inconnue (code " . $_FILES['image']['error'] . ")";
                        break;
                }
            }
        }

        if ($image_path) {
            $stmt = $db->prepare("INSERT INTO hero_slides (title, subtitle, image_path, button_text, button_link, display_order) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $subtitle, $image_path, $button_text, $button_link, $display_order]);
            $message = "Slide ajoutée avec succès !";
        }
    } elseif (isset($_POST['delete_slide'])) {
        $id = (int)$_POST['slide_id'];
        $stmt = $db->prepare("DELETE FROM hero_slides WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Slide supprimée.";
    } elseif (isset($_POST['toggle_status'])) {
        $id = (int)$_POST['slide_id'];
        $status = (int)$_POST['status'];
        $stmt = $db->prepare("UPDATE hero_slides SET is_active = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
    } elseif (isset($_POST['update_order'])) {
        $id = (int)$_POST['slide_id'];
        $order = (int)$_POST['order'];
        $stmt = $db->prepare("UPDATE hero_slides SET display_order = ? WHERE id = ?");
        $stmt->execute([$order, $id]);
    }
    
    // Si pas d'erreur critique nécessitant RE-POST, on peut rediriger ou continuer
    if (!$error && !isset($_POST['add_slide'])) {
        header('Location: hero_slider.php');
        exit;
    }
}

// Récupérer toutes les slides
$stmt = $db->query("SELECT * FROM hero_slides ORDER BY id DESC");
$slides = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMA HUB - Gestion du Slider</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/jpeg" href="../../asset/placeholder.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
            background: #0a0a0c !important; 
            color: #fff; 
            min-height: 100vh; 
            margin: 0;
            overflow-x: hidden;
        }
        .bg-glow { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; display: flex; flex-direction: column; padding: 2rem 1.5rem; transition: all 0.3s ease; }
        .main-content { margin-left: 280px; padding: 2rem; min-height: 100vh; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; font-size: 0.9rem; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 1.5rem; padding: 1.5rem; transition: all 0.3s ease; }
        .custom-input { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 0.75rem; padding: 0.75rem 1rem; color: white; width: 100%; outline: none; transition: all 0.3s ease; }
        .custom-input:focus { border-color: #ff6600; background: rgba(255, 102, 0, 0.05); }
        .btn-primary { background: #ff6600; color: white; padding: 0.75rem 1.5rem; border-radius: 0.75rem; font-weight: 600; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary:hover { background: #e65c00; transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="bg-glow"></div>

    <aside class="sidebar">
        <div class="flex items-center gap-4 mb-12 px-2">
            <img src="../../asset/trans.png" alt="Logo" class="h-10">
            <div>
                <h1 class="text-xl font-black bg-gradient-to-r from-orange-500 to-orange-300 bg-clip-text text-transparent tracking-tighter">WMA HUB</h1>
                <p class="text-[8px] text-gray-500 font-bold uppercase tracking-[1px] -mt-1">We move, WMAFam</p>
            </div>
        </div>
        <nav class="flex-1">
            <a href="index.php" class="nav-link"><i class="fas fa-layer-group"></i> Projets</a>
            <a href="hero_slider.php" class="nav-link active"><i class="fas fa-images"></i> Gestion Slider</a>
            <!-- Autres liens simplifiés pour le prototype -->
            <a href="index.php" class="nav-link"><i class="fas fa-arrow-left"></i> Retour au Dashboard</a>
        </nav>
        <div class="mt-auto pt-6 border-t border-white/5">
            <a href="../../auth/logout.php" class="nav-link !text-red-500"><i class="fas fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="flex justify-between items-center mb-12">
            <div>
                <h2 class="text-4xl font-black tracking-tighter">Gestion du <span class="text-orange-500">Slider Hero</span></h2>
                <p class="text-gray-400 mt-2">Ajoutez ou modifiez les bannières de la page d'accueil.</p>
            </div>
            <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="btn-primary">
                <i class="fas fa-plus"></i> Nouvelle Slide
            </button>
        </header>

        <?php if ($message): ?>
            <div class="mb-8 p-4 bg-green-500/10 border border-green-500/20 text-green-500 rounded-xl flex items-center gap-3">
                <i class="fas fa-check-circle"></i> <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-8 p-4 bg-red-500/10 border border-red-500/20 text-red-500 rounded-xl flex items-center gap-3">
                <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($slides as $slide): ?>
                <div class="glass-card flex flex-col gap-4">
                    <div class="relative h-48 rounded-xl overflow-hidden group">
                        <img src="../../<?= $slide['image_path'] ?>" alt="Slide" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-4">
                            <form method="POST" onsubmit="return confirm('Supprimer cette slide ?')">
                                <input type="hidden" name="slide_id" value="<?= $slide['id'] ?>">
                                <button type="submit" name="delete_slide" class="w-10 h-10 rounded-full bg-red-500 text-white flex items-center justify-center hover:scale-110 transition-transform"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                        <div class="absolute top-4 left-4 bg-black/60 backdrop-blur-md px-3 py-1 rounded-full text-[10px] font-bold uppercase">
                            Ordre: <?= $slide['display_order'] ?>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="font-bold text-lg leading-tight mb-2"><?= strip_tags($slide['title']) ?></h3>
                        <p class="text-xs text-gray-400 line-clamp-2"><?= htmlspecialchars($slide['subtitle']) ?></p>
                    </div>

                    <div class="mt-auto pt-4 border-t border-white/5 flex items-center justify-between">
                        <form method="POST" class="flex items-center gap-2">
                            <input type="hidden" name="slide_id" value="<?= $slide['id'] ?>">
                            <input type="hidden" name="status" value="<?= $slide['is_active'] ? 0 : 1 ?>">
                            <button type="submit" name="toggle_status" class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase transition-all <?= $slide['is_active'] ? 'bg-green-500/10 text-green-500 border border-green-500/20' : 'bg-gray-500/10 text-gray-400 border border-white/10' ?>">
                                <?= $slide['is_active'] ? 'Actif' : 'Inactif' ?>
                            </button>
                        </form>
                        
                        <form method="POST" class="flex items-center gap-2">
                            <input type="hidden" name="slide_id" value="<?= $slide['id'] ?>">
                            <input type="number" name="order" value="<?= $slide['display_order'] ?>" class="w-12 bg-white/5 border border-white/10 rounded px-1 py-1 text-center text-xs outline-none focus:border-orange-500" onchange="this.form.submit()">
                            <input type="hidden" name="update_order" value="1">
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Add Modal -->
        <div id="addModal" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-[1000] flex items-center justify-center p-4">
            <div class="glass-card w-full max-w-2xl">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-black">Ajouter une <span class="text-orange-500">Slide</span></h3>
                    <button onclick="document.getElementById('addModal').classList.add('hidden')" class="text-gray-500 hover:text-white"><i class="fas fa-times text-xl"></i></button>
                </div>
                <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input type="hidden" name="MAX_FILE_SIZE" value="134217728"> <!-- 128MB -->
                    <div class="col-span-full">
                        <label class="text-[10px] font-black uppercase text-gray-500 mb-1 block">Titre (HTML autorisé)</label>
                        <input type="text" name="title" required class="custom-input" placeholder="Titre principal">
                    </div>
                    <div class="col-span-full">
                        <label class="text-[10px] font-black uppercase text-gray-500 mb-1 block">Sous-titre</label>
                        <textarea name="subtitle" class="custom-input" rows="3" placeholder="Texte de description"></textarea>
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase text-gray-500 mb-1 block">Texte Bouton</label>
                        <input type="text" name="button_text" value="Rejoindre" class="custom-input">
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase text-gray-500 mb-1 block">Lien Bouton</label>
                        <input type="text" name="button_link" value="#" class="custom-input">
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase text-gray-500 mb-1 block">Image</label>
                        <input type="file" name="image" required class="custom-input" accept="image/*">
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase text-gray-500 mb-1 block">Ordre</label>
                        <input type="number" name="display_order" value="0" class="custom-input">
                    </div>
                    <div class="col-span-full pt-4">
                        <button type="submit" name="add_slide" class="btn-primary w-full justify-center">Enregistrer la Slide</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
