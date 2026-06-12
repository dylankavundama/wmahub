<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint aux distributeurs non certifiés
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'distributeur') {
    header('Location: ../../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../includes/subscription_check.php';
// Vérifier l'abonnement
if (!hasActiveSubscription($_SESSION['user_id'])) {
    header('Location: ../../auth/subscription.php');
    exit;
}

$db = getDBConnection();
$userId = $_SESSION['user_id'];

// Vérifier si déjà certifié
$stmt = $db->prepare("SELECT is_certified FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($user['is_certified']) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currency = $_POST['currency'] ?? 'USD';
    $phone = trim($_POST['phone'] ?? '');

    if (!preg_match('/^243[0-9]{9}$/', $phone)) {
        $error = "Veuillez entrer un numéro au format valide (ex: 243820000000).";
    } else {
        $usd_fee = (float)getSetting('cert_fee_usd', 1);
        $cdf_fee = (float)getSetting('cert_fee_cdf', 2800);
        $amount = ($currency === 'USD') ? $usd_fee : $cdf_fee;
        $orderNumber = "CERT-" . time() . "-" . $userId;
        
        $callbackUrl = $baseUrl . "/api/flexpay-callback.php";
        
        $postData = [
            'merchant' => FLEXPAY_MERCHANT,
            'type' => '1',
            'reference' => $orderNumber,
            'amount' => $amount,
            'currency' => $currency,
            'phone' => $phone,
            'callbackUrl' => $callbackUrl,
            'returnUrl' => $baseUrl . '/dashboards/distributeur/index.php',
            'description' => "Certification de compte WMA Hub"
        ];

        $ch = curl_init(FLEXPAY_API_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . FLEXPAY_TOKEN
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode === 200 && (isset($result['orderNumber']) || (isset($result['code']) && $result['code'] === "0"))) {
            $remoteOrder = $result['orderNumber'] ?? $orderNumber;
            
            $stmt = $db->prepare("INSERT INTO payments (user_id, reference, order_number, amount, currency, payment_type, status) VALUES (?, ?, ?, ?, ?, 'certification', 'pending')");
            $stmt->execute([$userId, $orderNumber, $remoteOrder, $amount, $currency]);
            
            if (!empty($result['payment_url'])) {
                header('Location: ' . $result['payment_url']);
            } else {
                // Push USSD
                $_SESSION['pending_order_number'] = $remoteOrder;
                header('Location: certify.php?pending_push=1');
            }
            exit;
        } else {
            $error = "Erreur FlexPay : " . ($result['message'] ?? 'Impossible d\'initier le paiement.');
        }
    }
}

$pageTitle = 'Certification de Compte - WMA Hub';
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
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 2rem; padding: 3rem; width: 100%; max-width: 600px; box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.8); }
        .input-glass { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 1rem; color: #fff; width: 100%; padding: 1rem 1.5rem; transition: all 0.3s ease; }
        .input-glass:focus { border-color: #00e5ff; outline: none; background: rgba(255, 255, 255, 0.08); }
        .btn-pay { background: linear-gradient(135deg, #00b8d4 0%, #00e5ff 100%); color: #000; padding: 1.25rem; border-radius: 1.25rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; width: 100%; margin-top: 2rem; transition: all 0.3s ease; }
        .btn-pay:hover { transform: scale(1.02); box-shadow: 0 0 30px rgba(0, 229, 255, 0.3); filter: brightness(1.1); }
    </style>
</head>
<body>
    <div class="bg-glow"></div>
    <div class="glass-card">
        <header class="text-center mb-10">
            <div class="w-20 h-20 bg-cyan-500/10 rounded-full flex items-center justify-center text-cyan-500 text-4xl mx-auto mb-6 border border-cyan-500/20">
                <i class="fas fa-check-decagram"></i>
            </div>
            <h1 class="text-4xl font-black mb-3 text-cyan-500 tracking-tighter uppercase">Certification</h1>
            <p class="text-gray-400">Obtenez le badge bleu de confiance pour <span class="text-white font-bold" id="certPriceDisplay"><?= getSetting('cert_fee_usd', 1) ?>$</span> seulement / mois.</p>
        </header>

        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl mb-6 flex items-center gap-3 text-sm">
                <i class="fas fa-exclamation-triangle"></i>
                <p><?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['pending_push'])): ?>
            <div class="bg-cyan-500/10 border border-cyan-500/20 text-cyan-500 p-6 rounded-xl mb-6 text-center animate-pulse">
                <h4 class="font-bold mb-2"><i class="fas fa-mobile-alt mr-2"></i> Push de paiement envoyé</h4>
                <p class="text-xs">Veuillez valider la transaction sur votre téléphone pour obtenir votre badge de certification.</p>
                <a href="index.php" class="inline-block mt-4 text-[10px] uppercase font-black tracking-widest border-b border-cyan-500">Retour au dashboard</a>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div class="mb-4">
                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-4">Choisir l'opérateur</label>
                <div class="grid grid-cols-4 gap-4">
                    <label class="cursor-pointer group">
                        <input type="radio" name="operator" value="Orange" class="peer sr-only" checked>
                        <div class="aspect-square rounded-2xl border-2 border-gray-700 bg-gray-900/50 flex items-center justify-center p-3 transition-all peer-checked:border-orange-500 peer-checked:bg-orange-500/10 peer-checked:shadow-[0_0_20px_rgba(255,102,0,0.3)] group-hover:scale-105">
                            <img src="../../asset/img/operators/orange.png" alt="Orange" class="w-full h-full object-contain filter grayscale group-hover:grayscale-0 peer-checked:grayscale-0 transition-all opacity-70 group-hover:opacity-100 peer-checked:opacity-100">
                        </div>
                    </label>
                    <label class="cursor-pointer group">
                        <input type="radio" name="operator" value="Airtel" class="peer sr-only">
                        <div class="aspect-square rounded-2xl border-2 border-gray-700 bg-gray-900/50 flex items-center justify-center p-3 transition-all peer-checked:border-red-500 peer-checked:bg-red-500/10 peer-checked:shadow-[0_0_20px_rgba(239,68,68,0.3)] group-hover:scale-105">
                            <img src="../../asset/img/operators/airtel.png" alt="Airtel" class="w-full h-full object-contain filter grayscale group-hover:grayscale-0 peer-checked:grayscale-0 transition-all opacity-70 group-hover:opacity-100 peer-checked:opacity-100">
                        </div>
                    </label>
                    <label class="cursor-pointer group">
                        <input type="radio" name="operator" value="Vodacom" class="peer sr-only">
                        <div class="aspect-square rounded-2xl border-2 border-gray-700 bg-gray-900/50 flex items-center justify-center p-3 transition-all peer-checked:border-red-600 peer-checked:bg-red-600/10 peer-checked:shadow-[0_0_20px_rgba(220,38,38,0.3)] group-hover:scale-105">
                            <img src="../../asset/img/operators/vodacom.png" alt="Vodacom" class="w-full h-full object-contain filter grayscale group-hover:grayscale-0 peer-checked:grayscale-0 transition-all opacity-70 group-hover:opacity-100 peer-checked:opacity-100">
                        </div>
                    </label>
                    <label class="cursor-pointer group">
                        <input type="radio" name="operator" value="Africell" class="peer sr-only">
                        <div class="aspect-square rounded-2xl border-2 border-gray-700 bg-gray-900/50 flex items-center justify-center p-3 transition-all peer-checked:border-purple-500 peer-checked:bg-purple-500/10 peer-checked:shadow-[0_0_20px_rgba(168,85,247,0.3)] group-hover:scale-105">
                            <img src="../../asset/img/operators/africell.png" alt="Africell" class="w-full h-full object-contain filter grayscale group-hover:grayscale-0 peer-checked:grayscale-0 transition-all opacity-70 group-hover:opacity-100 peer-checked:opacity-100">
                        </div>
                    </label>
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest ml-2">Devise</label>
                <select name="currency" id="certCurrencySelect" class="input-glass">
                    <option value="USD">Dollar Américain (USD)</option>
                    <option value="CDF">Franc Congolais (CDF)</option>
                </select>
            </div>

            <div class="space-y-2">
                <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest ml-2">Numéro Mobile Money (243...)</label>
                <input type="text" name="phone" class="input-glass" placeholder="243820000000" required>
            </div>

            <button type="submit" class="btn-pay">
                Payer maintenant
            </button>
            <a href="index.php" class="block text-center text-xs text-gray-500 hover:text-white transition-colors">Plus tard</a>
        </form>

        <footer class="mt-8 text-center text-[10px] text-gray-600 uppercase font-black tracking-widest">
            <p><i class="fas fa-lock mr-2"></i> Sécurisé par FlexPay</p>
        </footer>
    </div>
    <script>
        const certPrices = {
            USD: <?= (float)getSetting('cert_fee_usd', 1) ?>,
            CDF: <?= (float)getSetting('cert_fee_cdf', 2800) ?>
        };

        const certCurrencySelect = document.getElementById('certCurrencySelect');
        const certPriceDisplay = document.getElementById('certPriceDisplay');

        function formatNumber(num) {
            return new Intl.NumberFormat('fr-FR').format(num).replace(',', ' ');
        }

        certCurrencySelect.addEventListener('change', (e) => {
            const currency = e.target.value;
            const symbol = currency === 'CDF' ? 'FC' : '$';
            certPriceDisplay.innerText = `${formatNumber(certPrices[currency])}${symbol}`;
        });

        // Polling pour le statut du paiement
        <?php if (isset($_GET['pending_push']) && isset($_SESSION['pending_order_number'])) { ?>
                let checkDelay = 2000;
        let statusTimeout;
        const checkStatus = async () => {
            try {
                const response = await fetch(`../../api/check-payment-status.php?orderNumber=<?= $_SESSION['pending_order_number'] ?>`);
                const data = await response.json();
                
                if (data.status === 'success') {
                    if (typeof statusTimeout !== 'undefined') clearTimeout(statusTimeout);
                    Swal.fire({
                        icon: 'success',
                        title: 'Certification Activée !',
                        html: '<i class="fas fa-check-circle" style="color: #00e5ff; font-size: 3rem;"></i><br><br>Votre badge de certification est désormais visible.',
                        showConfirmButton: false,
                        timer: 2500,
                        timerProgressBar: true,
                        background: '#1a1a1a',
                        color: '#fff'
                    }).then(() => {
                        window.location.href = 'index.php?payment_success=1';
                    });
                    return;
                }
                
                // Plan next check with dynamic delay (max 12s)
                if (checkDelay < 12000) checkDelay += 1000;
                statusTimeout = setTimeout(checkStatus, checkDelay);
            } catch (error) {
                console.error("Erreur de vérification :", error);
                if (checkDelay < 12000) checkDelay += 1000;
                statusTimeout = setTimeout(checkStatus, checkDelay);
            }
        };

        // Vérifier avec délai progressif
        statusTimeout = setTimeout(checkStatus, checkDelay);
        // Arrêter après 5 minutes
        setTimeout(() => {
            if (typeof statusTimeout !== 'undefined') clearTimeout(statusTimeout);
            console.log("Polling arrêté après timeout.");
        }, 300000);
        <?php } ?>
    </script>
</body>
</html>
