<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/subscription_check.php';

// Sécurité : Accès restreint aux distributeurs
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'distributeur') {
    header('Location: ../../auth/login.php');
    exit;
}

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

// Statistiques Globales
// Nombre d'artistes
$stmt_artists = $db->prepare("SELECT COUNT(*) FROM distributor_artists WHERE distributor_id = ?");
$stmt_artists->execute([$userId]);
$total_artists = $stmt_artists->fetchColumn();

// Nombre de projets
$stmt_projects = $db->prepare("SELECT COUNT(*) FROM projects WHERE user_id = ?");
$stmt_projects->execute([$userId]);
$total_projects = $stmt_projects->fetchColumn();

// Total Streams et Revenus
$stmt_stats = $db->prepare("SELECT SUM(streams) as total_streams, SUM(revenue) as total_revenue FROM projects WHERE user_id = ?");
$stmt_stats->execute([$userId]);
$stats = $stmt_stats->fetch();
$total_streams = $stats['total_streams'] ?? 0;
$total_revenue = $stats['total_revenue'] ?? 0;

$pageTitle = 'Dashboard Distributeur - WMA Hub';
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
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; min-height: 100vh; margin: 0; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 0% 0%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 1.5rem; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; padding: 2rem 1.5rem; display: flex; flex-direction: column; }
        .main-content { margin-left: 280px; padding: 2rem; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; transform: translateX(5px); }
        .cert-btn { background: rgba(0, 184, 212, 0.1); color: #00e5ff; border: 1px solid rgba(0, 229, 255, 0.2); padding: 0.5rem 1rem; border-radius: 0.75rem; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; transition: all 0.3s ease; white-space: nowrap; }
        .cert-btn:hover { background: #00e5ff; color: #000; box-shadow: 0 0 20px rgba(0, 229, 255, 0.4); }
    </style>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8882238368661853"
     crossorigin="anonymous"></script>
</head>
<body>
    <div id="wma-global-loader"><div class="loader-spin"></div></div>
    <div class="bg-glow"></div>

    <aside class="sidebar">
        <div class="flex items-center gap-4 mb-12 px-2">
            <img src="../../asset/trans.png" alt="Logo" class="h-10">
            <div>
                <h1 class="text-xl font-black bg-gradient-to-r from-orange-500 to-orange-300 bg-clip-text text-transparent tracking-tighter leading-tight">WMA HUB</h1>
                <p class="text-[8px] text-gray-500 font-bold uppercase tracking-[1px] -mt-1 flex items-center gap-1">
                    Distributeur Indépendant
                    <?php if ($user['is_certified']): ?>
                        <img src="../../asset/img/verified-badge.png" alt="Certifié" class="w-3 h-3 inline-block" title="Compte Certifié">
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
        <!-- Premium Header -->
        <header class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12">
            <div>
                <div class="flex items-center gap-4 mb-2">
                    <h2 class="text-5xl font-black tracking-tighter leading-none flex items-center gap-3">
                        Hello, <span class="text-orange-500"><?= htmlspecialchars($user['name']) ?></span>
                        <?php if ($user['is_certified']): ?>
                            <img src="../../asset/img/verified-badge.png" alt="Certifié" class="w-10 h-10" title="Compte Certifié">
                        <?php endif; ?>
                    </h2>
                    <?php if (!$user['is_certified']): ?>
                        <a href="certify.php" class="cert-btn"><i class="fas fa-check-decagram mr-2"></i> Certifier mon compte</a>
                    <?php endif; ?>
                </div>
                <p class="text-gray-400 font-medium">Content de vous revoir sur votre centre de distribution <span class="text-white font-bold tracking-tight"><?= htmlspecialchars($user['distributor_name']) ?></span>.</p>
            </div>
            
            <div class="flex items-center gap-4 bg-white/5 p-4 rounded-3xl border border-white/10">
                <div class="w-12 h-12 rounded-2xl bg-orange-500 flex items-center justify-center text-white text-xl font-bold font-black shadow-lg shadow-orange-500/20">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div>
                    <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest leading-none mb-1">Date du jour</p>
                    <p class="text-sm font-black"><?= date('d F Y') ?></p>
                </div>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Artist Card -->
            <div class="lg:col-span-3">
                <div class="glass-card relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-orange-500/10 rounded-full blur-3xl -mr-32 -mt-32"></div>
                    
                    <div class="relative flex flex-col md:flex-row md:items-center gap-8">
                        <div class="w-32 h-32 rounded-3xl bg-gray-900 p-1 border-2 border-orange-500/20 overflow-hidden shadow-2xl">
                            <?php if ($user['distributor_logo']): ?>
                                <img src="uploads/<?= $user['distributor_logo'] ?>" class="w-full h-full object-cover rounded-2xl">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-3xl text-gray-700"><i class="fas fa-university"></i></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex-1">
                            <h3 class="text-3xl font-black mb-1 flex items-center gap-2">
                                <?= htmlspecialchars($user['distributor_name']) ?>
                                <?php if ($user['is_certified']): ?>
                                    <img src="../../asset/img/verified-badge.png" alt="Certifié" class="w-8 h-8" title="Organisation Certifiée">
                                <?php endif; ?>
                            </h3>
                            <p class="text-gray-500 font-medium flex items-center gap-2 mb-4">
                                <i class="fas fa-map-marker-alt text-orange-500"></i> <?= htmlspecialchars($user['distributor_city']) ?>
                            </p>
                            
                            <div class="flex flex-wrap gap-4">
                                <div class="bg-white/5 px-6 py-3 rounded-2xl border border-white/10 hover:border-orange-500/50 transition-all cursor-default group/stat">
                                    <p class="text-[10px] text-gray-600 font-bold uppercase tracking-tighter mb-1">Artistes</p>
                                    <p class="text-2xl font-black group-hover/stat:text-orange-500 transition-colors"><?= $total_artists ?></p>
                                </div>
                                <div class="bg-white/5 px-6 py-3 rounded-2xl border border-white/10 hover:border-orange-500/50 transition-all cursor-default group/stat">
                                    <p class="text-[10px] text-gray-600 font-bold uppercase tracking-tighter mb-1">Projets</p>
                                    <p class="text-2xl font-black group-hover/stat:text-orange-500 transition-colors"><?= $total_projects ?></p>
                                </div>
                                <div class="bg-white/5 px-6 py-3 rounded-2xl border border-white/10 hover:border-orange-500/50 transition-all cursor-default group/stat">
                                    <p class="text-[10px] text-gray-600 font-bold uppercase tracking-tighter mb-1">Streams</p>
                                    <p class="text-2xl font-black group-hover/stat:text-orange-500 transition-colors"><?= number_format($total_streams, 0, '.', ' ') ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
     <!-- Actions Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="glass-card p-6 border-orange-500/20 hover:border-orange-500/40 transition-all cursor-pointer group">
                        <div class="w-12 h-12 rounded-xl bg-orange-500/10 flex items-center justify-center text-orange-500 mb-4 group-hover:scale-110 transition-transform">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h4 class="text-lg font-bold mb-2">Ajouter un Artiste</h4>
                        <p class="text-xs text-gray-500">Commencez à gérer un nouvel artiste dans votre réseau.</p>
                    </div>
                    <div class="glass-card p-6 border-blue-500/20 hover:border-blue-500/40 transition-all cursor-pointer group">
                        <div class="w-12 h-12 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-500 mb-4 group-hover:scale-110 transition-transform">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <h4 class="text-lg font-bold mb-2">Nouvelle Distribution</h4>
                        <p class="text-xs text-gray-500">Envoyez de nouveaux morceaux sur les plateformes.</p>
                    </div>
                </div>
            </div>

            <aside class="space-y-8">
                <section class="glass-card p-6">
                    <h3 class="text-lg font-bold mb-6 flex items-center gap-2"><i class="fas fa-chart-line text-orange-500"></i> Mes Revenus</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-4 bg-white/5 rounded-xl border border-white/10">
                            <span class="text-sm text-gray-400">Streams</span>
                            <span class="text-xl font-bold"><?= number_format($total_streams, 0, '.', ' ') ?></span>
                        </div>
                        <div class="flex justify-between items-center p-4 bg-orange-500/10 rounded-xl border border-orange-500/20">
                            <span class="text-sm text-orange-500">Total Royalties</span>
                            <span class="text-xl font-black text-white"><?= number_format($total_revenue, 2, '.', ' ') ?> $</span>
                        </div>
                    </div>
                    <button class="w-full mt-6 py-3 bg-white/5 hover:bg-white/10 rounded-xl text-sm font-bold transition-all">Détails des paiements</button>
                </section>

                <section class="glass-card p-6">
                    <h3 class="text-lg font-bold mb-6 flex items-center gap-2"><i class="fas fa-info-circle text-blue-500"></i> Support Distributeur</h3>
                    <p class="text-xs text-gray-500 leading-relaxed mb-6">Besoin d'aide pour gérer votre label ou vos artistes ? Notre équipe dédiée est là pour vous accompagner.</p>
                    <a href="https://wa.me/<?= str_replace('+', '', $whatsappNumber) ?>" target="_blank" class="flex items-center justify-center gap-2 w-full py-3 bg-green-500/10 hover:bg-green-500/20 text-green-500 rounded-xl text-sm font-bold transition-all">
                        <i class="fab fa-whatsapp"></i> Contact Support
                    </a>
                </section>
            </aside>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.addEventListener('load', () => {
            const loader = document.getElementById('wma-global-loader');
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(() => loader.style.display = 'none', 500);
            }

            // Message de bienvenue pour distributeurs certifiés
            <?php if (isset($_SESSION['show_welcome_certified']) && $_SESSION['show_welcome_certified']): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Bienvenue <?= htmlspecialchars($user['name']) ?> !',
                    html: '<p class="text-lg">Votre compte <strong class="text-cyan-400">certifié</strong> vous donne accès à des fonctionnalités premium.</p>',
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    background: '#1a1a2e',
                    color: '#fff',
                    iconColor: '#00e5ff',
                    customClass: {
                        popup: 'rounded-3xl border border-cyan-400/20'
                    }
                });
                <?php unset($_SESSION['show_welcome_certified']); ?>
            <?php endif; ?>
        });
    </script>
</body>
</html>
