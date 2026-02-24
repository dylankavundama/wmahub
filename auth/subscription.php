<?php
require_once __DIR__ . '/../includes/config.php';

// Sécurité : Accès restreint aux utilisateurs connectés
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Les artistes ont désormais un accès gratuit et ne devraient plus être sur cette page
if ($_SESSION['role'] === 'artiste') {
    header('Location: ../dashboards/artiste/index.php');
    exit;
}

// Seuls les distributeurs ont encore besoin de gérer des abonnements
if ($_SESSION['role'] !== 'distributeur') {
    header('Location: select-role.php');
    exit;
}

$db = getDBConnection();

// Vérifier si l'utilisateur a déjà un abonnement actif (pour éviter les doublons)
$stmt = $db->prepare("SELECT id FROM subscriptions WHERE user_id = ? AND status = 'active' AND end_date >= CURDATE()");
$stmt->execute([$_SESSION['user_id']]);
if ($stmt->fetch()) {
    $redirect = ($_SESSION['role'] === 'distributeur') ? '../dashboards/distributeur/index.php' : '../dashboards/artiste/index.php';
    header('Location: ' . $redirect);
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan = $_POST['plan'] ?? ''; // 'monthly' ou 'annual'
    $currency = $_POST['currency'] ?? 'CDF';
    $phone = trim($_POST['phone'] ?? '');

    // Validation du numéro (format RDC attendu : 243...)
    if (!preg_match('/^243[0-9]{9}$/', $phone)) {
        $error = "Veuillez entrer un numéro au format valide (ex: 243820000000).";
    } else {
        // Seuls les distributeurs ont des prix définis ici
        $prices = [
            'monthly' => [
                'CDF' => (float)getSetting('dist_sub_monthly_cdf', 70000), 
                'USD' => (float)getSetting('dist_sub_monthly_usd', 25)
            ],
            'annual' => [
                'CDF' => (float)getSetting('dist_sub_annual_cdf', 616000), 
                'USD' => (float)getSetting('dist_sub_annual_usd', 220)
            ]
        ];

        if (isset($prices[$plan][$currency])) {
            $amount = $prices[$plan][$currency];
            $orderNumber = "SUB-" . time() . "-" . $_SESSION['user_id'];
            
            // Appel API FlexPay
            $callbackUrl = $baseUrl . "/api/flexpay-callback.php";
            
            $postData = [
                'merchant' => FLEXPAY_MERCHANT,
                'type' => '1', // Mobile Money
                'reference' => $orderNumber,
                'amount' => $amount,
                'currency' => $currency,
                'phone' => $phone,
                'callbackUrl' => $callbackUrl,
                'returnUrl' => $baseUrl . '/auth/subscription.php',
                'description' => "Abonnement WMA Hub " . ($plan === 'monthly' ? 'Mensuel' : 'Annuel')
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
                $remoteOrder = $result['orderNumber'] ?? $orderNumber; // Fallback to our order number if remote is missing
                
                // Enregistrer la transaction en attente
                $stmt = $db->prepare("INSERT INTO payments (user_id, reference, order_number, amount, currency, status, payment_type, plan_type) VALUES (?, ?, ?, ?, ?, 'pending', 'subscription', ?)");
                $stmt->execute([$_SESSION['user_id'], $orderNumber, $remoteOrder, $amount, $currency, $plan]);
                
                // Rediriger ou afficher message selon la réponse
                if (!empty($result['payment_url'])) {
                    header('Location: ' . $result['payment_url']);
                } else {
                    // C'est un Push USSD direct
                    $_SESSION['pending_order_number'] = $remoteOrder;
                    header('Location: subscription.php?pending_push=1');
                }
                exit;
            } else {
                $error = "Erreur FlexPay : " . ($result['message'] ?? 'Impossible d\'initier le paiement.');
            }
        } else {
            $error = "Veuillez sélectionner un plan valide.";
        }
    }
}

// Récupération automatique d'un paiement récent en attente ou échoué pour vérification
$autoCheckOrderNumber = null;
if (!isset($_GET['pending_push'])) {
    $stmt = $db->prepare("SELECT order_number FROM payments WHERE user_id = ? AND status IN ('pending', 'failed') AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $lastPayment = $stmt->fetch();
    if ($lastPayment) {
        $autoCheckOrderNumber = $lastPayment['order_number'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abonnement <?= $_SESSION['role'] === 'distributeur' ? 'Distributeur' : 'Artiste' ?> - WMA Hub</title>
    <link rel="icon" type="image/jpeg" href="../asset/placeholder.jpg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 2rem; padding: 3rem; width: 100%; max-width: 900px; box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.8); }
        .plan-card { background: rgba(255, 255, 255, 0.02); border: 2px solid rgba(255, 255, 255, 0.05); border-radius: 1.5rem; padding: 2rem; transition: all 0.3s ease; cursor: pointer; position: relative; }
        .plan-card:hover { transform: translateY(-5px); background: rgba(255, 255, 255, 0.05); border-color: rgba(255, 102, 0, 0.3); }
        .peer:checked + .plan-card { border-color: #ff6600; background: rgba(255, 102, 0, 0.05); box-shadow: 0 0 30px rgba(255, 102, 0, 0.2); }
        .input-glass { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 1rem; color: #fff; width: 100%; padding: 1rem 1.5rem; transition: all 0.3s ease; }
        .input-glass:focus { border-color: #ff6600; outline: none; background: rgba(255, 255, 255, 0.08); }
        .btn-pay { background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%); color: #fff; padding: 1.25rem; border-radius: 1.25rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; width: 100%; margin-top: 2rem; transition: all 0.3s ease; }
        .btn-pay:hover { transform: scale(1.02); filter: brightness(1.1); box-shadow: 0 15px 30px -5px rgba(255, 102, 0, 0.5); }
    </style>
</head>
<body>
    <div class="bg-glow"></div>
    <div class="glass-card">
        <header class="text-center mb-12">
            <h1 class="text-5xl font-black mb-4">Prêt à <span class="text-orange-500">exploser</span> ?</h1>
            <p class="text-gray-400 text-lg">Choisissez votre forfait pour accéder au tableau de bord distributeur.</p>
        </header>

        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-6 rounded-2xl mb-8 flex items-center gap-4">
                <i class="fas fa-exclamation-triangle text-2xl"></i>
                <p><?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['pending_push'])): ?>
            <div class="bg-orange-500/10 border border-orange-500/20 text-orange-500 p-8 rounded-3xl mb-12 text-center animate-pulse">
                <div class="w-16 h-16 bg-orange-500/10 rounded-full flex items-center justify-center mx-auto mb-6 text-2xl">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Paiement en cours...</h3>
                <p class="text-sm opacity-80">Un message Push a été envoyé sur votre téléphone. Veuillez valider la transaction pour activer votre abonnement.</p>
                <div class="mt-8 flex justify-center gap-4">
                    <a href="<?= ($_SESSION['role'] === 'distributeur') ? '../dashboards/distributeur/index.php' : '../dashboards/artiste/index.php' ?>" class="text-xs font-black uppercase tracking-widest border-b border-orange-500 pb-1">J'ai validé le paiement</a>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
                <label class="relative">
                    <input type="radio" name="plan" value="monthly" class="sr-only peer" checked>
                    <div class="plan-card h-full flex flex-col items-center">
                        <div class="w-16 h-16 bg-orange-500/10 rounded-2xl flex items-center justify-center text-orange-500 text-3xl mb-6">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-2 uppercase">Mensuel</h3>
                        <div class="text-4xl font-black mb-6" id="monthlyPriceMain"><?= number_format($_SESSION['role'] === 'distributeur' ? getSetting('dist_sub_monthly_cdf', 70000) : getSetting('sub_monthly_cdf', 11000), 0, '.', ' ') ?> <span class="text-lg font-bold text-gray-500">FC</span></div>
                        <ul class="text-gray-400 text-sm space-y-3 mb-8 text-center flex-1">
                            <li><i class="fas fa-check text-orange-500 mr-2"></i> Distribution illimitée</li>
                            <li><i class="fas fa-check text-orange-500 mr-2"></i> Dashboard complet</li>
                            <li><i class="fas fa-check text-orange-500 mr-2"></i> Support Prioritaire</li>
                        </ul>
                        <p class="text-[10px] text-gray-500 uppercase font-black tracking-widest" id="monthlyPriceSub">Ou <?= $_SESSION['role'] === 'distributeur' ? getSetting('dist_sub_monthly_usd', 25) : getSetting('sub_monthly_usd', 5) ?> USD / mois</p>
                    </div>
                </label>

                <label class="relative">
                    <input type="radio" name="plan" value="annual" class="sr-only peer">
                    <div class="plan-card h-full flex flex-col items-center border-orange-500/20">
                        <div class="absolute top-4 right-4 bg-orange-500 text-white text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-tighter shadow-lg shadow-orange-500/50">Économisez 25%</div>
                        <div class="w-16 h-16 bg-orange-500/10 rounded-2xl flex items-center justify-center text-orange-500 text-3xl mb-6">
                            <i class="fas fa-crown"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-2 uppercase">Annuel</h3>
                        <div class="text-4xl font-black mb-6" id="annualPriceMain"><?= number_format($_SESSION['role'] === 'distributeur' ? getSetting('dist_sub_annual_cdf', 616000) : getSetting('sub_annual_cdf', 100000), 0, '.', ' ') ?> <span class="text-lg font-bold text-gray-500">FC</span></div>
                        <ul class="text-gray-400 text-sm space-y-3 mb-8 text-center flex-1">
                            <li><i class="fas fa-check text-orange-500 mr-2"></i> Distribution illimitée</li>
                            <li><i class="fas fa-check text-orange-500 mr-2"></i> Dashboard complet</li>
                            <li><i class="fas fa-check text-orange-500 mr-2"></i> Gestion d'artistes illimitée</li>
                            <li><i class="fas fa-check text-orange-500 mr-2"></i> Statistiques avancées</li>
                        </ul>
                        <p class="text-[10px] text-gray-500 uppercase font-black tracking-widest" id="annualPriceSub">Ou <?= getSetting('dist_sub_annual_usd', 220) ?> USD / an</p>
                    </div>
                </label>
            </div>

            <div class="mb-10">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-4">Choisir votre opérateur</label>
                <div class="grid grid-cols-4 gap-4">
                    <label class="cursor-pointer group">
                        <input type="radio" name="operator" value="Orange" class="peer sr-only" checked>
                        <div class="aspect-square rounded-2xl border-2 border-gray-700 bg-gray-900/50 flex items-center justify-center p-4 transition-all peer-checked:border-orange-500 peer-checked:bg-orange-500/10 peer-checked:shadow-[0_0_20px_rgba(255,102,0,0.3)] group-hover:scale-105">
                            <img src="../asset/img/operators/orange.png" alt="Orange" class="w-full h-full object-contain filter grayscale group-hover:grayscale-0 peer-checked:grayscale-0 transition-all opacity-70 group-hover:opacity-100 peer-checked:opacity-100">
                        </div>
                    </label>
                    <label class="cursor-pointer group">
                        <input type="radio" name="operator" value="Airtel" class="peer sr-only">
                        <div class="aspect-square rounded-2xl border-2 border-gray-700 bg-gray-900/50 flex items-center justify-center p-4 transition-all peer-checked:border-red-500 peer-checked:bg-red-500/10 peer-checked:shadow-[0_0_20px_rgba(239,68,68,0.3)] group-hover:scale-105">
                            <img src="../asset/img/operators/airtel.png" alt="Airtel" class="w-full h-full object-contain filter grayscale group-hover:grayscale-0 peer-checked:grayscale-0 transition-all opacity-70 group-hover:opacity-100 peer-checked:opacity-100">
                        </div>
                    </label>
                    <label class="cursor-pointer group">
                        <input type="radio" name="operator" value="Vodacom" class="peer sr-only">
                        <div class="aspect-square rounded-2xl border-2 border-gray-700 bg-gray-900/50 flex items-center justify-center p-4 transition-all peer-checked:border-red-600 peer-checked:bg-red-600/10 peer-checked:shadow-[0_0_20px_rgba(220,38,38,0.3)] group-hover:scale-105">
                            <img src="../asset/img/operators/vodacom.png" alt="Vodacom" class="w-full h-full object-contain filter grayscale group-hover:grayscale-0 peer-checked:grayscale-0 transition-all opacity-70 group-hover:opacity-100 peer-checked:opacity-100">
                        </div>
                    </label>
                    <label class="cursor-pointer group">
                        <input type="radio" name="operator" value="Africell" class="peer sr-only">
                        <div class="aspect-square rounded-2xl border-2 border-gray-700 bg-gray-900/50 flex items-center justify-center p-4 transition-all peer-checked:border-purple-500 peer-checked:bg-purple-500/10 peer-checked:shadow-[0_0_20px_rgba(168,85,247,0.3)] group-hover:scale-105">
                            <img src="../asset/img/operators/africell.png" alt="Africell" class="w-full h-full object-contain filter grayscale group-hover:grayscale-0 peer-checked:grayscale-0 transition-all opacity-70 group-hover:opacity-100 peer-checked:opacity-100">
                        </div>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-end">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-4">Devise de paiement</label>
                    <select name="currency" id="currencySelect" class="input-glass">
                        <option value="CDF">Franc Congolais (CDF)</option>
                        <option value="USD">Dollar Américain (USD)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-4">Numéro Mobile Money (243...)</label>
                    <input type="text" name="phone" class="input-glass" placeholder="243820000000" required>
                </div>
            </div>

            <button type="submit" class="btn-pay group">
                S'abonner maintenant <i class="fas fa-chevron-right ml-2 group-hover:translate-x-1 transition-transform"></i>
            </button>
        </form>

        <footer class="mt-12 text-center text-gray-600 text-sm">
            <p><i class="fas fa-lock mr-2"></i> Paiement sécurisé via FlexPay RDC</p>
        </footer>
    </div>
    <script>
        const prices = {
            monthly: {
                CDF: <?= $_SESSION['role'] === 'distributeur' ? (float)getSetting('dist_sub_monthly_cdf', 70000) : (float)getSetting('sub_monthly_cdf', 11000) ?>,
                USD: <?= $_SESSION['role'] === 'distributeur' ? (float)getSetting('dist_sub_monthly_usd', 25) : (float)getSetting('sub_monthly_usd', 5) ?>
            },
            annual: {
                CDF: <?= $_SESSION['role'] === 'distributeur' ? (float)getSetting('dist_sub_annual_cdf', 616000) : (float)getSetting('sub_annual_cdf', 100000) ?>,
                USD: <?= $_SESSION['role'] === 'distributeur' ? (float)getSetting('dist_sub_annual_usd', 220) : (float)getSetting('sub_annual_usd', 100) ?>
            }
        };

        const currencySelect = document.getElementById('currencySelect');
        const monthlyPriceMain = document.getElementById('monthlyPriceMain');
        const monthlyPriceSub = document.getElementById('monthlyPriceSub');
        const annualPriceMain = document.getElementById('annualPriceMain');
        const annualPriceSub = document.getElementById('annualPriceSub');

        function formatNumber(num) {
            return new Intl.NumberFormat('fr-FR').format(num).replace(',', ' ');
        }

        currencySelect.addEventListener('change', (e) => {
            const currency = e.target.value;
            const otherCurrency = currency === 'CDF' ? 'USD' : 'CDF';
            const symbol = currency === 'CDF' ? 'FC' : '$';
            const otherSymbol = otherCurrency === 'CDF' ? 'FC' : 'USD';

            // Mensuel
            monthlyPriceMain.innerHTML = `${formatNumber(prices.monthly[currency])} <span class="text-lg font-bold text-gray-500">${symbol}</span>`;
            monthlyPriceSub.innerText = `Ou ${formatNumber(prices.monthly[otherCurrency])} ${otherSymbol} / mois`;

            // Annuel
            annualPriceMain.innerHTML = `${formatNumber(prices.annual[currency])} <span class="text-lg font-bold text-gray-500">${symbol}</span>`;
            annualPriceSub.innerText = `Ou ${formatNumber(prices.annual[otherCurrency])} ${otherSymbol} / an`;
        });

        // Polling pour le statut du paiement
        <?php if ((isset($_GET['pending_push']) && isset($_SESSION['pending_order_number'])) || !empty($autoCheckOrderNumber)): 
            $checkTarget = isset($_GET['pending_push']) ? $_SESSION['pending_order_number'] : $autoCheckOrderNumber;
        ?>
        console.log("Démarrage de la vérification pour : <?= $checkTarget ?>");
        const checkStatus = async () => {
            try {
                const response = await fetch(`../api/check-payment-status.php?orderNumber=<?= $checkTarget ?>`);
                const data = await response.json();
                
                if (data.status === 'success') {
                    if (typeof statusInterval !== 'undefined') clearInterval(statusInterval);
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Paiement Validé !',
                        text: 'Votre abonnement a été activé avec succès.',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                        background: '#1a1a1a',
                        color: '#fff'
                    }).then(() => {
                        window.location.href = '<?= ($_SESSION['role'] === 'distributeur') ? '../dashboards/distributeur/index.php?payment_success=1' : '../dashboards/artiste/index.php?payment_success=1' ?>';
                    });
                }
            } catch (error) {
                console.error("Erreur de vérification :", error);
            }
        };

        // Si c'est une récupération automatique (pas de push en cours), on vérifie une seule fois au chargement
        <?php if (!isset($_GET['pending_push'])): ?>
            checkStatus();
        <?php else: ?>
            // Vérifier toutes les 2 secondes (détection rapide pour Push)
            const statusInterval = setInterval(checkStatus, 2000);
            // Arrêter après 5 minutes
            setTimeout(() => {
                if (typeof statusInterval !== 'undefined') clearInterval(statusInterval);
                console.log("Polling arrêté après timeout.");
            }, 300000);
        <?php endif; ?>

        <?php endif; ?>
    </script>
</body>
</html>
