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

// Traitement des actions (Ajout, Suppr, Toggle Status)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST) && empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $error = "Le fichier est trop volumineux.";
    } elseif (isset($_POST['add_distribution'])) {
        $title = $_POST['title'];
        $artist = $_POST['artist'];
        $link = $_POST['link'];
        
        $image_url = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'dist_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], '../../uploads/' . $filename)) {
                $image_url = 'uploads/' . $filename;
            } else {
                $error = "Erreur lors de l'upload de l'image.";
            }
        } elseif (!empty($_POST['image_url'])) {
            $image_url = $_POST['image_url'];
        }

        if ($image_url && !$error) {
            $stmt = $db->prepare("INSERT INTO distributions (title, artist, image_url, link) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $artist, $image_url, $link]);
            $_SESSION['flash_message'] = "Distribution ajoutée avec succès !";
            header('Location: distributions.php');
            exit;
        } else if (!$error) {
            $error = "Veuillez fournir une image (fichier ou URL).";
        }
    } elseif (isset($_POST['delete_dist'])) {
        $id = (int)$_POST['dist_id'];
        $stmt = $db->prepare("DELETE FROM distributions WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['flash_message'] = "Distribution supprimée.";
        header('Location: distributions.php');
        exit;
    } elseif (isset($_POST['toggle_status'])) {
        $id = (int)$_POST['dist_id'];
        $status = $_POST['status'];
        $stmt = $db->prepare("UPDATE distributions SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        $_SESSION['flash_message'] = "Statut mis à jour.";
        header('Location: distributions.php');
        exit;
    }
}

// Récupérer les distributions
try {
    $stmt = $db->query("SELECT * FROM distributions ORDER BY created_at DESC");
    $distributions = $stmt->fetchAll();
} catch (PDOException $e) {
    $distributions = [];
    $error = "La table 'distributions' est introuvable. Veuillez exécuter la migration SQL. (" . $e->getMessage() . ")";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMA HUB - Gestion des Distributions</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; }
        .bg-glow { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; padding: 2rem 1.5rem; }
        .main-content { margin-left: 280px; padding: 2rem; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; text-decoration: none; margin-bottom: 0.5rem; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 1.5rem; padding: 1.5rem; }
        .custom-input { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 0.75rem; padding: 0.75rem 1rem; color: white; width: 100%; outline: none; }
        .btn-primary { background: #ff6600; color: white; padding: 0.75rem 1.5rem; border-radius: 0.75rem; font-weight: 600; display: inline-flex; align-items: center; gap: 0.5rem; }
    </style>
</head>
<body>
    <div class="bg-glow"></div>
    <aside class="sidebar" id="sidebar">
        <div class="flex items-center gap-4 mb-12 px-2">
            <img src="../../asset/trans.png" alt="Logo" class="h-10">
            <div>
                <h1 class="text-xl font-black bg-gradient-to-r from-orange-500 to-orange-300 bg-clip-text text-transparent tracking-tighter leading-tight">WMA HUB</h1>
                <p class="text-[8px] text-gray-500 font-bold uppercase tracking-[1px] -mt-1">We move, WMAFam</p>
            </div>
        </div>
        <nav class="flex-1" style="overflow-y:auto;overflow-x:hidden;padding-right:0.5rem;">
            <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>"><i class="fas fa-layer-group"></i> Gestion Projets</a>
            <a href="subscriptions.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'subscriptions.php' ? 'active' : '' ?>"><i class="fas fa-crown"></i> Abonnements</a>
            <a href="payments.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'payments.php' ? 'active' : '' ?>"><i class="fas fa-history"></i> Paiements</a>
            <a href="artists.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'artists.php' ? 'active' : '' ?>"><i class="fas fa-microphone-alt"></i> Artistes</a>
            <a href="distributors.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'distributors.php' ? 'active' : '' ?>"><i class="fas fa-truck-loading"></i> Distributeurs</a>
            <a href="revenues.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'revenues.php' ? 'active' : '' ?>"><i class="fas fa-wallet"></i> Revenus Gérés</a>
            <a href="payouts.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'payouts.php' ? 'active' : '' ?>"><i class="fas fa-money-bill-transfer"></i> Retraits &amp; Payouts</a>
            <a href="employees.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'employees.php' ? 'active' : '' ?>"><i class="fas fa-users-cog"></i> Équipe &amp; Staff</a>
            <a href="tasks.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'tasks.php' ? 'active' : '' ?>"><i class="fas fa-tasks"></i> Gestion Tâches</a>
            <a href="salaries.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'salaries.php' ? 'active' : '' ?>"><i class="fas fa-money-check-alt"></i> Gestion Salaires</a>
            <a href="project_files.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'project_files.php' ? 'active' : '' ?>"><i class="fas fa-folder-open"></i> Fichier Projet</a>
            <a href="service_cards.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'service_cards.php' ? 'active' : '' ?>"><i class="fas fa-id-card"></i> Cartes de Service</a>
            <a href="notifications.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'notifications.php' ? 'active' : '' ?>"><i class="fas fa-bell"></i> Notifications</a>
            <a href="finance.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'finance.php' ? 'active' : '' ?>"><i class="fas fa-chart-pie"></i> Rapports Financiers</a>
            <a href="site_stats.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'site_stats.php' ? 'active' : '' ?>"><i class="fas fa-chart-line"></i> Statistiques Site</a>
            <a href="comptabilite.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'comptabilite.php' ? 'active' : '' ?>"><i class="fas fa-calculator"></i> Comptabilité</a>
            <a href="hero_slider.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'hero_slider.php' ? 'active' : '' ?>"><i class="fas fa-images"></i> Gestion Slider</a>
            <a href="distributions.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'distributions.php' ? 'active' : '' ?>"><i class="fas fa-music"></i> Distributions Vitrine</a>
            <a href="users.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>"><i class="fas fa-user-friends"></i> Utilisateurs</a>
            <?php if ($_SESSION['role'] === 'superadmin'): ?>
                <div class="mt-4 pt-4 border-t border-white/5">
                    <p class="text-[9px] text-yellow-500/50 font-black uppercase tracking-widest px-4 mb-2">Master Controls</p>
                    <a href="../superadmin/index.php" class="nav-link !text-yellow-500 hover:!bg-yellow-500/10"><i class="fas fa-crown"></i> Console Superadmin</a>
                </div>
            <?php endif; ?>
        </nav>
        <div class="mt-auto pt-6 border-t border-white/5">
            <div class="flex items-center gap-4 mb-4 px-2">
                <div class="w-10 h-10 rounded-full bg-orange-500/10 border border-orange-500/20 flex items-center justify-center text-orange-500"><i class="fas fa-user-shield"></i></div>
                <div>
                    <p class="text-sm font-bold text-white"><?= explode(' ', $_SESSION['user_name'])[0] ?></p>
                    <p class="text-[10px] text-gray-500 uppercase font-black tracking-widest"><?= $_SESSION['role'] ?></p>
                </div>
            </div>
            <a href="../../auth/logout.php" class="nav-link !text-red-500 hover:!bg-red-500/10"><i class="fas fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="flex justify-between items-center mb-12">
            <div>
                <h2 class="text-4xl font-black tracking-tighter">Gestion des <span class="text-orange-500">Distributions</span></h2>
                <p class="text-gray-400 mt-2">Gérez les chansons et projets publiés.</p>
            </div>
            <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="btn-primary">
                <i class="fas fa-plus"></i> Nouveau Projet
            </button>
        </header>

        <?php if ($message): ?>
            <div class="mb-8 p-4 bg-green-500/10 border border-green-500/20 text-green-500 rounded-xl"><?= $message ?></div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($distributions as $dist): ?>
                <div class="glass-card">
                    <img src="../../<?= $dist['image_url'] ?>" class="w-full h-48 object-cover rounded-xl mb-4">
                    <h3 class="font-bold text-lg"><?= htmlspecialchars($dist['title']) ?></h3>
                    <p class="text-orange-500 text-sm mb-4"><?= htmlspecialchars($dist['artist']) ?></p>
                    <div class="flex justify-between items-center">
                        <a href="<?= htmlspecialchars($dist['link']) ?>" target="_blank" class="text-xs text-gray-500 hover:text-white underline">Lien de stream</a>
                        <form method="POST" onsubmit="return confirm('Supprimer ?')">
                            <input type="hidden" name="dist_id" value="<?= $dist['id'] ?>">
                            <button type="submit" name="delete_dist" class="text-red-500 hover:scale-110"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Modal -->
        <div id="addModal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-[1000]">
            <div class="glass-card w-full max-w-md">
                <h3 class="text-2xl font-black mb-6">Ajouter une <span class="text-orange-500">Distribution</span></h3>
                <form method="POST" enctype="multipart/form-data" class="flex flex-col gap-4">
                    <input type="text" name="title" placeholder="Titre du projet" required class="custom-input">
                    <input type="text" name="artist" placeholder="Nom de l'artiste" required class="custom-input">
                    <input type="text" name="link" placeholder="Lien de streaming (Spotify, etc.)" required class="custom-input">
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Image de couverture</label>
                        <input type="file" name="image" class="custom-input" accept="image/*">
                        <p class="text-[10px] text-gray-400 mt-1">OU URL de l'image :</p>
                        <input type="text" name="image_url" placeholder="https://..." class="custom-input mt-1">
                    </div>
                    <div class="flex gap-2 mt-4">
                                <button type="button" onclick="hideAddModal()" class="bg-white/10 px-6 py-2 rounded-xl flex-1">Annuler</button>
                        <button type="submit" name="add_distribution" class="btn-primary flex-1 justify-center">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <script>
        function showAddModal() {
            document.getElementById('addModal').classList.remove('hidden');
        }
        function hideAddModal() {
            document.getElementById('addModal').classList.add('hidden');
        }
        var addDistributionForm = document.getElementById('addDistributionForm');
        if (addDistributionForm) {
            addDistributionForm.addEventListener('submit', function(event) {
                var btn = this.querySelector('button[type="submit"]');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';
                }
            });
        }
    </script>
</body>
</html>
