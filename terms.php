<?php require_once __DIR__ . '/includes/config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conditions d'Utilisation - WMA Hub</title>
    <link rel="icon" type="image/jpeg" href="asset/placeholder.jpg">
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
                <h1 class="text-4xl md:text-6xl font-black text-white mb-4">Conditions <span class="text-orange-500">d'Utilisation</span></h1>
                <p class="text-gray-500 italic">Dernière mise à jour : 13 Janvier 2026</p>
            </header>

            <section>
                <h2>1. Acceptation des Conditions</h2>
                <p>En accédant et en utilisant la plateforme WMA Hub, vous acceptez d'être lié par les présentes conditions d'utilisation. Si vous n'acceptez pas ces conditions, veuillez ne pas utiliser nos services.</p>
            </section>

            <section>
                <h2>2. Services de Distribution</h2>
                <p>WMA Hub fournit une plateforme de distribution musicale permettant aux artistes de diffuser leurs œuvres sur les plateformes de streaming mondiales. En soumettant votre projet, vous garantissez détenir l'intégralité des droits d'auteur ou avoir obtenu toutes les autorisations nécessaires.</p>
            </section>

            <section>
                <h2>3. Compte Utilisateur</h2>
                <p>Pour accéder à certaines fonctionnalités, vous devez vous connecter via votre compte Google. Vous êtes responsable du maintien de la confidentialité de vos accès et de toutes les activités effectuées sous votre compte.</p>
            </section>

            <section>
                <h2>4. Contenu et Droits</h2>
                <p>Vous conservez la propriété de votre musique. En utilisant WMA Hub, vous nous accordez une licence non-exclusive pour distribuer votre contenu sur les plateformes de vente et de streaming sélectionnées.</p>
            </section>

            <section>
                <h2>5. Paiements et Commissions</h2>
                <p>Les tarifs de distribution sont définis selon les "Packs" choisis. Les paiements sont effectués via les canaux sécurisés indiqués. WMA Hub s'engage à reverser les royalties perçues selon les termes du pack sélectionné.</p>
            </section>

            <section>
                <h2>6. Limitation de Responsabilité</h2>
                <p>WMA Hub ne saurait être tenu responsable des retards de mise en ligne causés par des tiers (plateformes de streaming) ou de toute perte indirecte de revenus.</p>
            </section>

            <section>
                <h2>7. Modification des Conditions</h2>
                <p>Nous nous réservons le droit de modifier ces conditions à tout moment. Les modifications prendront effet dès leur publication sur cette page.</p>
            </section>

            <footer class="mt-20 pt-8 border-t border-white/5 text-center">
                <p class="text-sm font-bold uppercase tracking-widest text-orange-500">We move, WMAFam</p>
                <p class="text-xs text-gray-600">© 2026 WMA Hub. Tous droits réservés.</p>
            </footer>
        </div>
    </div>
</body>
</html>
