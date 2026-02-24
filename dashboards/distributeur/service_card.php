<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint aux distributeurs
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

// Récupérer les infos du distributeur
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Récupérer la demande de carte existante
$stmt_card = $db->prepare("SELECT * FROM service_cards WHERE user_id = ?");
$stmt_card->execute([$userId]);
$card = $stmt_card->fetch();

$error = '';
$success = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_card'])) {
    try {
        $full_name = $_POST['full_name'] ?? '';
        $function = $_POST['function'] ?? 'Distributeur Indépendant';
        $department = $_POST['department'] ?? 'Distribution';
        $blood_group = $_POST['blood_group'] ?? '';
        $emergency_contact = $_POST['emergency_contact'] ?? '';
        
        $photo_path = $card['photo_path'] ?? '';

        // Gestion de la photo
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/uploads/cards/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $file_ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $new_name = 'card_' . $userId . '_' . time() . '.' . $file_ext;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $new_name)) {
                $photo_path = 'dashboards/distributeur/uploads/cards/' . $new_name;
            }
        }

        if ($card) {
            $stmt_upd = $db->prepare("UPDATE service_cards SET full_name = ?, role = ?, department = ?, blood_group = ?, emergency_contact = ?, photo_path = ?, status = 'pending_payment', updated_at = NOW() WHERE id = ?");
            $stmt_upd->execute([$full_name, $function, $department, $blood_group, $emergency_contact, $photo_path, $card['id']]);
        } else {
            $stmt_ins = $db->prepare("INSERT INTO service_cards (user_id, full_name, role, department, blood_group, emergency_contact, photo_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending_payment')");
            $stmt_ins->execute([$userId, $full_name, $function, $department, $blood_group, $emergency_contact, $photo_path]);
        }

        header("Location: service_card.php?success=1");
        exit;
    } catch (Exception $e) {
        $error = "Erreur : " . $e->getMessage();
    }
}

// Initiation du paiement
if (isset($_POST['pay_card'])) {
    $usd_fee = (float)getSetting('card_fee_usd', 1);
    $cdf_fee = (float)getSetting('card_fee_cdf', 3000);
    
    $amount = ($_POST['currency'] === 'USD') ? $usd_fee : $cdf_fee;
    $currency = $_POST['currency'];
    $phone = $_POST['phone'];
    $orderNumber = "CARD-" . time() . "-" . $userId;

    $stmt_pay = $db->prepare("INSERT INTO payments (user_id, reference, order_number, amount, currency, payment_type, status) VALUES (?, ?, ?, ?, ?, 'service_card', 'pending')");
    $stmt_pay->execute([$userId, "REF-" . $orderNumber, $orderNumber, $amount, $currency]);
    $paymentId = $db->lastInsertId();

    // Appel API FlexPay
    $payload = [
        "merchant" => FLEXPAY_MERCHANT,
        "type" => "1", // Mobile Money
        "phone" => $phone,
        "reference" => $orderNumber,
        "amount" => $amount,
        "currency" => $currency,
        "callbackUrl" => $baseUrl . "/api/flexpay-callback.php",
        "returnUrl" => $baseUrl . "/dashboards/distributeur/service_card.php",
        "description" => "Paiement Carte de Service WMA HUB"
    ];

    $ch = curl_init(FLEXPAY_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . FLEXPAY_TOKEN]);
    
    $response = curl_exec($ch);
    $result = json_decode($response, true);
    curl_close($ch);

    if (isset($result['orderNumber']) || (isset($result['code']) && $result['code'] === "0")) {
        if (isset($result['payment_url'])) {
            header('Location: ' . $result['payment_url']);
        } else {
            // Push USSD
            $_SESSION['pending_order_number'] = $orderNumber;
            header('Location: service_card.php?pending_push=1');
        }
        exit;
    } else {
        $error = "Erreur FlexPay : " . ($result['message'] ?? "L'initiation du paiement a échoué.");
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ma Carte de Service - WMA HUB</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/jpeg" href="../../asset/placeholder.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; min-height: 100vh; margin: 0; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; padding: 2rem 1.5rem; display: flex; flex-direction: column; }
        .main-content { margin-left: 280px; padding: 3rem; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; transform: translateX(5px); }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 2rem; padding: 2.5rem; }
        .input-glass { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 1rem; color: #fff; padding: 1rem; width: 100%; outline: none; transition: all 0.3s ease; }
        .input-glass:focus { border-color: #ff6600; background: rgba(255, 255, 255, 0.06); }
        .btn-premium { background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%); color: #fff; font-weight: 800; border-radius: 1rem; padding: 1rem 2rem; transition: all 0.3s ease; box-shadow: 0 10px 20px -5px rgba(255, 102, 0, 0.4); }
        .btn-premium:hover { transform: translateY(-2px); box-shadow: 0 15px 30px -5px rgba(255, 102, 0, 0.6); }
        #wma-global-loader { position: fixed; inset: 0; background: #0a0a0c; display: flex; align-items: center; justify-content: center; z-index: 100000; transition: opacity 0.5s ease; }
        .loader-spin { width: 40px; height: 40px; border: 3px solid rgba(255, 102, 0, 0.1); border-top-color: #ff6600; border-radius: 50%; animation: wma-spin 1s linear infinite; }
        @keyframes wma-spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div id="wma-global-loader"><div class="loader-spin"></div></div>
    <div class="bg-glow"></div>

    <aside class="sidebar">
        <div class="flex items-center gap-4 mb-12 px-2">
            <img src="../../asset/trans.png" alt="Logo" class="h-10">
            <div>
                <h1 class="text-xl font-black bg-gradient-to-r from-orange-500 to-orange-300 bg-clip-text text-transparent tracking-tighter leading-tight">WMA HUB</h1>
                <p class="text-[8px] text-gray-500 font-bold uppercase tracking-[1px] -mt-1 flex items-center gap-1">
                    Distributeur Indépendant
                    <?php if ($user['is_certified']): ?>
                        <i class="fas fa-check-decagram text-cyan-400 text-[10px]"></i>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <nav class="flex-1">
            <a href="index.php" class="nav-link"><i class="fas fa-th-large"></i> Vue d'ensemble</a>
            <a href="artists.php" class="nav-link"><i class="fas fa-users"></i> Mes Artistes</a>
            <a href="catalogue.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'catalogue.php' ? 'active' : '' ?>"><i class="fas fa-compact-disc"></i> Mon Catalogue</a>
            <a href="distributed_projects.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'distributed_projects.php' ? 'active' : '' ?>"><i class="fas fa-check-circle"></i> Projets Distribués</a>
            <a href="submit.php" class="nav-link"><i class="fas fa-upload"></i> Distribuer</a>
            <a href="service_card.php" class="nav-link active"><i class="fas fa-id-card"></i> Ma Carte Service</a>
            <a href="royalties.php" class="nav-link"><i class="fas fa-wallet"></i> Royalties</a>
            <a href="profile.php" class="nav-link"><i class="fas fa-user-circle"></i> Mon Profil</a>
        </nav>
        <div class="mt-auto pt-6 border-t border-white/5">
            <a href="../../auth/logout.php" class="nav-link !text-red-500"><i class="fas fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="mb-12">
            <h2 class="text-5xl font-black tracking-tighter leading-none">Ma Carte de <span class="text-orange-500">Service</span></h2>
            <p class="text-gray-400 mt-3 text-lg">Identifiez-vous comme partenaire officiel de WMA HUB.</p>
        </header>

        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl mb-8 flex items-center gap-3">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['pending_push'])): ?>
            <div class="bg-orange-500/10 border border-orange-500/20 text-orange-500 p-6 rounded-xl mb-8 text-center animate-pulse">
                <h4 class="font-bold mb-2"><i class="fas fa-mobile-alt mr-2"></i> Paiement Mobile en cours</h4>
                <p class="text-xs">Un message a été envoyé sur votre téléphone. Validez-le pour confirmer votre commande de carte.</p>
                <a href="service_card.php" class="inline-block mt-4 text-[10px] uppercase font-black tracking-widest border-b border-orange-500">Actualiser mon statut</a>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-500/10 border border-green-500/20 text-green-500 p-4 rounded-xl mb-8 flex items-center gap-3">
                <i class="fas fa-check-circle"></i> Vos informations ont été enregistrées. Veuillez finaliser le paiement.
            </div>
        <?php endif; ?>

        <?php if (!$card || $card['status'] === 'rejected'): ?>
            <div class="glass-card">
                <h3 class="text-2xl font-bold mb-8">Demander ma Carte officielle</h3>
                <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-6">
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2 block">Nom complet (Tel que sur la carte)</label>
                            <input type="text" name="full_name" required class="input-glass" value="<?= htmlspecialchars($user['name']) ?>">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2 block">Fonction</label>
                            <input type="text" name="function" required class="input-glass" value="Distributeur Indépendant">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2 block">Département</label>
                            <input type="text" name="department" required class="input-glass" value="Distribution Digital">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2 block">Groupe Sanguin</label>
                                <select name="blood_group" class="input-glass">
                                    <option value="">N/A</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2 block">Contact d'urgence</label>
                                <input type="tel" name="emergency_contact" required class="input-glass" placeholder="+243 ...">
                            </div>
                        </div>
                    </div>
                    <div class="space-y-6">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2 block">Photo de profil (Format passeport)</label>
                        <div class="border-2 border-dashed border-white/10 rounded-2xl p-8 text-center hover:border-orange-500/50 transition-all cursor-pointer group" onclick="document.getElementById('photoInput').click()">
                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-600 mb-4 group-hover:text-orange-500 transition-all"></i>
                            <p class="text-sm text-gray-500" id="photoName">Cliquez pour choisir une photo</p>
                            <input type="file" name="photo" id="photoInput" class="hidden" accept="image/*" onchange="document.getElementById('photoName').textContent = this.files[0].name">
                        </div>
                        <p class="text-[10px] text-gray-600 italic">Note: La création de la carte coûte <?= getSetting('card_fee_usd', 1) ?>$ (<?= number_format(getSetting('card_fee_cdf', 3000), 0, '.', ' ') ?> FC) pour les frais techniques.</p>
                        <button type="submit" name="submit_card" class="btn-premium w-full">Enregistrer et Continuer Vers le Paiement</button>
                    </div>
                </form>
            </div>
        <?php elseif ($card['status'] === 'pending_payment'): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <div class="glass-card">
                    <div class="w-16 h-16 bg-orange-500/10 rounded-full flex items-center justify-center text-orange-500 text-3xl mb-6">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Finaliser le paiement</h3>
                    <p class="text-gray-400 mb-8">Votre demande est prête. Une fois le paiement de <?= getSetting('card_fee_usd', 1) ?>$ (<?= number_format(getSetting('card_fee_cdf', 3000), 0, '.', ' ') ?> FC) effectué, notre équipe validera votre carte sous 24h.</p>
                    
                    <div class="mb-8">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-4">Choisir l'opérateur</label>
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

                    <form method="POST" class="space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <label class="cursor-pointer">
                                <input type="radio" name="currency" value="USD" checked class="sr-only peer">
                                <div class="p-4 rounded-xl border border-white/10 bg-white/5 text-center peer-checked:border-orange-500 peer-checked:bg-orange-500/10 transition-all">
                                    <span class="font-bold"><?= getSetting('card_fee_usd', 1) ?> USD</span>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="currency" value="CDF" class="sr-only peer">
                                <div class="p-4 rounded-xl border border-white/10 bg-white/5 text-center peer-checked:border-orange-500 peer-checked:bg-orange-500/10 transition-all">
                                    <span class="font-bold"><?= number_format(getSetting('card_fee_cdf', 3000), 0, '.', ' ') ?> FC</span>
                                </div>
                            </label>
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2 block">Numéro Mobile Money (Airtel, M-Pesa, Orange)</label>
                            <input type="tel" name="phone" required class="input-glass" placeholder="0810000000" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>
                        <button type="submit" name="pay_card" class="btn-premium w-full">Payer Maintenant</button>
                    </form>
                </div>

                <!-- Preview of data -->
                <div class="glass-card border-orange-500/20 bg-orange-500/5">
                    <h4 class="text-sm font-black uppercase tracking-widest text-orange-500 mb-6">Aperçu des informations</h4>
                    <div class="flex items-center gap-6 mb-8">
                        <div class="w-24 h-24 rounded-2xl overflow-hidden border border-white/10 bg-white/5">
                            <img src="../../<?= $card['photo_path'] ?: 'asset/aspi.jpg' ?>" class="w-full h-full object-cover">
                        </div>
                        <div>
                            <p class="text-2xl font-black"><?= htmlspecialchars($card['full_name']) ?></p>
                            <p class="text-sm text-gray-400 font-bold"><?= htmlspecialchars($card['role']) ?></p>
                            <p class="text-xs text-orange-500 uppercase font-black tracking-tighter mt-1"><?= htmlspecialchars($card['department']) ?></p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 text-xs">
                        <div>
                            <p class="text-gray-500 uppercase font-black text-[9px] tracking-widest">Groupe Sanguin</p>
                            <p class="font-bold mt-1"><?= $card['blood_group'] ?: 'N/A' ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500 uppercase font-black text-[9px] tracking-widest">Urgence</p>
                            <p class="font-bold mt-1"><?= htmlspecialchars($card['emergency_contact']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif ($card['status'] === 'pending'): ?>
            <div class="glass-card text-center py-20">
                <div class="w-20 h-20 bg-amber-500/10 rounded-full flex items-center justify-center text-amber-500 text-4xl mx-auto mb-8 animate-pulse">
                    <i class="fas fa-clock"></i>
                </div>
                <h3 class="text-3xl font-black mb-4">En attente de validation</h3>
                <p class="text-gray-400 max-w-md mx-auto">Votre paiement a été reçu ! Votre carte est actuellement en cours de revue par l'administration. Elle sera disponible ici dès validation.</p>
                <div class="mt-12 pt-8 border-t border-white/5">
                    <p class="text-[10px] text-gray-600 font-black uppercase tracking-widest">Généralement validé sous 24h</p>
                </div>
            </div>
        <?php elseif ($card['status'] === 'approved'): ?>
             <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <div class="glass-card bg-green-500/5 border-green-500/20">
                    <div class="w-16 h-16 bg-green-500/10 rounded-full flex items-center justify-center text-green-500 text-3xl mb-6">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">Carte Validée !</h3>
                    <p class="text-gray-300 mb-8">Votre carte de service officielle est active. Vous pouvez l'utiliser pour prouver votre partenariat avec WMA HUB.</p>
                    
                    <div class="space-y-4">
                        <div class="p-4 rounded-xl bg-white/5 border border-white/10 flex justify-between items-center">
                            <span class="text-xs text-gray-500 uppercase font-black">Matricule</span>
                            <span class="font-mono font-bold"><?= $card['matricule'] ?: 'WMA-' . str_pad($card['id'], 4, '0', STR_PAD_LEFT) ?></span>
                        </div>
                        <div class="p-4 rounded-xl bg-white/5 border border-white/10 flex justify-between items-center">
                            <span class="text-xs text-gray-500 uppercase font-black">Expiration</span>
                            <span class="font-bold"><?= $card['expires_at'] ? date('d/m/Y', strtotime($card['expires_at'])) : '31/12/' . date('Y') ?></span>
                        </div>
                    </div>
                    
                    <button class="btn-premium w-full mt-8 opacity-50 cursor-not-allowed">Télécharger la Carte (Bientôt)</button>
                </div>
                
                <!-- Front Card Preview -->
                <div class="glass-card relative overflow-hidden h-[350px] flex flex-col justify-between p-10 border-orange-500/30">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-orange-500/10 rounded-full -mr-16 -mt-16 blur-3xl"></div>
                    <div class="flex justify-between items-start">
                        <img src="../../asset/trans.png" class="h-10">
                        <div class="text-right">
                            <p class="text-[10px] font-black uppercase tracking-widest text-orange-500">Service Card</p>
                            <p class="text-[8px] text-gray-500">Official Partner</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-6">
                        <div class="w-24 h-24 rounded-2xl border-2 border-orange-500/50 p-1">
                            <img src="../../<?= $card['photo_path'] ?: 'asset/aspi.jpg' ?>" class="w-full h-full object-cover rounded-xl">
                        </div>
                        <div>
                            <p class="text-2xl font-black tracking-tighter leading-none"><?= htmlspecialchars($card['full_name']) ?></p>
                            <p class="text-xs font-bold text-gray-400 mt-2"><?= htmlspecialchars($card['role']) ?></p>
                            <p class="text-[10px] font-black uppercase tracking-widest text-orange-500 mt-1"><?= htmlspecialchars($card['department']) ?></p>
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-end">
                        <div class="text-[10px] font-mono text-gray-600">
                             <?= $card['matricule'] ?: 'WMA-' . str_pad($card['id'], 4, '0', STR_PAD_LEFT) ?>
                        </div>
                        <div class="w-12 h-12 bg-white flex items-center justify-center rounded-lg">
                            <i class="fas fa-qrcode text-black text-2xl"></i>
                        </div>
                    </div>
                </div>
             </div>
        <?php endif; ?>
    </main>

    <script>
        window.addEventListener('load', () => {
            const loader = document.getElementById('wma-global-loader');
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(() => loader.style.display = 'none', 500);
            }
        });

        // Polling pour le statut du paiement
        <?php if (isset($_GET['pending_push']) && isset($_SESSION['pending_order_number'])): ?>
        console.log("Démarrage du polling pour : <?= $_SESSION['pending_order_number'] ?>");
        const checkStatus = async () => {
            try {
                const response = await fetch(`../../api/check-payment-status.php?orderNumber=<?= $_SESSION['pending_order_number'] ?>`);
                const data = await response.json();
                
                if (data.status === 'success') {
                    clearInterval(statusInterval);
                    Swal.fire({
                        icon: 'success',
                        title: 'Paiement Validé !',
                        text: 'Votre carte de service sera traitée sous 24h.',
                        showConfirmButton: false,
                        timer: 2500,
                        timerProgressBar: true,
                        background: '#1a1a1a',
                        color: '#fff'
                    }).then(() => {
                        window.location.href = 'service_card.php?success=1';
                    });
                }
            } catch (error) {
                console.error("Erreur de vérification :", error);
            }
        };

        // Vérifier toutes les 2 secondes (détection rapide)
        const statusInterval = setInterval(checkStatus, 2000);
        // Arrêter après 5 minutes
        setTimeout(() => {
            clearInterval(statusInterval);
            console.log("Polling arrêté après timeout.");
        }, 300000);
        <?php endif; ?>
    </script>
</body>
</html>
