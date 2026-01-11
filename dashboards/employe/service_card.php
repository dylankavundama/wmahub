<?php
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employe') {
    header('Location: ../../auth/login.php');
    exit;
}

$db = getDBConnection();
$userId = $_SESSION['user_id'];

// Get existing card info
$stmt = $db->prepare("SELECT * FROM service_cards WHERE user_id = ?");
$stmt->execute([$userId]);
$card = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMA HUB - Carte de Service</title>
    <link rel="icon" type="image/png" href="../../asset/icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; min-height: 100vh; overflow-x: hidden; }
        .bg-glow { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .glow-spot { position: fixed; width: 40vw; height: 40vw; background: radial-gradient(circle, rgba(255, 102, 0, 0.05) 0%, transparent 70%); border-radius: 50%; z-index: -1; filter: blur(80px); pointer-events: none; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; display: flex; flex-direction: column; padding: 2rem 1.5rem; }
        .main-content { margin-left: 280px; padding: 2rem; min-height: 100vh; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; font-size: 0.9rem; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; transform: translateX(5px); }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 1.5rem; padding: 1.5rem; }
        
        /* 3D Card Styles */
        .card-container { perspective: 1000px; width: 350px; height: 500px; cursor: pointer; }
        .card-inner { position: relative; width: 100%; height: 100%; text-align: center; transition: transform 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275); transform-style: preserve-3d; }
        .card-container.flipped .card-inner { transform: rotateY(180deg); }
        .card-face { position: absolute; width:100%; height: 100%; -webkit-backface-visibility: hidden; backface-visibility: hidden; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.5); overflow: hidden; display: flex; flex-direction: column; }
        .card-front { background: linear-gradient(135deg, #1a1a2e 0%, #0a0a0c 100%); border: 1px solid rgba(255, 102, 0, 0.2); }
        .card-back { background: linear-gradient(-135deg, #1a1a2e 0%, #0a0a0c 100%); border: 1px solid rgba(255, 102, 0, 0.2); transform: rotateY(180deg); }
        
        /* Card Design Elements */
        .card-header { height: 100px; background: linear-gradient(90deg, #ff6600, #ff9933); clip-path: polygon(0 0, 100% 0, 100% 70%, 0 100%); padding: 15px; display: flex; align-items: flex-start; justify-content: space-between; }
        .card-title { font-weight: 900; letter-spacing: -1px; font-size: 1.2rem; color: #fff; }
        .photo-placeholder { width: 140px; height: 140px; border-radius: 15px; border: 4px solid #fff; background: #222; margin: -50px auto 20px; overflow: hidden; position: relative; z-index: 10; box-shadow: 0 8px 16px rgba(0,0,0,0.3); }
        .info-row { padding: 0 25px; margin-bottom: 15px; text-align: left; }
        .info-label { font-size: 10px; text-transform: uppercase; color: #ff6600; font-weight: 800; }
        .info-value { font-size: 14px; font-weight: 600; color: #fff; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 4px; }
        
        .qr-placeholder { width: 80px; height: 80px; background: #fff; margin: 20px auto; border-radius: 10px; padding: 5px; }
        
        @media (max-width: 1024px) { .sidebar { transform: translateX(-100%); width: 0; padding: 0; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <div class="bg-glow"></div>
    <div id="glow" class="glow-spot"></div>

    <aside class="sidebar">
        <div class="flex items-center gap-4 mb-12 px-2">
            <img src="../../asset/trans.png" alt="Logo" class="h-10">
            <h1 class="text-xl font-black bg-gradient-to-r from-orange-500 to-orange-300 bg-clip-text text-transparent tracking-tighter">WMA STAFF</h1>
        </div>
        <nav class="flex-1">
            <a href="index.php" class="nav-link"><i class="fas fa-tasks"></i> Mes Missions</a>
            <a href="service_card.php" class="nav-link active"><i class="fas fa-id-card"></i> Carte de Service</a>
        </nav>
        <div class="mt-auto pt-6 border-t border-white/5">
            <div class="flex items-center gap-4 mb-8 px-2">
                <div class="w-10 h-10 rounded-full bg-orange-500/10 border border-orange-500/20 flex items-center justify-center text-orange-500"><i class="fas fa-user-cog"></i></div>
                <div>
                    <p class="text-sm font-bold text-white"><?= explode(' ', $_SESSION['user_name'])[0] ?></p>
                    <p class="text-[10px] text-gray-500 uppercase font-black tracking-widest">Employé</p>
                </div>
            </div>
            <a href="../../auth/logout.php" class="nav-link !text-red-500 hover:!bg-red-500/10"><i class="fas fa-power-off"></i> Déconnexion</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="flex flex-col md:flex-row md:items-center justify-between gap-8 mb-12">
            <div>
                <h2 class="text-4xl font-black tracking-tighter text-white">Carte de <span class="text-orange-500">Service</span></h2>
                <p class="text-gray-400 mt-2">Générez et gérez votre identification officielle.</p>
            </div>
        </header>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-12 items-start">
            <!-- Form Section -->
            <div class="glass-card">
                <h3 class="text-xl font-bold mb-8 flex items-center gap-3"><i class="fas fa-edit text-orange-500"></i> Informations de la carte</h3>
                <form id="cardForm" enctype="multipart/form-data" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-500 mb-2">Nom Complet</label>
                            <input type="text" name="full_name" value="<?= htmlspecialchars($card['full_name'] ?? $_SESSION['user_name']) ?>" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-orange-500 outline-none transition-all" required>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-500 mb-2">Fonction / Poste</label>
                            <input type="text" name="role_title" value="<?= htmlspecialchars($card['role'] ?? 'Agent WMA') ?>" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-orange-500 outline-none transition-all" required>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-500 mb-2">Matricule</label>
                            <input type="text" name="matricule" value="<?= htmlspecialchars($card['matricule'] ?? '') ?>" placeholder="Ex: WMA-2024-001" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-orange-500 outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-500 mb-2">Département</label>
                            <input type="text" name="department" value="<?= htmlspecialchars($card['department'] ?? 'Distribution') ?>" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-orange-500 outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-500 mb-2">Groupe Sanguin</label>
                            <select name="blood_group" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-orange-500 outline-none transition-all">
                                <option value="" <?= !($card['blood_group'] ?? '') ? 'selected' : '' ?>>Non spécifié</option>
                                <?php foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg): ?>
                                    <option value="<?= $bg ?>" <?= ($card['blood_group'] ?? '') === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-500 mb-2">Contact d'urgence</label>
                            <input type="text" name="emergency_contact" value="<?= htmlspecialchars($card['emergency_contact'] ?? '') ?>" placeholder="Nom & Téléphone" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm focus:border-orange-500 outline-none transition-all">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase text-gray-500 mb-2">Photo d'identité</label>
                        <input type="file" name="photo" accept="image/*" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm file:bg-orange-500 file:border-none file:rounded-lg file:text-white file:px-4 file:py-1 file:mr-4 file:text-[10px] file:font-black file:uppercase">
                        <input type="hidden" name="existing_photo" value="<?= $card['photo_path'] ?? '' ?>">
                    </div>

                    <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-4 rounded-xl transition-all shadow-lg shadow-orange-500/20 flex items-center justify-center gap-3">
                        <i class="fas fa-save text-xl"></i>
                        Soumettre / Mettre à jour la carte
                    </button>
                    <p class="text-center text-[10px] text-gray-500 uppercase font-black tracking-widest mt-4">Note: Toute modification rendra la carte invalide jusqu'à validation par l'admin.</p>
                </form>
            </div>

            <!-- Preview Section -->
            <div class="flex flex-col items-center">
                <div class="mb-6 flex items-center gap-4">
                    <span class="text-[10px] font-black uppercase px-3 py-1 rounded-full bg-orange-500/10 text-orange-500 border border-orange-500/20">Aperçu 3D interactif</span>
                    <?php if (($card['status'] ?? '') === 'approved'): ?>
                        <span class="text-[10px] font-black uppercase px-3 py-1 rounded-full bg-green-500/10 text-green-500 border border-green-500/20">Approuvée par l'admin</span>
                    <?php else: ?>
                        <span class="text-[10px] font-black uppercase px-3 py-1 rounded-full bg-amber-500/10 text-amber-500 border border-amber-500/20">En attente de validation</span>
                    <?php endif; ?>
                </div>

                <div class="card-container" onclick="this.classList.toggle('flipped')">
                    <div class="card-inner" id="serviceCard">
                        <!-- FRONT FACE -->
                        <div class="card-face card-front" id="cardFront">
                            <div class="card-header">
                                <div class="card-title">WMA STAFF</div>
                                <img src="../../asset/trans.png" class="h-8 opacity-90 shadow-sm" alt="WMA">
                            </div>
                            <div class="photo-placeholder">
                                <img id="previewPhoto" src="<?= $card['photo_path'] ? '../../' . $card['photo_path'] : '../../asset/aspi.jpg' ?>" class="w-full h-full object-cover">
                            </div>
                            <h4 id="previewName" class="text-xl font-black mb-1 uppercase tracking-tighter"><?= htmlspecialchars($card['full_name'] ?? $_SESSION['user_name']) ?></h4>
                            <p id="previewRole" class="text-orange-500 font-bold text-xs uppercase mb-8"><?= htmlspecialchars($card['role'] ?? 'Agent WMA') ?></p>
                            
                            <div class="info-row">
                                <div class="info-label">Matricule</div>
                                <div id="previewMatricule" class="info-value"><?= htmlspecialchars($card['matricule'] ?? 'N/A') ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Département</div>
                                <div id="previewDept" class="info-value"><?= htmlspecialchars($card['department'] ?? 'Général') ?></div>
                            </div>

                            <div class="mt-auto p-4 border-t border-white/5 flex justify-between items-center bg-white/5">
                                <span class="text-[8px] text-gray-500 font-black">ID OFFICIEL WMA HUB</span>
                                <span class="text-[8px] text-white font-bold italic">Member since <?= date('Y', strtotime($_SESSION['user_created_at'] ?? 'now')) ?></span>
                            </div>
                        </div>

                        <!-- BACK FACE -->
                        <div class="card-face card-back" id="cardBack">
                            <div class="p-6 h-full flex flex-col">
                                <div class="flex justify-between items-center mb-10">
                                    <img src="../../asset/trans.png" class="h-6 opacity-40">
                                    <span class="text-[10px] font-bold text-orange-500">WM-Staff-ID</span>
                                </div>

                                <div class="space-y-6 flex-1">
                                    <div class="text-left">
                                        <div class="info-label">Groupe Sanguin</div>
                                        <div id="previewBG" class="font-bold text-xl text-red-500"><?= htmlspecialchars($card['blood_group'] ?? '??') ?></div>
                                    </div>
                                    <div class="text-left">
                                        <div class="info-label">Contact d'urgence</div>
                                        <div id="previewEmergency" class="text-xs font-semibold"><?= htmlspecialchars($card['emergency_contact'] ?? 'Non fourni') ?></div>
                                    </div>
                                </div>

                                <div class="qr-placeholder flex items-center justify-center">
                                    <i class="fas fa-qrcode text-4xl text-black"></i>
                                </div>
                                <p class="text-[8px] text-gray-500 mt-4 leading-relaxed">Cette carte est strictement personnelle. <br> En cas de perte, veuillez contacter l'administration de WMA HUB. Toute utilisation frauduleuse est passible de poursuites.</p>
                                
                                <div class="mt-auto pt-4 border-t border-white/5">
                                    <img src="../../asset/icon.png" class="h-4 mx-auto opacity-20">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex gap-4">
                    <?php if (($card['status'] ?? '') === 'approved'): ?>
                        <button onclick="downloadCard('jpg')" class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-xl font-bold transition-all flex items-center gap-2">
                            <i class="fas fa-download"></i> Télécharger (JPG)
                        </button>
                    <?php else: ?>
                        <div class="bg-white/5 border border-white/10 text-gray-500 px-6 py-3 rounded-xl font-bold flex items-center gap-2 cursor-not-allowed">
                            <i class="fas fa-lock text-sm"></i> Téléchargement indisponible
                        </div>
                    <?php endif; ?>
                    <button onclick="document.querySelector('.card-container').classList.toggle('flipped')" class="bg-white/5 hover:bg-white/10 text-white px-6 py-3 rounded-xl font-bold border border-white/10 transition-all flex items-center gap-2">
                        <i class="fas fa-sync"></i> Retourner
                    </button>
                </div>
            </div>
        </div>
    </main>

    <script>
        const glow = document.getElementById('glow');
        document.addEventListener('mousemove', (e) => { glow.style.left = (e.clientX - 200) + 'px'; glow.style.top = (e.clientY - 200) + 'px'; });

        // Live Preview
        const form = document.getElementById('cardForm');
        form.addEventListener('input', (e) => {
            const formData = new FormData(form);
            document.getElementById('previewName').innerText = formData.get('full_name') || 'NOM COMPLET';
            document.getElementById('previewRole').innerText = formData.get('role_title') || 'FONCTION';
            document.getElementById('previewMatricule').innerText = formData.get('matricule') || 'N/A';
            document.getElementById('previewDept').innerText = formData.get('department') || 'GÉNÉRAL';
            document.getElementById('previewBG').innerText = formData.get('blood_group') || '??';
            document.getElementById('previewEmergency').innerText = formData.get('emergency_contact') || 'NON FOURNI';
        });

        // Photo Preview
        form.querySelector('input[name="photo"]').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewPhoto').src = e.target.result;
                }
                reader.readAsDataURL(this.files[0]);
            }
        });

        // Submit Form
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            try {
                const response = await fetch('../../api/service_cards.php?action=submit', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    alert('Demande de carte mise à jour ! En attente de validation admin.');
                    window.location.reload();
                } else {
                    alert('Erreur: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Une erreur est survenue.');
            }
        });

        // Download Card
        async function downloadCard(format) {
            const cardInner = document.querySelector('.card-inner');
            const isFlipped = document.querySelector('.card-container').classList.contains('flipped');
            
            // Ensure front is visible for capture
            if (isFlipped) document.querySelector('.card-container').classList.remove('flipped');
            
            // Front Capture
            const canvasFront = await html2canvas(document.getElementById('cardFront'), { scale: 2 });
            
            // Flip for back capture
            document.querySelector('.card-container').classList.add('flipped');
            // Wait for flip animation
            await new Promise(r => setTimeout(r, 800));
            
            const canvasBack = await html2canvas(document.getElementById('cardBack'), { scale: 2 });
            
            // Download logic
            const link = document.createElement('a');
            link.download = `wma-card-${format === 'jpg' ? 'front.jpg' : 'back.jpg'}`;
            link.href = canvasFront.toDataURL("image/jpeg", 0.9);
            link.click();
            
            link.download = `wma-card-back.jpg`;
            link.href = canvasBack.toDataURL("image/jpeg", 0.9);
            link.click();
            
            // Reset state
            if (!isFlipped) document.querySelector('.card-container').classList.remove('flipped');
        }
    </script>
</body>
</html>
