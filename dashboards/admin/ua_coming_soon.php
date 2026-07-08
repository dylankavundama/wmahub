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

// Action d'exportation CSV
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    $file = '../../uploads/waitlist_emails.txt';
    $entries = [];
    if (file_exists($file)) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $parts = explode(' | ', $line, 2);
            if (count($parts) === 2) {
                $entries[] = [
                    'date' => trim($parts[0]),
                    'email' => trim($parts[1])
                ];
            }
        }
    }
    
    // Nettoyer le tampon de sortie
    if (ob_get_length()) {
        ob_clean();
    }
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=waitlist_emails_' . date('Ymd_His') . '.csv');
    
    $output = fopen('php://output', 'w');
    // UTF-8 BOM pour Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    fputcsv($output, ['Date Inscription', 'Adresse Email']);
    
    foreach ($entries as $entry) {
        fputcsv($output, [$entry['date'], $entry['email']]);
    }
    
    fclose($output);
    exit;
}

// Actions d'enregistrement et suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_config'])) {
        $title = $_POST['title'] ?? 'Bientôt disponible';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        
        // Formater end_date s'il y a un format de date local HTML5 (ex: Y-m-d\TH:i)
        if ($end_date) {
            $end_date = str_replace('T', ' ', $end_date);
            if (strlen($end_date) === 16) { // Y-m-d H:i
                $end_date .= ':00';
            }
        }
        
        try {
            // Récupérer la config actuelle pour l'image
            $stmt = $db->query("SELECT * FROM ua_coming_soon ORDER BY id LIMIT 1");
            $current_config = $stmt->fetch();
            $image_url = $current_config ? $current_config['image_url'] : '';
            
            if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === 0) {
                $ext = pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION);
                $filename = 'banner_' . time() . '.' . $ext;
                $upload_dir = '../../uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $upload_path = $upload_dir . $filename;
                if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $upload_path)) {
                    $image_url = 'uploads/' . $filename;
                } else {
                    $error = "Erreur lors de l'upload de l'image de la bannière.";
                }
            } elseif (isset($_POST['image_url']) && !empty($_POST['image_url'])) {
                $image_url = $_POST['image_url'];
            }
            
            if (!$error) {
                if ($current_config) {
                    $up = $db->prepare("UPDATE ua_coming_soon SET title = ?, end_date = ?, is_active = ?, image_url = ? WHERE id = ?");
                    $up->execute([$title, $end_date, $is_active, $image_url, $current_config['id']]);
                } else {
                    $ins = $db->prepare("INSERT INTO ua_coming_soon (title, end_date, is_active, image_url) VALUES (?, ?, ?, ?)");
                    $ins->execute([$title, $end_date, $is_active, $image_url]);
                }
                $_SESSION['flash_message'] = "Configuration Coming Soon mise à jour avec succès !";
                header('Location: ua_coming_soon.php');
                exit;
            }
        } catch (Exception $e) {
            $error = "Erreur de base de données : " . $e->getMessage();
        }
    } elseif (isset($_POST['clear_waitlist'])) {
        $file = '../../uploads/waitlist_emails.txt';
        if (file_exists($file)) {
            file_put_contents($file, '');
        }
        $_SESSION['flash_message'] = "La liste d'attente a été réinitialisée.";
        header('Location: ua_coming_soon.php');
        exit;
    }
}

// Récupérer la config actuelle
$config = null;
try {
    $stmt = $db->query("SELECT * FROM ua_coming_soon ORDER BY id LIMIT 1");
    $config = $stmt->fetch();
} catch (Exception $e) {
    // Si la table n'existe pas encore
}

// Récupérer les inscriptions
$waitlist = [];
$file = '../../uploads/waitlist_emails.txt';
if (file_exists($file)) {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $parts = explode(' | ', $line, 2);
        if (count($parts) === 2) {
            $waitlist[] = [
                'date' => trim($parts[0]),
                'email' => trim($parts[1])
            ];
        }
    }
    // Inverser pour voir le plus récent en premier
    $waitlist = array_reverse($waitlist);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMA HUB (UA) - Configuration Coming Soon</title>
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
                <h2 class="text-4xl font-black tracking-tighter">Coming Soon <span class="text-orange-500">United Africa</span></h2>
                <p class="text-gray-400 mt-2">Configurez la date d'expiration de la bannière et gérez la liste d'attente.</p>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="mb-8 p-4 bg-green-500/10 border border-green-500/20 text-green-500 rounded-xl flex items-center gap-3">
                <i class="fas fa-check-circle"></i> <span><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="mb-8 p-4 bg-red-500/10 border border-red-500/20 text-red-500 rounded-xl flex items-center gap-3">
                <i class="fas fa-exclamation-circle"></i> <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Formulaire de Configuration -->
            <div class="lg:col-span-1">
                <div class="glass-card">
                    <h3 class="font-bold text-xl mb-6 flex items-center gap-2">
                        <i class="fas fa-cog text-orange-500"></i> Paramètres Bannière
                    </h3>
                    <form method="POST" enctype="multipart/form-data" class="flex flex-col gap-5">
                        <div>
                            <label class="text-xs text-gray-400 mb-1 block">Titre Coming Soon</label>
                            <input type="text" name="title" required value="<?= htmlspecialchars($config['title'] ?? 'Bientôt disponible') ?>" class="custom-input">
                        </div>

                        <div>
                            <label class="text-xs text-gray-400 mb-1 block">Image de la Bannière (Appareil)</label>
                            <input type="file" name="banner_image" accept="image/*" class="custom-input">
                            <p class="text-[10px] text-gray-500 mt-1">OU URL de l'image existante :</p>
                            <input type="text" name="image_url" placeholder="https://..." value="<?= htmlspecialchars($config['image_url'] ?? '') ?>" class="custom-input mt-2">
                        </div>

                        <?php if (!empty($config['image_url'])): ?>
                            <div>
                                <label class="text-xs text-gray-400 mb-1 block">Image actuelle :</label>
                                <div class="w-full h-32 rounded-xl overflow-hidden border border-white/10 relative bg-black/40">
                                    <?php 
                                    $imgSrc = $config['image_url'];
                                    if (!filter_var($imgSrc, FILTER_VALIDATE_URL)) {
                                        $imgSrc = '../../' . ltrim($imgSrc, '/');
                                    }
                                    ?>
                                    <img src="<?= htmlspecialchars($imgSrc) ?>" alt="Banner Preview" class="w-full h-full object-cover">
                                </div>
                            </div>
                        <?php endif; ?>

                        <div>
                            <label class="text-xs text-gray-400 mb-1 block">Date et Heure d'Expiration</label>
                            <?php 
                            $endDateVal = '';
                            if (!empty($config['end_date'])) {
                                $endDateVal = date('Y-m-d\TH:i', strtotime($config['end_date']));
                            }
                            ?>
                            <input type="datetime-local" name="end_date" value="<?= $endDateVal ?>" class="custom-input">
                            <p class="text-[10px] text-gray-500 mt-1">Laissez vide si le compte à rebours ou la bannière ne doit jamais expirer automatiquement.</p>
                        </div>

                        <div class="flex items-center gap-3 py-2">
                            <input type="checkbox" name="is_active" id="is_active" value="1" <?= (!isset($config['is_active']) || $config['is_active'] == 1) ? 'checked' : '' ?> class="rounded border-white/10 bg-white/5 text-orange-500 focus:ring-orange-500 w-4 h-4 cursor-pointer">
                            <label for="is_active" class="text-sm text-gray-300 cursor-pointer select-none">Bannière Active / Affichée</label>
                        </div>

                        <button type="submit" name="save_config" class="btn-primary justify-center mt-2">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                    </form>
                </div>
            </div>

            <!-- Liste d'attente / Subscriptions -->
            <div class="lg:col-span-2">
                <div class="glass-card">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                        <h3 class="font-bold text-xl flex items-center gap-2">
                            <i class="fas fa-envelope text-orange-500"></i> Liste d'attente (<?= count($waitlist) ?>)
                        </h3>
                        <div class="flex items-center gap-2">
                            <?php if (!empty($waitlist)): ?>
                                <a href="?action=export_csv" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 transition">
                                    <i class="fas fa-file-excel"></i> Exporter CSV
                                </a>
                                <form method="POST" onsubmit="return confirm('Êtes-vous absolument sûr de vouloir vider TOUTE la liste d\'attente ? Cette action est irréversible.')" class="inline-block">
                                    <button type="submit" name="clear_waitlist" class="bg-red-500/15 hover:bg-red-500/30 border border-red-500/30 text-red-400 px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 transition">
                                        <i class="fas fa-trash-alt"></i> Vider la liste
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="overflow-x-auto max-h-[500px] overflow-y-auto rounded-xl border border-white/5">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-white/5 text-gray-400 text-xs uppercase font-bold sticky top-0">
                                <tr>
                                    <th class="px-6 py-4">Date & Heure</th>
                                    <th class="px-6 py-4">Adresse Email</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php if (empty($waitlist)): ?>
                                    <tr>
                                        <td colspan="2" class="px-6 py-8 text-center text-gray-500">
                                            <i class="fas fa-envelope-open text-3xl mb-3 block"></i>
                                            Aucun email enregistré pour le moment.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($waitlist as $item): ?>
                                        <tr class="hover:bg-white/[0.02] transition-colors">
                                            <td class="px-6 py-4 text-xs text-gray-400 font-medium">
                                                <?= date('d/m/Y H:i:s', strtotime($item['date'])) ?>
                                            </td>
                                            <td class="px-6 py-4 text-white font-bold font-mono">
                                                <?= htmlspecialchars($item['email']) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
