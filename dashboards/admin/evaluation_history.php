<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint à l'admin uniquement
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = getDBConnection();

// Récupérer tous les awards groupés par mois
$stmt = $db->query("SELECT a.*, u.name as employee_name, u.email as employee_email 
                   FROM monthly_awards a 
                   JOIN users u ON a.employee_id = u.id 
                   ORDER BY a.month DESC, a.position ASC");
$awards = $stmt->fetchAll(PDO::FETCH_GROUP);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des Évaluations - WMA HUB</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 2rem; padding: 2rem; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; display: flex; flex-direction: column; padding: 2rem 1.5rem; }
        .main-content { margin-left: 280px; padding: 3rem; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; }
        .medal-1 { color: #ffd700; }
        .medal-2 { color: #c0c0c0; }
        .medal-3 { color: #cd7f32; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <header class="mb-12">
            <h2 class="text-4xl font-black text-white">Archives de <span class="text-orange-500">Performance</span></h2>
            <p class="text-gray-400 mt-2">Retrouvez les meilleurs employés des mois précédents.</p>
        </header>

        <div class="space-y-12">
            <?php if (empty($awards)): ?>
                <div class="glass-card text-center py-20">
                    <i class="fas fa-history text-5xl text-white/10 mb-6"></i>
                    <p class="text-gray-500">Aucun historique disponible pour le moment.</p>
                </div>
            <?php else: ?>
                <?php foreach ($awards as $month => $monthAwards): ?>
                    <section>
                        <h3 class="text-2xl font-black mb-6 flex items-center gap-3">
                            <span class="text-orange-500 capitalize"><?= date('F Y', strtotime($month . '-01')) ?></span>
                            <div class="h-[1px] flex-1 bg-white/5"></div>
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <?php foreach ($monthAwards as $award): ?>
                                <div class="glass-card relative overflow-hidden group">
                                    <div class="absolute -right-4 -top-4 text-6xl opacity-5 group-hover:opacity-10 transition-opacity">
                                        <i class="fas fa-award"></i>
                                    </div>
                                    <div class="flex flex-col items-center text-center">
                                        <div class="w-16 h-16 rounded-full bg-white/5 flex items-center justify-center text-3xl mb-4 medal-<?= $award['position'] ?>">
                                            <?php if($award['position'] == 1) echo '🥇'; 
                                                  elseif($award['position'] == 2) echo '🥈'; 
                                                  else echo '🥉'; ?>
                                        </div>
                                        <h4 class="text-lg font-bold mb-1"><?= htmlspecialchars($award['employee_name']) ?></h4>
                                        <p class="text-xs text-gray-500 mb-4"><?= htmlspecialchars($award['employee_email']) ?></p>
                                        <div class="px-4 py-1 bg-orange-500/10 text-orange-500 rounded-full text-xs font-black">
                                            SCORE: <?= $award['score'] ?>/5
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
