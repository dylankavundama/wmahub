<?php
error_reporting(0);
ini_set('display_errors', '0');

require_once __DIR__ . '/../includes/config.php';

if (ob_get_level() > 0) {
    ob_clean();
}

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = getDBConnection();

    if ($method === 'GET') {
        $userId = $_GET['user_id'] ?? '';
        if (empty($userId)) {
            throw new Exception("user_id est requis");
        }

        $stmt = $db->prepare("SELECT contract_signature FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new Exception("Utilisateur introuvable");
        }

        $signature = $user['contract_signature'] ?? '';
        echo json_encode([
            "success" => true,
            "signed" => !empty($signature),
            "signature" => $signature
        ]);
        exit;
    } 
    
    if ($method === 'POST') {
        $userId = $_POST['user_id'] ?? '';
        $signatureData = $_POST['signature_data'] ?? '';

        if (empty($userId) || empty($signatureData)) {
            throw new Exception("user_id et signature_data sont requis");
        }

        // Récupérer les informations de l'utilisateur
        $stmt = $db->prepare("SELECT name, email FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new Exception("Utilisateur introuvable");
        }

        // Mettre à jour la signature dans la base de données
        $updateStmt = $db->prepare("UPDATE users SET contract_signature = ? WHERE id = ?");
        $updateStmt->execute([$signatureData, $userId]);

        // Envoi de la notification par e-mail à l'admin
        try {
            require_once __DIR__ . '/../includes/mailer.php';
            
            $artistName = $user['name'] ?? 'Artiste mobile';
            $artistEmail = $user['email'] ?? 'Non spécifié';
            $date = date('d/m/Y H:i:s');
            
            // Envoyer l'email standard de signature
            $subject = "Contrat de Distribution Signé (Mobile) - " . $artistName;
            $htmlBody = "
            <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee;'>
                <h2 style='color: #ff6600; text-align: center;'>CONTRAT DE DISTRIBUTION MUSICALE (MOBILE)</h2>
                <p><strong>Artiste :</strong> $artistName</p>
                <p><strong>Email :</strong> $artistEmail</p>
                <p><strong>Date de signature :</strong> $date</p>
                <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                <p>L'artiste mentionné ci-dessus a lu et accepté les termes du contrat de distribution de WMA HUB via l'application mobile.</p>
                <p>Ci-dessous sa signature numérique :</p>
                <div style='text-align: center; margin-top: 20px; border: 1px solid #ccc; padding: 10px; display: inline-block;'>
                    <img src='$signatureData' alt='Signature de l\'artiste' style='max-width: 100%; height: auto;'>
                </div>
                <p style='font-size: 12px; color: #777; margin-top: 30px;'>Ceci est une signature électronique valide enregistrée depuis l'application mobile WMA HUB.</p>
            </div>
            ";
            
            sendEmail('landryxbb0@gmail.com', $subject, $htmlBody);
            
            // Envoyer aussi la notification d'admin générale
            notifyAdmin('certification', 'Contrat Signé (Mobile)', [
                'Nom' => $artistName,
                'Email' => $artistEmail,
                'Date' => $date
            ], 'https://wmahub.com/dashboards/admin/users.php');
            
            // Notification dans l'app
            createNotification($userId, 'project_update', "Votre contrat de distribution mobile a été envoyé avec succès.", 0);

        } catch (Exception $mailEx) {
            error_log("Failed to notify admin of mobile signature: " . $mailEx->getMessage());
        }

        echo json_encode([
            "success" => true,
            "message" => "Contrat signé avec succès"
        ]);
        exit;
    }

    throw new Exception("Méthode non autorisée");

} catch (Exception $e) {
    if (ob_get_level() > 0) {
        ob_clean();
    }
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
