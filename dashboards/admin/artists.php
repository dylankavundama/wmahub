<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint à l'admin uniquement
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = getDBConnection();

// Traitement du toggle d'activation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $userId = (int)$_POST['user_id'];
    $newStatus = (int)$_POST['new_status'];
    
    $stmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role = 'artiste'");
    $stmt->execute([$newStatus, $userId]);
    
    header('Location: artists.php');
    exit;
}

// Traitement du toggle d'artiste UA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_ua'])) {
    $userId = (int)$_POST['user_id'];
    $newUaStatus = (int)$_POST['new_ua_status'];
    
    // Vérifier si un enregistrement existe déjà dans ua_artists pour cet utilisateur
    $stmt = $db->prepare("SELECT id FROM ua_artists WHERE user_id = ?");
    $stmt->execute([$userId]);
    $uaArtist = $stmt->fetch();
    
    if ($uaArtist) {
        // Enregistrement existant : mettre à jour le statut is_ua
        $stmt = $db->prepare("UPDATE ua_artists SET is_ua = ? WHERE user_id = ?");
        $stmt->execute([$newUaStatus, $userId]);
    } else {
        // Enregistrement inexistant : créer un nouvel enregistrement
        // Récupérer les informations de l'artiste depuis la table users
        $stmt = $db->prepare("SELECT name, photo_url FROM users WHERE id = ? AND role = 'artiste'");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user) {
            $name = $user['name'];
            $photo_url = $user['photo_url'] ?? null;
            // Insérer dans ua_artists
            $stmt = $db->prepare("INSERT INTO ua_artists (name, photo_url, is_ua, user_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $photo_url, $newUaStatus, $userId]);
        }
    }
    
    header('Location: artists.php');
    exit;
}

// Récupérer les artistes avec statistiques et statut UA
$query = "SELECT u.*, 
                 (SELECT COUNT(*) FROM projects WHERE user_id = u.id) as project_count,
                 s.status as sub_status, 
                 s.end_date as sub_end_date,
                 ua.is_ua
          FROM users u 
          LEFT JOIN (
              SELECT user_id, status, end_date
              FROM subscriptions
              WHERE id IN (SELECT MAX(id) FROM subscriptions GROUP BY user_id)
          ) s ON u.id = s.user_id
          LEFT JOIN ua_artists ua ON u.id = ua.user_id
          WHERE u.role = 'artiste'
          ORDER BY u.created_at DESC";

$artists = $db->query($query)->fetchAll();

$pageTitle = 'Gestion des Artistes - Admin';
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
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c !important; color: #fff; min-height: 100vh; margin: 0; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; padding: 2rem 1.5rem; display: flex; flex-direction: column; }
        .main-content { margin-left: 280px; padding: 2rem; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; transform: translateX(5px); }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 1.5rem; padding: 2rem; }
        .admin-table { width: 100%; border-collapse: separate; border-spacing: 0 0.5rem; }
        .admin-table th { padding: 1rem; color: rgba(255, 255, 255, 0.4); font-size: 0.75rem; text-transform: uppercase; font-weight: 800; letter-spacing: 1px; }
        .admin-table tr td { padding: 1.25rem 1rem; background: rgba(255, 255, 255, 0.02); border-top: 1px solid rgba(255, 255, 255, 0.05); border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .admin-table tr td:first-child { border-left: 1px solid rgba(255, 255, 255, 0.05); border-radius: 1rem 0 0 1rem; }
        .admin-table tr td:last-child { border-right: 1px solid rgba(255, 255, 255, 0.05); border-radius: 0 1rem 1rem 0; }
        .status-badge { padding: 0.35rem 0.75rem; border-radius: 99px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .btn-toggle { padding: 0.5rem 1rem; border-radius: 0.75rem; font-size: 0.75rem; font-weight: 700; transition: all 0.3s; }
        #wma-global-loader { position: fixed; inset: 0; background: #0a0a0c; display: flex; align-items: center; justify-content: center; z-index: 100000; transition: opacity 0.5s ease; }
        .loader-spin { width: 40px; height: 40px; border: 3px solid rgba(255, 102, 0, 0.1); border-top-color: #ff6600; border-radius: 50%; animation: wma-spin 1s linear infinite; }
        @keyframes wma-spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div id="wma-global-loader"><div class="loader-spin"></div></div>
    <div class="bg-glow"></div>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <header class="mb-12">
            <h2 class="text-4xl font-black tracking-tighter">Gestion <span class="text-orange-500">Artistes</span></h2>
            <p class="text-gray-400 mt-2">Suivez les activités, abonnements et accès des artistes de la plateforme.</p>
        </header>

        <!-- Barre de recherche -->
        <div class="mb-6 flex max-w-md bg-white/5 border border-white/10 rounded-xl px-4 py-3 items-center gap-3">
            <i class="fas fa-search text-gray-400"></i>
            <input type="text" id="artistSearch" placeholder="Rechercher par nom ou email..." class="bg-transparent text-white placeholder-gray-500 text-sm outline-none w-full">
        </div>

        <div class="glass-card p-0 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="admin-table text-left">
                    <thead>
                        <tr>
                            <th class="px-8">Artiste</th>
                            <th>Projets</th>
                            <th>Abonnement</th>
                            <th>Expiration</th>
                            <th>Accès</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($artists as $a): 
                            $isActive = (bool)$a['is_active'];
                            $isUa = isset($a['is_ua']) ? (bool)$a['is_ua'] : false;
                            $subStatus = $a['sub_status'] ?? 'aucun';
                            $subColor = match($subStatus) {
                                'active' => 'bg-green-500/10 text-green-500',
                                'expired' => 'bg-red-500/10 text-red-500',
                                default => 'bg-gray-500/10 text-gray-500'
                            };
                        ?>
                            <tr>
                                <td class="px-8">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-full bg-orange-500/10 flex items-center justify-center border border-orange-500/20 text-orange-500">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <div class="font-bold text-white flex items-center gap-2">
                                                <?= htmlspecialchars($a['name']) ?>
                                                <?php if ($isUa): ?>
                                                    <span class="text-[9px] bg-orange-500/20 text-orange-500 px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">UA</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-[9px] text-gray-500 uppercase tracking-tighter"><?= htmlspecialchars($a['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <span class="text-white font-black"><?= $a['project_count'] ?></span>
                                        <span class="text-[10px] text-gray-500 uppercase font-bold">Sorties</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?= $subColor ?>">
                                        <?= $subStatus === 'active' ? 'ACTIF' : ($subStatus === 'expired' ? 'EXPIRÉ' : 'AUCUN') ?>
                                    </span>
                                </td>
                                <td class="text-xs text-gray-400">
                                    <?= $a['sub_end_date'] ? date('d/m/Y', strtotime($a['sub_end_date'])) : '-' ?>
                                </td>
                                <td>
                                    <span class="status-badge <?= $isActive ? 'bg-blue-500/10 text-blue-400' : 'bg-red-500/10 text-red-400' ?>">
                                        <?= $isActive ? 'ACTIF' : 'SUSPENDU' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <!-- Statut Actif/Suspendu -->
                                        <form method="POST" onsubmit="return confirm('Voulez-vous vraiment changer l\'état de cet artiste ?')">
                                            <input type="hidden" name="user_id" value="<?= $a['id'] ?>">
                                            <input type="hidden" name="new_status" value="<?= $isActive ? 0 : 1 ?>">
                                            <button type="submit" name="toggle_status" class="btn-toggle <?= $isActive ? 'bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white' : 'bg-green-500/10 text-green-500 hover:bg-green-500 hover:text-white' ?>">
                                                <i class="fas <?= $isActive ? 'fa-user-slash' : 'fa-user-check' ?> mr-2"></i>
                                                <?= $isActive ? 'Suspendre' : 'Rétablir' ?>
                                            </button>
                                        </form>

                                        <!-- Statut Artiste UA -->
                                        <form method="POST" onsubmit="return confirm('Voulez-vous vraiment modifier le statut Artiste UA de cet artiste ?')">
                                            <input type="hidden" name="user_id" value="<?= $a['id'] ?>">
                                            <input type="hidden" name="new_ua_status" value="<?= $isUa ? 0 : 1 ?>">
                                            <button type="submit" name="toggle_ua" class="btn-toggle <?= $isUa ? 'bg-orange-500/10 text-orange-500 hover:bg-orange-500 hover:text-white' : 'bg-gray-500/10 text-gray-400 hover:bg-gray-500 hover:text-white' ?>">
                                                <i class="fas fa-microphone-alt mr-2"></i>
                                                <?= $isUa ? 'Retirer UA' : 'Promouvoir UA' ?>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($artists)): ?>
                            <tr><td colspan="6" class="text-center py-20 text-gray-500 italic">Aucun artiste enregistré.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        window.addEventListener('load', () => {
            const loader = document.getElementById('wma-global-loader');
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(() => loader.style.display = 'none', 500);
            }
        });

        // Filtrage de la table des artistes
        const searchInput = document.getElementById('artistSearch');
        const tableRows = document.querySelectorAll('.admin-table tbody tr');

        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            let visibleCount = 0;
            let noMatchRow = document.getElementById('no-match-row');
            
            tableRows.forEach(row => {
                if (row.id === 'no-match-row') return;
                if (row.cells.length < 6) return; // ignorer la ligne vide par défaut

                const content = row.cells[0].textContent.toLowerCase();
                if (content.includes(query)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (visibleCount === 0 && query !== '') {
                if (!noMatchRow) {
                    noMatchRow = document.createElement('tr');
                    noMatchRow.id = 'no-match-row';
                    noMatchRow.innerHTML = `<td colspan="6" class="text-center py-20 text-gray-500 italic">Aucun artiste ne correspond à votre recherche.</td>`;
                    document.querySelector('.admin-table tbody').appendChild(noMatchRow);
                } else {
                    noMatchRow.style.display = '';
                }
            } else if (noMatchRow) {
                noMatchRow.style.display = 'none';
            }
        });
    </script>
</body>
</html>
