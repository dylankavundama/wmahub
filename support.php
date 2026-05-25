<?php
// WMA Hub - Support Page
$statusMsg = '';
$statusClass = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Simple basic validation
    $name = htmlspecialchars($_POST['name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $message = htmlspecialchars($_POST['message'] ?? '');

    if (!empty($name) && !empty($email) && !empty($message)) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Here you would typically send an email
            // mail("support@yourdomain.com", "Support Request: $name", $message, "From: $email");
            $statusMsg = "Merci pour votre message, $name. Notre équipe d'assistance vous répondra dans les plus brefs délais.";
            $statusClass = "success";
        } else {
            $statusMsg = "Veuillez entrer une adresse e-mail valide.";
            $statusClass = "error";
        }
    } else {
        $statusMsg = "Veuillez remplir tous les champs.";
        $statusClass = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assistance - WMA Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0a0a0c;
            --card-bg: rgba(255, 255, 255, 0.03);
            --card-border: rgba(255, 255, 255, 0.08);
            --primary: #ff6600;
            --primary-hover: #e55c00;
            --text-main: #ffffff;
            --text-muted: #a1a1aa;
            --success: #4ade80;
            --error: #f87171;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
            flex-grow: 1;
        }

        header {
            text-align: center;
            margin-bottom: 50px;
            animation: fadeIn 0.8s ease-out;
        }

        .logo {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-main);
            text-decoration: none;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        .logo span {
            color: var(--primary);
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .subtitle {
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        .content-grid {
            display: grid;
            gap: 30px;
            grid-template-columns: 1fr;
        }

        @media (min-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 30px;
            backdrop-filter: blur(10px);
            animation: slideUp 0.6s ease-out forwards;
            opacity: 0;
        }

        .card:nth-child(1) { animation-delay: 0.1s; }
        .card:nth-child(2) { animation-delay: 0.2s; }
        .card.full-width { grid-column: 1 / -1; animation-delay: 0.3s; }

        .card h2 {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: var(--primary);
            font-weight: 600;
        }

        .contact-info {
            list-style: none;
        }

        .contact-info li {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .contact-info a {
            color: var(--text-main);
            text-decoration: none;
            transition: color 0.3s;
        }

        .contact-info a:hover {
            color: var(--primary);
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        input, textarea {
            width: 100%;
            padding: 12px 15px;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--card-border);
            border-radius: 8px;
            color: var(--text-main);
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s;
            font-family: 'Outfit', sans-serif;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(255, 102, 0, 0.2);
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        button {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s, transform 0.1s;
        }

        button:hover {
            background: var(--primary-hover);
        }

        button:active {
            transform: scale(0.98);
        }

        /* Alert Messages */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.95rem;
            animation: fadeIn 0.3s ease-out;
        }

        .alert.success {
            background: rgba(74, 222, 128, 0.1);
            color: var(--success);
            border: 1px solid rgba(74, 222, 128, 0.2);
        }

        .alert.error {
            background: rgba(248, 113, 113, 0.1);
            color: var(--error);
            border: 1px solid rgba(248, 113, 113, 0.2);
        }

        /* FAQ */
        .faq-item {
            margin-bottom: 20px;
            border-bottom: 1px solid var(--card-border);
            padding-bottom: 20px;
        }

        .faq-item:last-child {
            margin-bottom: 0;
            border-bottom: none;
            padding-bottom: 0;
        }

        .faq-question {
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 1.1rem;
        }

        .faq-answer {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        footer {
            text-align: center;
            padding: 20px;
            color: var(--text-muted);
            font-size: 0.9rem;
            border-top: 1px solid var(--card-border);
            margin-top: 50px;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <div class="container">
        <header>
            <a href="index.php" class="logo">WMA <span>Hub</span></a>
            <h1>Assistance & Support</h1>
            <p class="subtitle">Comment pouvons-nous vous aider aujourd'hui ?</p>
        </header>

        <div class="content-grid">
            
            <!-- Informations de contact -->
            <div class="card">
                <h2>Contactez-nous</h2>
                <p class="subtitle" style="margin-bottom: 20px;">Notre équipe est disponible pour répondre à vos questions et résoudre vos problèmes.</p>
                <ul class="contact-info">
                    <li>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                        <a href="mailto:support@wmahub.com">support@wmahub.com</a>
                    </li>
                </ul>
            </div>

            <!-- Formulaire de contact -->
            <div class="card">
                <h2>Envoyer un message</h2>
                
                <?php if (!empty($statusMsg)): ?>
                    <div class="alert <?php echo $statusClass; ?>">
                        <?php echo $statusMsg; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-group">
                        <label for="name">Votre Nom</label>
                        <input type="text" id="name" name="name" required placeholder="Ex: Jean Dupont">
                    </div>
                    <div class="form-group">
                        <label for="email">Adresse E-mail</label>
                        <input type="email" id="email" name="email" required placeholder="Ex: jean@example.com">
                    </div>
                    <div class="form-group">
                        <label for="message">Comment pouvons-nous vous aider ?</label>
                        <textarea id="message" name="message" required placeholder="Décrivez votre problème ou votre question..."></textarea>
                    </div>
                    <button type="submit">Envoyer le message</button>
                </form>
            </div>

            <!-- FAQ Rapide -->
            <div class="card full-width">
                <h2>Questions Fréquemment Posées (FAQ)</h2>
                
                <div class="faq-item">
                    <div class="faq-question">J'ai oublié mon mot de passe, comment le réinitialiser ?</div>
                    <div class="faq-answer">Sur l'écran de connexion de l'application mobile, cliquez sur "Mot de passe oublié". Un lien de réinitialisation vous sera envoyé par e-mail.</div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">Comment supprimer mon compte ?</div>
                    <div class="faq-answer">Conformément aux directives d'Apple, vous pouvez supprimer votre compte directement depuis l'application. Allez dans votre Profil > Paramètres > Supprimer mon compte.</div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">L'application ne se synchronise pas, que faire ?</div>
                    <div class="faq-answer">Vérifiez que vous disposez d'une connexion internet stable. Vous pouvez également essayer de vous déconnecter puis de vous reconnecter, ou forcer la fermeture de l'application et la rouvrir.</div>
                </div>
            </div>

        </div>
    </div>

    <footer>
        &copy; <?php echo date("Y"); ?> WMA Hub. Tous droits réservés.
    </footer>

</body>
</html>
