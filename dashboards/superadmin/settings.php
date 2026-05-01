<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint au superadmin uniquement
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = getDBConnection();
$success = '';
$error = '';

// Traitement des mises à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    try {
        $db->beginTransaction();
        foreach ($_POST['settings'] as $key => $value) {
            $stmt = $db->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        }
        $db->commit();
        $success = "Paramètres mis à jour avec succès.";
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Erreur lors de la mise à jour : " . $e->getMessage();
    }
}

// Récupérer les paramètres groupés par catégorie
$settings_raw = $db->query("SELECT * FROM site_settings ORDER BY category, setting_key")->fetchAll();
$settings_by_cat = [];
foreach ($settings_raw as $s) {
    $settings_by_cat[$s['category']][] = $s;
}

$pageTitle = 'Paramètres Système - WMA HUB';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="/asset/icon.png"><link rel="apple-touch-icon" href="/asset/icon.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #050507; color: #fff; min-height: 100vh; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 50% 50%, #10101a 0%, #050507 100%); z-index: -1; }
        .sidebar { width: 300px; background: rgba(255, 255, 255, 0.01); backdrop-filter: blur(30px); border-right: 1px solid rgba(255, 255, 255, 0.03); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; padding: 2.5rem 2rem; display: flex; flex-direction: column; }
        .main-content { margin-left: 300px; padding: 4rem; }
        .nav-link { display: flex; align-items: center; gap: 1.25rem; padding: 1.15rem 1.5rem; color: rgba(255, 255, 255, 0.3); border-radius: 1.25rem; font-weight: 500; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); margin-bottom: 0.75rem; text-decoration: none; font-size: 0.95rem; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 215, 0, 0.05); color: #ffd700; transform: translateX(8px); }
        .nav-link.active { border-right: 3px solid #ffd700; border-radius: 1.25rem 0 0 1.25rem; }
        .glass-card { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 2.5rem; padding: 3rem; }
        .input-glass { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 1.25rem; color: #fff; padding: 1.15rem 1.5rem; width: 100%; outline: none; transition: all 0.3s ease; }
        .input-glass:focus { border-color: #ffd700; background: rgba(255, 255, 255, 0.06); box-shadow: 0 0 20px rgba(255, 215, 0, 0.1); }
        .save-btn { background: linear-gradient(135deg, #ffd700 0%, #ffaa00 100%); color: #000; font-weight: 800; border-radius: 1.25rem; padding: 1.25rem 2.5rem; transition: all 0.3s ease; text-transform: uppercase; letter-spacing: 1px; }
        .save-btn:hover { transform: translateY(-3px); box-shadow: 0 15px 30px -5px rgba(255, 215, 0, 0.4); filter: brightness(1.1); }
    </style>
</head>
<body>
    <div class="bg-glow"></div>

    <aside class="sidebar">
        <div class="flex items-center gap-4 mb-20 px-2">
            <img src="../../asset/trans.png" alt="Logo" class="h-12">
            <div>
                <h1 class="text-2xl font-black bg-gradient-to-r from-yellow-400 to-yellow-200 bg-clip-text text-transparent tracking-tighter leading-tight">SUPERADMIN</h1>
                <p class="text-[9px] text-gray-500 font-bold uppercase tracking-[2px] -mt-1">Control Center</p>
            </div>
        </div>
        
        <nav class="flex-1">
            <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>"><i class="fas fa-th-large"></i> Vue d'ensemble</a>
            <a href="revenues.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'revenues.php' ? 'active' : '' ?>"><i class="fas fa-wallet"></i> Analyse Revenus</a>
            <a href="payments.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'payments.php' ? 'active' : '' ?>"><i class="fas fa-history"></i> Historique Paiements</a>
            <a href="payment_logs.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'payment_logs.php' ? 'active' : '' ?>"><i class="fas fa-file-alt"></i> Logs Paiement</a>
            <a href="artists.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'artists.php' ? 'active' : '' ?>"><i class="fas fa-microphone-alt"></i> Artistes</a>
            <a href="distributors.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'distributors.php' ? 'active' : '' ?>"><i class="fas fa-truck-loading"></i> Distributeurs</a>
            <a href="admins.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'admins.php' ? 'active' : '' ?>"><i class="fas fa-user-shield"></i> Gestion des Admins</a>
            <a href="settings.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : '' ?>"><i class="fas fa-cogs"></i> Paramètres</a>
            <a href="../admin/index.php" class="nav-link"><i class="fas fa-arrow-left"></i> Retour au Panel Admin</a>
        </nav>

        <div class="mt-auto pt-8 border-t border-white/5">
            <a href="../../auth/logout.php" class="nav-link !text-red-500 hover:!bg-red-500/5"><i class="fas fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="mb-16 flex justify-between items-end">
            <div>
                <h2 class="text-6xl font-black tracking-tighter leading-none">Réglages <span class="text-yellow-500">Système</span></h2>
                <p class="text-gray-500 mt-4 text-xl">Pilotez les tarifs et les frais de la plateforme en temps réel.</p>
            </div>
        </header>

        <?php if ($success): ?>
            <div class="bg-green-500/10 border border-green-500/20 text-green-500 p-6 rounded-3xl mb-12 flex items-center gap-4">
                <i class="fas fa-check-circle text-2xl"></i>
                <p class="font-bold"><?= $success ?></p>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-6 rounded-3xl mb-12 flex items-center gap-4">
                <i class="fas fa-exclamation-triangle text-2xl"></i>
                <p class="font-bold"><?= $error ?></p>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="update_settings" value="1">
            
            <div class="space-y-12">
                <?php foreach ($settings_by_cat as $category => $items): ?>
                    <section>
                        <h3 class="text-[11px] text-yellow-500 font-black uppercase tracking-[4px] mb-8 flex items-center gap-4">
                            <?= $category ?>
                            <div class="h-[1px] flex-1 bg-yellow-500/10"></div>
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <?php foreach ($items as $s): ?>
                                <div class="glass-card !p-8">
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-4">
                                        <?= htmlspecialchars($s['description']) ?>
                                    </label>
                                    <input type="text" name="settings[<?= $s['setting_key'] ?>]" 
                                           class="input-glass" 
                                           value="<?= htmlspecialchars($s['setting_value']) ?>">
                                    <p class="text-[9px] text-gray-600 mt-2 font-mono uppercase tracking-tighter">Key: <?= $s['setting_key'] ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>

            <div class="mt-16 pt-12 border-t border-white/5 flex justify-end">
                <button type="submit" class="save-btn flex items-center gap-3">
                    <i class="fas fa-save"></i> Enregistrer les modifications
                </button>
            </div>
        </form>
    </main>
</body>
</html>
