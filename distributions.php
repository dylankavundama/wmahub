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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #0a0a0c;
            color: #fff;
            min-height: 100vh;
            overflow-x: hidden;
            padding: 2rem;
        }

        @media (min-width: 768px) {
            body {
                padding: 4rem;
            }
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

        .container {
            max-width: 1280px;
            margin: 0 auto;
        }

        header {
            margin-bottom: 4rem;
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

        .page-title .text-accent {
            color: #ff6600;
        }

        .subtitle {
            color: #a0a0a6;
            max-width: 42rem;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 1.5rem;
            transition: all 0.3s ease;
            padding: 1rem;
            display: flex;
            flex-direction: column;
        }

        .glass-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 102, 0, 0.3);
            background: rgba(255, 255, 255, 0.05);
        }

        .distributions-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            margin-bottom: 6rem;
        }

        @media (min-width: 640px) {
            .distributions-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .distributions-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .dist-image-container {
            position: relative;
            width: 100%;
            aspect-ratio: 1 / 1;
            overflow: hidden;
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .dist-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .dist-image.error {
            display: none;
        }

        .image-icon {
            font-size: 3rem;
            color: #666;
            text-align: center;
            display: none;
        }

        .dist-image-container.has-error .image-icon {
            display: block;
        }

        .dist-title {
            font-weight: 700;
            font-size: 1.125rem;
            line-height: 1.2;
            margin-bottom: 0.25rem;
        }

        .dist-artist {
            color: #ff6600;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .mt-auto {
            margin-top: auto;
        }

        .stream-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: #ff6600;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.75rem;
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            width: 100%;
            border: none;
            cursor: pointer;
        }

        .stream-link:hover {
            background: #e65c00;
            transform: scale(1.05);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
        }

        .empty-state i {
            font-size: 2.25rem;
            color: #555;
            margin-bottom: 1rem;
            display: block;
        }

        .empty-text {
            color: #6b7280;
        }

        .section-divider {
            margin-top: 6rem;
            padding: 3rem 0;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            text-align: center;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 900;
            margin-bottom: 1rem;
        }

        .section-subtitle {
            color: #8b8b93;
            font-style: italic;
        }

        footer {
            margin-top: 6rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            flex-direction: column;
            gap: 1rem;
            font-size: 0.875rem;
            color: #8b8b93;
        }

        @media (min-width: 768px) {
            footer {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                gap: 1rem;
            }
        }

        .footer-brand {
            color: #ff6600;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-size: 0.625rem;
        }

        #pageLoader {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 10000;
            background: rgba(10, 10, 12, 0.96);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .loader-ring {
            width: 80px;
            height: 80px;
            border: 8px solid rgba(255, 255, 255, 0.1);
            border-top-color: #ff6600;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>
<body>
    <div id="pageLoader">
        <div class="loader-ring"></div>
    </div>
    <div class="bg-glow"></div>
    
    <div class="container">
        <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Retour à l'accueil</a>
        
        <header>
            <h1 class="page-title">Nos <span class="text-accent">Distributions</span></h1>
            <p class="subtitle">Découvrez les projets musicaux que nous avons propulsés sur les plateformes de streaming mondiales.</p>
        </header>

        <?php if (empty($distributions)): ?>
            <div class="glass-card empty-state">
                <i class="fas fa-music"></i>
                <p class="empty-text">Aucune distribution affichée pour le moment.</p>
            </div>
        <?php else: ?>
            <div class="distributions-grid">
                <?php foreach ($distributions as $dist): ?>
                    <div class="glass-card">
                        <div class="dist-image-container" data-image-container>
                            <img src="<?= htmlspecialchars($dist['image_url']) ?>" alt="<?= htmlspecialchars($dist['title']) ?>" class="dist-image" onerror="this.classList.add('error'); this.parentElement.classList.add('has-error');">
                            <div class="image-icon">
                                <i class="fas fa-compact-disc"></i>
                            </div>
                        </div>
                        <h3 class="dist-title"><?= htmlspecialchars($dist['title']) ?></h3>
                        <p class="dist-artist"><?= htmlspecialchars($dist['artist']) ?></p>
                        <div class="mt-auto">
                            <a href="<?= htmlspecialchars($dist['link']) ?>" target="_blank" class="stream-link">
                                <i class="fab fa-spotify"></i> Écouter
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <section class="section-divider">
            <h2 class="section-title">Ce sont là nos plus gros projets déjà distribués</h2>
            <p class="section-subtitle">"La musique n'a pas de frontières, WMA Hub non plus."</p>
        </section>

        <footer>
            <p>© 2026 WMA Hub. Propulsé par l'innovation.</p>
            <p class="footer-brand">We move, WMAFam</p>
        </footer>
    </div>
    <script>
        window.addEventListener('load', function() {
            var loader = document.getElementById('pageLoader');
            if (loader) {
                loader.style.display = 'none';
            }
        });
    </script>
</body>
</html>
