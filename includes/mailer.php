<?php
/**
 * WMA Hub - Email Helper
 * Envoi d'emails via SMTP cPanel avec PHPMailer
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

// Configuration SMTP (cPanel)
define('SMTP_HOST', 'mail.wmahub.com');
define('SMTP_PORT', 465);
define('SMTP_USERNAME', 'noreply@wmahub.com');
define('SMTP_PASSWORD', 'F)K4q_]E2#M^f0qt');
define('SMTP_FROM_NAME', 'WMA HUB');
define('SMTP_FROM_EMAIL', 'noreply@wmahub.com');

/**
 * Envoie un email HTML via SMTP
 * 
 * @param string|array $to Destinataire(s)
 * @param string $subject Sujet du mail
 * @param string $htmlBody Contenu HTML
 * @param string|null $replyTo Email de réponse (optionnel)
 * @return bool Succès ou échec
 */
function sendEmail($to, $subject, $htmlBody, $replyTo = null) {
    $mail = new PHPMailer(true);
    
    try {
        // Configuration du serveur SMTP
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        
        // Expéditeur
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        
        // Destinataire(s)
        if (is_array($to)) {
            foreach ($to as $email) {
                $mail->addAddress($email);
            }
        } else {
            $mail->addAddress($to);
        }
        
        // Reply-To optionnel
        if ($replyTo) {
            $mail->addReplyTo($replyTo);
        }
        
        // Contenu
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("WMA Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Email template pour les notifications de projet
 */
function getProjectEmailTemplate($title, $content, $buttonText = null, $buttonUrl = null) {
    $button = '';
    if ($buttonText && $buttonUrl) {
        $button = "<a href='$buttonUrl' style='display: inline-block; background: linear-gradient(135deg, #ff6600, #ff8533); color: #ffffff; padding: 14px 28px; text-decoration: none; border-radius: 10px; font-weight: bold; margin-top: 20px;'>$buttonText</a>";
    }
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    </head>
    <body style='margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #0a0a0c;'>
        <table width='100%' cellpadding='0' cellspacing='0' style='background-color: #0a0a0c; padding: 40px 20px;'>
            <tr>
                <td align='center'>
                    <table width='600' cellpadding='0' cellspacing='0' style='background: linear-gradient(135deg, #1a1a2e, #16162a); border-radius: 20px; overflow: hidden; border: 1px solid rgba(255,255,255,0.05);'>
                        <!-- Header -->
                        <tr>
                            <td style='padding: 30px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.05);'>
                                <h1 style='margin: 0; font-size: 24px; font-weight: bold; background: linear-gradient(90deg, #ff6600, #ff8533); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;'>WMA HUB</h1>
                                <p style='margin: 5px 0 0 0; font-size: 10px; color: #666; text-transform: uppercase; letter-spacing: 2px;'>Distribution Musicale</p>
                            </td>
                        </tr>
                        <!-- Content -->
                        <tr>
                            <td style='padding: 40px 30px;'>
                                <h2 style='margin: 0 0 20px 0; color: #ffffff; font-size: 22px;'>$title</h2>
                                <div style='color: #aaaaaa; font-size: 15px; line-height: 1.6;'>
                                    $content
                                </div>
                                <div style='text-align: center; margin-top: 30px;'>
                                    $button
                                </div>
                            </td>
                        </tr>
                        <!-- Footer -->
                        <tr>
                            <td style='padding: 20px 30px; background: rgba(0,0,0,0.2); text-align: center;'>
                                <p style='margin: 0; font-size: 11px; color: #555;'>© " . date('Y') . " WMA HUB. Tous droits réservés.</p>
                                <p style='margin: 5px 0 0 0; font-size: 10px; color: #444;'>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>
    ";
}

/**
 * Notifie l'équipe d'un nouveau projet soumis
 */
function notifyNewProject($projectId, $projectTitle, $artistName, $projectType, $releaseDate) {
    $content = "
        <p>Un nouveau projet vient d'être soumis sur la plateforme.</p>
        <table style='width: 100%; margin-top: 20px; border-collapse: collapse;'>
            <tr>
                <td style='padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #888;'>Projet</td>
                <td style='padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #fff; font-weight: bold;'>" . htmlspecialchars($projectTitle) . "</td>
            </tr>
            <tr>
                <td style='padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #888;'>Artiste</td>
                <td style='padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #fff;'>" . htmlspecialchars($artistName) . "</td>
            </tr>
            <tr>
                <td style='padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #888;'>Type</td>
                <td style='padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #fff;'>" . htmlspecialchars($projectType) . "</td>
            </tr>
            <tr>
                <td style='padding: 12px; color: #888;'>Date de sortie</td>
                <td style='padding: 12px; color: #ff6600; font-weight: bold;'>" . date('d/m/Y', strtotime($releaseDate)) . "</td>
            </tr>
        </table>
    ";
    
    $html = getProjectEmailTemplate(
        "🎵 Nouveau Projet Soumis",
        $content,
        "Voir le projet",
        "https://wmahub.com/dashboards/admin/index.php?search=" . urlencode($projectTitle)
    );
    
    $recipients = ['info@wmahub.com', 'calebzubabeatz@gmail.com'];
    return sendEmail($recipients, "Nouveau Projet: " . $projectTitle, $html);
}

/**
 * Notifie l'artiste d'un changement de statut
 */
function notifyStatusChange($artistEmail, $artistName, $projectTitle, $newStatus) {
    $statusLabels = [
        'en_attente' => ['label' => 'En attente', 'color' => '#ffa500', 'icon' => '⏳'],
        'en_preparation' => ['label' => 'En préparation', 'color' => '#3b82f6', 'icon' => '🔧'],
        'distribue' => ['label' => 'Distribué', 'color' => '#22c55e', 'icon' => '✅']
    ];
    
    $status = $statusLabels[$newStatus] ?? ['label' => $newStatus, 'color' => '#888', 'icon' => '📋'];
    
    $content = "
        <p>Bonjour <strong style='color: #fff;'>" . htmlspecialchars($artistName) . "</strong>,</p>
        <p>Le statut de votre projet a été mis à jour.</p>
        <table style='width: 100%; margin-top: 20px; border-collapse: collapse;'>
            <tr>
                <td style='padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #888;'>Projet</td>
                <td style='padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #fff; font-weight: bold;'>" . htmlspecialchars($projectTitle) . "</td>
            </tr>
            <tr>
                <td style='padding: 12px; color: #888;'>Nouveau statut</td>
                <td style='padding: 12px;'>
                    <span style='display: inline-block; padding: 8px 16px; background: " . $status['color'] . "20; color: " . $status['color'] . "; border-radius: 20px; font-weight: bold; font-size: 12px; text-transform: uppercase;'>
                        " . $status['icon'] . " " . $status['label'] . "
                    </span>
                </td>
            </tr>
        </table>
    ";
    
    $html = getProjectEmailTemplate(
        "Mise à jour de votre projet",
        $content,
        "Voir mon projet",
        "https://wmahub.com/dashboards/artiste/catalogue.php"
    );
    
    return sendEmail($artistEmail, "Projet " . $projectTitle . " : " . $status['label'], $html);
}

/**
 * Notifie l'équipe admin (info@wmahub.com) de toute action importante
 * 
 * @param string $actionType Type d'action (registration, payment, certification, project, etc)
 * @param string $title Titre court de la notification
 * @param array $details Tableau de détails à afficher
 * @param string|null $actionUrl URL vers l'action (optionnel)
 */
function notifyAdmin($actionType, $title, $details = [], $actionUrl = null) {
    $icons = [
        'registration' => '👤',
        'subscription' => '💳',
        'certification' => '✅',
        'project' => '🎵',
        'payment' => '💰',
        'service_card' => '🪪',
        'employee' => '👷',
        'default' => '📢'
    ];
    
    $colors = [
        'registration' => '#3b82f6',
        'subscription' => '#22c55e',
        'certification' => '#00e5ff',
        'project' => '#ff6600',
        'payment' => '#22c55e',
        'service_card' => '#a855f7',
        'employee' => '#f59e0b',
        'default' => '#888888'
    ];
    
    $icon = $icons[$actionType] ?? $icons['default'];
    $color = $colors[$actionType] ?? $colors['default'];
    
    // Construire le tableau de détails
    $detailsHtml = '';
    foreach ($details as $label => $value) {
        $detailsHtml .= "
            <tr>
                <td style='padding: 10px 12px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #888; font-size: 13px;'>" . htmlspecialchars($label) . "</td>
                <td style='padding: 10px 12px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #fff; font-size: 13px; font-weight: 500;'>" . htmlspecialchars($value) . "</td>
            </tr>
        ";
    }
    
    $content = "
        <div style='padding: 15px; background: {$color}10; border-left: 4px solid {$color}; border-radius: 8px; margin-bottom: 20px;'>
            <span style='font-size: 24px; margin-right: 10px;'>$icon</span>
            <span style='color: {$color}; font-weight: bold; text-transform: uppercase; font-size: 11px; letter-spacing: 1px;'>" . strtoupper($actionType) . "</span>
        </div>
        <table style='width: 100%; border-collapse: collapse;'>
            $detailsHtml
        </table>
        <p style='margin-top: 20px; font-size: 11px; color: #666;'>
            <strong>Date :</strong> " . date('d/m/Y H:i:s') . "
        </p>
    ";
    
    $buttonText = $actionUrl ? "Voir les détails" : null;
    
    $html = getProjectEmailTemplate($icon . " " . $title, $content, $buttonText, $actionUrl);
    
    return sendEmail('info@wmahub.com', "[WMA HUB] " . $title, $html);
}

/**
 * Notifie l'équipe abonnement (abonnement@wmahub.com) des paiements réussis
 * 
 * @param string $paymentType Type de paiement (subscription, certification, service_card)
 * @param string $userName Nom de l'utilisateur
 * @param string $userEmail Email de l'utilisateur
 * @param string $userRole Rôle (artiste, distributeur)
 * @param float $amount Montant payé
 * @param string $currency Devise (USD, CDF)
 * @param string $reference Référence FlexPay
 * @param array $extraDetails Détails supplémentaires optionnels
 */
function notifySubscriptionTeam($paymentType, $userName, $userEmail, $userRole, $amount, $currency, $reference, $extraDetails = []) {
    $typeLabels = [
        'subscription' => ['label' => 'Abonnement', 'icon' => '💳', 'color' => '#22c55e'],
        'certification' => ['label' => 'Certification', 'icon' => '✅', 'color' => '#00e5ff'],
        'service_card' => ['label' => 'Carte de Service', 'icon' => '🪪', 'color' => '#a855f7']
    ];
    
    $type = $typeLabels[$paymentType] ?? ['label' => 'Paiement', 'icon' => '💰', 'color' => '#ff6600'];
    $roleLabel = ($userRole === 'distributeur') ? 'Distributeur' : 'Artiste';
    
    // Construire les détails
    $allDetails = [
        'Type' => $type['label'],
        $roleLabel => $userName,
        'Email' => $userEmail,
        'Montant' => number_format($amount, 2) . ' ' . $currency,
        'Référence' => $reference,
        'Date' => date('d/m/Y H:i:s')
    ];
    
    // Ajouter les détails supplémentaires
    foreach ($extraDetails as $key => $value) {
        $allDetails[$key] = $value;
    }
    
    // Construire le HTML des détails
    $detailsHtml = '';
    foreach ($allDetails as $label => $value) {
        $detailsHtml .= "
            <tr>
                <td style='padding: 12px 15px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #888; font-size: 13px; width: 40%;'>" . htmlspecialchars($label) . "</td>
                <td style='padding: 12px 15px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #fff; font-size: 13px; font-weight: 600;'>" . htmlspecialchars($value) . "</td>
            </tr>
        ";
    }
    
    $content = "
        <div style='padding: 20px; background: {$type['color']}15; border-left: 5px solid {$type['color']}; border-radius: 10px; margin-bottom: 25px;'>
            <span style='font-size: 28px; margin-right: 12px;'>{$type['icon']}</span>
            <span style='color: {$type['color']}; font-weight: 800; text-transform: uppercase; font-size: 12px; letter-spacing: 2px;'>PAIEMENT RÉUSSI</span>
        </div>
        <table style='width: 100%; border-collapse: collapse; background: rgba(255,255,255,0.02); border-radius: 12px; overflow: hidden;'>
            $detailsHtml
        </table>
    ";
    
    $html = getProjectEmailTemplate($type['icon'] . " Nouveau " . $type['label'] . " Payé", $content);
    
    return sendEmail('abonnement@wmahub.com', "[PAIEMENT] " . $type['label'] . " - " . $userName, $html);
}
/**
 * Notifie l'employé d'une nouvelle tâche attribuée
 */
function notifyNewTask($employeeEmail, $employeeName, $taskTitle, $taskId) {
    $content = "
        <p>Bonjour <strong style='color: #fff;'>" . htmlspecialchars($employeeName) . "</strong>,</p>
        <p>Une nouvelle mission vous a été attribuée sur <strong>WMA HUB</strong>.</p>
        <div style='background: rgba(255,102,0,0.05); border-left: 4px solid #ff6600; padding: 20px; margin: 25px 0; border-radius: 8px;'>
            <p style='margin: 0; font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 1px;'>Mission</p>
            <p style='margin: 5px 0 0 0; font-size: 18px; color: #fff; font-weight: bold;'>" . htmlspecialchars($taskTitle) . "</p>
        </div>
        <p style='font-size: 14px;'>Vous pouvez consulter les détails de cette mission, échanger avec l'administration et suivre son évolution via votre tableau de bord.</p>
    ";
    
    $html = getProjectEmailTemplate(
        "🚀 Nouvelle Mission Attribuée",
        $content,
        "Voir les détails et commencer",
        "https://wmahub.com/dashboards/admin/task_chat.php?id=" . $taskId
    );
    
    return sendEmail($employeeEmail, "🚀 Nouvelle mission : " . $taskTitle, $html);
}
/**
 * Envoie un email de bienvenue personnalisé selon le rôle
 */
function sendWelcomeEmail($email, $name, $role) {
    $roleTitles = [
        'artiste' => 'Bienvenue Artiste ! 🎤',
        'distributeur' => 'Bienvenue Partenaire ! 🤝',
        'employe' => 'Bienvenue dans l\'Équipe ! 👷',
        'superadmin' => 'Accès Superadmin Activé ⚡'
    ];

    $title = $roleTitles[$role] ?? 'Bienvenue sur WMA HUB !';
    
    $content = '';
    $buttonText = 'Accéder à mon tableau de bord';
    $buttonUrl = 'https://wmahub.com/auth/login.php';

    switch ($role) {
        case 'artiste':
            $content = "
                <p>C'est un honneur de vous compter parmi les artistes de <strong>WMA HUB</strong>.</p>
                <p>Votre compte a été créé avec succès. Vous êtes maintenant à un pas de propulser votre carrière musicale à l'échelle mondiale.</p>
                <div style='background: rgba(255,255,255,0.03); border-radius: 12px; padding: 20px; margin: 25px 0;'>
                    <p style='margin:0 0 10px 0; color:#fff; font-weight:bold;'>Prochaines étapes :</p>
                    <ul style='margin:0; padding-left:20px; color:#888;'>
                        <li>Souscrivez à un abonnement</li>
                        <li>Soumettez votre premier projet</li>
                        <li>Suivez vos statistiques en temps réel</li>
                    </ul>
                </div>
                <p>Nous avons hâte d'entendre vos prochaines créations !</p>
            ";
            break;

        case 'distributeur':
            $content = "
                <p>Félicitations ! Votre organisation est désormais partenaire de <strong>WMA HUB</strong>.</p>
                <p>En tant que distributeur, vous disposez d'un accès privilégié pour gérer vos artistes, organiser leurs sorties et maximiser leurs revenus.</p>
                <div style='background: rgba(255,255,255,0.03); border-radius: 12px; padding: 20px; margin: 25px 0;'>
                    <p style='margin:0 0 10px 0; color:#fff; font-weight:bold;'>Vos outils :</p>
                    <ul style='margin:0; padding-left:20px; color:#888;'>
                        <li>Gestion centralisée des artistes</li>
                        <li>Rapports de revenus détaillés</li>
                        <li>Certification de compte distributeur</li>
                    </ul>
                </div>
                <p>Ensemble, faisons bouger l'industrie musicale.</p>
            ";
            break;

        case 'employe':
            $content = "
                <p>Bienvenue dans l'équipe interne de <strong>WMA HUB</strong>.</p>
                <p>Votre inscription a bien été enregistrée. Pour des raisons de sécurité, votre accès doit être <strong>activé manuellement</strong> par un administrateur.</p>
                <p>Vous recevrez un email dès que votre compte sera prêt à l'emploi. En attendant, n'hésitez pas à vous familiariser avec nos processus internes.</p>
            ";
            $buttonText = 'Voir l\'état de mon compte';
            break;

        default:
            $content = "<p>Merci d'avoir rejoint WMA HUB. Votre compte est prêt et vous pouvez désormais profiter de tous nos services.</p>";
    }

    $html = getProjectEmailTemplate($title, $content, $buttonText, $buttonUrl);
    return sendEmail($email, $title, $html);
}

/**
 * Email de félicitations pour les top employés du mois
 */
function notifyMonthlyAward($email, $name, $position, $month) {
    $medals = [1 => '🥇', 2 => '🥈', 3 => '🥉'];
    $medal = $medals[$position] ?? '⭐';
    $pos_text = ($position == 1) ? '1ère place' : "{$position}ème place";
    
    $subject = "Félicitations ! Vous êtes dans le Top 3 du mois de $month";
    $content = "
        <div style='text-align: center; padding: 20px;'>
            <div style='font-size: 50px; margin-bottom: 20px;'>$medal</div>
            <h2 style='color: #ff6600;'>Bravo $name !</h2>
            <p style='color: #fff; font-size: 16px;'>
                Nous sommes ravis de vous annoncer que vous avez atteint la <strong>$pos_text</strong> 
                au classement des performances de <strong>WMA HUB</strong> pour le mois de <strong>$month</strong>.
            </p>
            <p style='color: #888; font-size: 14px;'>
                Votre dévouement et votre efficacité font briller l'équipe. Continuez sur cette lancée !
            </p>
        </div>
    ";
    
    return sendEmail($email, $subject, getProjectEmailTemplate("🏆 Récompense Mensuelle", $content, "Voir mon dashboard", "https://wmahub.com/dashboards/employe/index.php"));
}
?>


