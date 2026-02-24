<?php
require_once __DIR__ . '/../../includes/config.php';

// Sécurité : Accès restreint à l'admin uniquement
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = getDBConnection();

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_withdrawal'])) {
    $withdrawal_id = $_POST['withdrawal_id'];
    $new_status = $_POST['status'];
    $proof_file = null;
    
    try {
        // Fetch request info for notification
        $stmt_info = $db->prepare("SELECT w.*, u.name as user_name FROM withdrawals w JOIN users u ON w.user_id = u.id WHERE w.id = ?");
        $stmt_info->execute([$withdrawal_id]);
        $info = $stmt_info->fetch();
        
        if ($info) {
            // Handle Proof Upload if status is approved
            if ($new_status === 'approved' && isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/uploads/payouts/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $ext = pathinfo($_FILES['proof_image']['name'], PATHINFO_EXTENSION);
                $proof_file = time() . '_proof_' . $withdrawal_id . '.' . $ext;
                
                if (!move_uploaded_file($_FILES['proof_image']['tmp_name'], $upload_dir . $proof_file)) {
                    throw new Exception("Erreur lors du téléchargement de la preuve.");
                }
            }

            $stmt = $db->prepare("UPDATE withdrawals SET status = ?, proof_file = ?, processed_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$new_status, $proof_file ?: $info['proof_file'], $withdrawal_id]);
            
            $msg = ($new_status === 'approved') 
                ? "Votre demande de retrait de " . $info['amount'] . " $ a été approuvée et payée." 
                : "Votre demande de retrait de " . $info['amount'] . " $ a été rejetée.";
            
            createNotification($info['user_id'], 'payout_update', $msg, $withdrawal_id);
            header("Location: payouts.php?success=1");
            exit;
        }
    } catch (Exception $e) {
        $error = "Erreur : " . $e->getMessage();
    }
}

// Récupérer les demandes (en attente d'abord)
$stmt = $db->query("SELECT w.*, u.name as user_name, u.email as user_email, u.role as user_role 
                    FROM withdrawals w 
                    JOIN users u ON w.user_id = u.id 
                    ORDER BY CASE WHEN w.status = 'pending' THEN 0 ELSE 1 END, w.created_at DESC");
$withdrawals = $stmt->fetchAll();

$pageTitle = 'Gestion des Paiements - WMA Hub';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/jpeg" href="../../asset/placeholder.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../css/admin-shared.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; min-height: 100vh; margin: 0; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; padding: 2rem 1.5rem; display: flex; flex-direction: column; }
        .main-content { margin-left: 280px; padding: 2rem; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; transform: translateX(5px); }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 2rem; }
    </style>
</head>
<body>
    <div class="bg-glow"></div>

    <aside class="sidebar">
        <div class="flex items-center gap-4 mb-12 px-2">
            <img src="../../asset/trans.png" alt="Logo" class="h-10">
            <div>
                <h1 class="text-xl font-black bg-gradient-to-r from-orange-500 to-orange-300 bg-clip-text text-transparent tracking-tighter leading-tight">WMA HUB</h1>
                <p class="text-[8px] text-gray-500 font-bold uppercase tracking-[1px] -mt-1">Administration</p>
            </div>
        </div>
        <nav class="flex-1">
            <a href="index.php" class="nav-link"><i class="fas fa-layer-group"></i> Gestion Projets</a>
            <a href="subscriptions.php" class="nav-link"><i class="fas fa-crown"></i> Abonnements</a>
            <a href="revenues.php" class="nav-link"><i class="fas fa-wallet"></i> Revenus Gérés</a>
            <a href="payouts.php" class="nav-link active"><i class="fas fa-money-bill-transfer"></i> Retraits & Payouts</a>
            <a href="employees.php" class="nav-link"><i class="fas fa-users-cog"></i> Équipe & Staff</a>
            <a href="finance.php" class="nav-link"><i class="fas fa-chart-pie"></i> Rapports Financiers</a>
        </nav>
        <div class="mt-auto pt-6 border-t border-white/5">
            <a href="../../auth/logout.php" class="nav-link !text-red-500"><i class="fas fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="mb-12">
            <h2 class="text-4xl font-black tracking-tighter">Gestion des <span class="text-orange-500">Retraits</span></h2>
            <p class="text-gray-400 mt-2">Validez ou refusez les demandes de paiement des utilisateurs.</p>
        </header>

        <div class="glass-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-white/5">
                        <tr class="text-[10px] text-gray-500 font-black uppercase tracking-[2px]">
                            <th class="px-8 py-6">Utilisateur</th>
                            <th>Montant</th>
                            <th>Méthode & Détails</th>
                            <th>Date</th>
                            <th class="px-8 text-center">Actions / Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php foreach ($withdrawals as $w): ?>
                            <tr class="hover:bg-white/[0.02] transition-all">
                                <td class="px-8 py-6">
                                    <p class="font-bold text-white"><?= htmlspecialchars($w['user_name']) ?></p>
                                    <p class="text-[10px] text-gray-500 uppercase font-black"><?= $w['user_role'] ?></p>
                                    <p class="text-[10px] text-gray-600"><?= htmlspecialchars($w['user_email']) ?></p>
                                </td>
                                <td>
                                    <p class="text-xl font-black text-white"><?= number_format($w['amount'], 2) ?> $</p>
                                </td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-orange-500/10 flex items-center justify-center text-orange-500"><i class="fas fa-wallet text-xs"></i></div>
                                        <div>
                                            <p class="text-sm font-bold text-orange-500"><?= htmlspecialchars($w['method']) ?></p>
                                            <p class="text-[10px] text-gray-400 font-mono"><?= htmlspecialchars($w['account_details']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-xs text-gray-500">
                                    <?= date('d/m/Y H:i', strtotime($w['created_at'])) ?>
                                </td>
                                <td class="px-8 py-6 text-center">
                                    <?php if ($w['status'] === 'pending'): ?>
                                        <div class="flex gap-2 justify-center">
                                            <button onclick="openApproveModal(<?= $w['id'] ?>, '<?= $w['amount'] ?>', '<?= htmlspecialchars($w['user_name']) ?>')" class="px-4 py-2 bg-green-500/10 text-green-500 border border-green-500/20 rounded-lg font-black text-[10px] uppercase hover:bg-green-500 hover:text-white transition-all">Approuver</button>
                                            
                                            <form method="POST" onsubmit="return confirm('Rejeter cette demande ?')">
                                                <input type="hidden" name="withdrawal_id" value="<?= $w['id'] ?>">
                                                <input type="hidden" name="update_withdrawal" value="1">
                                                <button name="status" value="rejected" class="px-4 py-2 bg-red-500/10 text-red-500 border border-red-500/20 rounded-lg font-black text-[10px] uppercase hover:bg-red-500 hover:text-white transition-all">Rejeter</button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <?php
                                        $st = $w['status'];
                                        $stClass = ($st === 'approved') ? 'text-green-500 border-green-500/20' : 'text-red-500 border-red-500/20';
                                        ?>
                                        <span class="px-3 py-1 rounded-full border <?= $stClass ?> font-black text-[8px] uppercase">
                                            <?= $st === 'approved' ? 'PAYÉ' : 'REJETÉ' ?>
                                        </span>
                                        <?php if ($w['proof_file']): ?>
                                            <a href="uploads/payouts/<?= $w['proof_file'] ?>" target="_blank" class="block text-[8px] text-cyan-400 mt-2 underline uppercase font-bold tracking-widest"><i class="fas fa-file-invoice-dollar mr-1"></i>Preuve Jointe</a>
                                        <?php endif; ?>
                                        <?php if ($w['processed_at']): ?>
                                            <p class="text-[8px] text-gray-600 mt-1 uppercase">Le <?= date('d/m/Y', strtotime($w['processed_at'])) ?></p>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($withdrawals)): ?>
                            <tr><td colspan="5" class="py-20 text-center text-gray-600 uppercase font-black tracking-widest italic">Aucune demande trouvée</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    </main>

    <!-- Modal Approbation avec Preuve -->
    <div id="approveModal" class="hidden fixed inset-0 z-[200] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-md" onclick="closeApproveModal()"></div>
        <div class="glass-card max-w-md w-full relative z-[210] p-10 border-green-500/20">
            <h3 class="text-2xl font-black mb-2 text-green-500">Valider le <span class="text-white">Paiement</span></h3>
            <p id="modalInfo" class="text-gray-400 text-sm mb-8"></p>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="withdrawal_id" id="modalWithdrawalId">
                <input type="hidden" name="status" value="approved">
                <input type="hidden" name="update_withdrawal" value="1">
                
                <div>
                    <label class="text-[10px] font-black uppercase text-gray-500 block mb-2">Capture d'écran du transfert (Optionnel)</label>
                    <input type="file" name="proof_image" accept="image/*" class="w-full bg-white/5 border border-white/10 rounded-xl px-5 py-3 outline-none focus:border-green-500 transition-all">
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="button" onclick="closeApproveModal()" class="flex-1 bg-white/5 hover:bg-white/10 text-white font-bold py-4 rounded-xl transition-all">Annuler</button>
                    <button type="submit" class="flex-1 bg-green-500 hover:bg-green-600 text-white font-bold py-4 rounded-xl transition-all shadow-lg shadow-green-500/20">Marquer comme Payé</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openApproveModal(id, amount, name) {
            document.getElementById('modalWithdrawalId').value = id;
            document.getElementById('modalInfo').innerText = "Vous confirmez avoir envoyé " + amount + " $ à " + name + " ?";
            document.getElementById('approveModal').classList.remove('hidden');
        }
        function closeApproveModal() {
            document.getElementById('approveModal').classList.add('hidden');
        }
    </script>
</body>
</html>
