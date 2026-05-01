<?php require_once __DIR__ . '/includes/config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Politique de Confidentialité - WMA Hub</title>
    <link rel="icon" type="image/png" href="/asset/icon.png">
    <link rel="apple-touch-icon" href="/asset/icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; line-height: 1.6; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .legal-card { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 2rem; padding: 3rem; margin: 4rem auto; max-width: 1000px; box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.8); }
        h1 { font-weight: 900; letter-spacing: -2px; }
        h2 { color: #ff6600; font-weight: 700; margin-top: 2.5rem; margin-bottom: 1rem; border-left: 4px solid #ff6600; padding-left: 1rem; }
        p { color: rgba(255, 255, 255, 0.7); margin-bottom: 1.5rem; }
        .back-btn { display: inline-flex; align-items: center; gap: 0.5rem; color: #ff6600; font-weight: 600; transition: all 0.3s; margin-bottom: 2rem; }
        .back-btn:hover { transform: translateX(-5px); }
    </style>
</head>
<body>
    <div class="bg-glow"></div>
    <div class="container mx-auto px-4">
        <div class="legal-card">
            <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Retour à l'accueil</a>
            
            <header class="mb-12">
                <h1 class="text-4xl md:text-6xl font-black text-white mb-4">Politique de <span class="text-orange-500">Confidentialité</span></h1>
                <p class="text-gray-500 italic">Dernière mise à jour : 13 Janvier 2026</p>
            </header>

            <section>
                <h2>1. Collecte des Informations</h2>
                <p>Nous collectons les informations que vous nous fournissez directement : email, nom d'artiste, numéro de téléphone, et métadonnées musicales. Lors de la connexion via Google OAuth, nous récupérons uniquement votre nom, email et photo de profil publique.</p>
            </section>

            <section>
                <h2>2. Utilisation des Données</h2>
                <p>Vos données sont utilisées exclusivement pour :
                    <ul class="list-disc list-inside ml-4 text-gray-400 mb-4">
                        <li>Gérer votre compte et vos projets de distribution.</li>
                        <li>Vous notifier de l'avancement de vos projets.</li>
                        <li>Assurer le versement correct de vos revenus.</li>
                        <li>Améliorer nos services techniques.</li>
                    </ul>
                </p>
            </section>

            <section>
                <h2>3. Partage des Données</h2>
                <p>WMA Hub ne vend jamais vos données. Nous partageons uniquement les métadonnées musicales nécessaires avec les plateformes de streaming (Apple Music, Spotify, Deezer, etc.) pour assurer la distribution de vos œuvres.</p>
            </section>

            <section>
                <h2>4. Conservation des Données</h2>
                <p>Nous conservons vos informations aussi longtemps que nécessaire pour vous fournir nos services ou pour satisfaire à nos obligations légales et fiscales.</p>
            </section>

            <section>
                <h2>5. Sécurité</h2>
                <p>Nous mettons en œuvre des mesures de sécurité techniques et organisationnelles pour protéger vos données contre tout accès non autorisé, perte ou altération.</p>
            </section>

            <section>
                <h2>6. Vos Droits</h2>
                <p>Vous avez le droit d'accéder, de corriger ou de demander la suppression de vos données personnelles à tout moment en nous contactant à l'adresse suivante : <strong>info@wmahub.com</strong>.</p>
            </section>

            <footer class="mt-20 pt-8 border-t border-white/5 text-center">
                <p class="text-sm font-bold uppercase tracking-widest text-orange-500">We move, WMAFam</p>
                <p class="text-xs text-gray-600">© 2026 WMA Hub. Tous droits réservés.</p>
            </footer>
        </div>
    </div>
</body>
</html>
