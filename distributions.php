<?php
require_once __DIR__ . '/includes/config.php';

$db = getDBConnection();

// Récupérer les distributions actives
try {
    $stmt = $db->query("SELECT * FROM distributions WHERE status = 'active' ORDER BY created_at DESC");
    $distributions = $stmt->fetchAll();
} catch (Exception $e) {
    $distributions = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/asset/icon.png">
    <link rel="apple-touch-icon" href="/asset/icon.png">
    <title>Nos Distributions - WMA Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #0a0a0c;
            color: #fff;
            min-height: 100vh;
            overflow-x: hidden;
        }
        .bg-glow {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%);
            z-index: -1;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 1.5rem;
            transition: all 0.3s ease;
        }
        .glass-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 102, 0, 0.3);
            background: rgba(255, 255, 255, 0.05);
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            transition: all 0.3s ease;
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }
        .back-btn:hover {
            color: #ff6600;
        }
        .page-title {
            font-size: 3rem;
            font-weight: 900;
            letter-spacing: -0.05em;
            margin-bottom: 1rem;
        }
        .dist-image {
            width: 100%;
            aspect-ratio: 1/1;
            object-fit: cover;
            border-radius: 1rem;
        }
        .stream-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #ff6600;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.75rem;
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .stream-link:hover {
            background: #e65c00;
            transform: scale(1.05);
        }
    </style>
</head>
<body class="p-8 md:p-16">
    <div class="bg-glow"></div>
    
    <div class="max-w-7xl mx-auto">
        <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Retour à l'accueil</a>
        
        <header class="mb-16">
            <h1 class="page-title">Nos <span class="text-orange-500">Distributions</span></h1>
            <p class="text-gray-400 max-w-2xl">Découvrez les projets musicaux que nous avons propulsés sur les plateformes de streaming mondiales.</p>
        </header>

        <?php if (empty($distributions)): ?>
            <div class="glass-card p-12 text-center">
                <i class="fas fa-music text-4xl text-gray-700 mb-4"></i>
                <p class="text-gray-500">Aucune distribution affichée pour le moment.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php foreach ($distributions as $dist): ?>
                    <div class="glass-card p-4 flex flex-col">
                        <img src="<?= htmlspecialchars($dist['image_url']) ?>" alt="<?= htmlspecialchars($dist['title']) ?>" class="dist-image mb-4">
                        <h3 class="font-bold text-lg leading-tight mb-1"><?= htmlspecialchars($dist['title']) ?></h3>
                        <p class="text-orange-500 text-sm font-medium mb-4"><?= htmlspecialchars($dist['artist']) ?></p>
                        <div class="mt-auto">
                            <a href="<?= htmlspecialchars($dist['link']) ?>" target="_blank" class="stream-link w-full justify-center">
                                <i class="fab fa-spotify"></i> Écouter
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <section class="mt-24 py-12 border-t border-white/5 text-center">
            <h2 class="text-2xl font-black mb-4">Ce sont là nos plus gros projets déjà distribués</h2>
            <p class="text-gray-500 italic">"La musique n'a pas de frontières, WMA Hub non plus."</p>
        </section>

        <footer class="mt-24 pt-8 border-t border-white/5 flex flex-col md:flex-row justify-between items-center gap-4 text-sm text-gray-500">
                <p>© 2026 WMA Hub. Propulsé par l'innovation.</p>
            <p class="text-orange-500 font-bold uppercase tracking-widest text-[10px]">We move, WMAFam</p>
        </footer>
    </div>
</body>
</html>
