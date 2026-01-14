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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; min-height: 100vh; overflow-x: hidden; }
        .bg-glow { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .glow-spot { position: fixed; width: 40vw; height: 40vw; background: radial-gradient(circle, rgba(255, 102, 0, 0.05) 0%, transparent 70%); border-radius: 50%; z-index: -1; filter: blur(80px); pointer-events: none; }
        .sidebar { width: 280px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); height: 100vh; position: fixed; left: 0; top: 0; z-index: 100; display: flex; flex-direction: column; padding: 2rem 1.5rem; transition: all 0.3s ease; }
        .main-content { margin-left: 280px; padding: 2rem; min-height: 100vh; transition: all 0.3s ease; }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.25rem; color: rgba(255, 255, 255, 0.4); border-radius: 1rem; font-weight: 500; transition: all 0.3s ease; margin-bottom: 0.5rem; text-decoration: none; font-size: 0.9rem; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 102, 0, 0.1); color: #ff6600; transform: translateX(5px); }
        
        /* Mobile Enhancements */
        .mobile-header { display: none; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; background: rgba(10, 10, 12, 0.8); backdrop-filter: blur(10px); position: sticky; top: 0; z-index: 90; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 95; backdrop-filter: blur(4px); }
        
        @media (max-width: 1024px) { 
            .sidebar { transform: translateX(-100%); } 
            .sidebar.active { transform: translateX(0); width: 280px; padding: 2rem 1.5rem; }
            .sidebar-overlay.active { display: block; }
            .main-content { margin-left: 0; padding: 1.5rem; } 
            .mobile-header { display: flex; }
        }

        /* 3D Card Effects */
        .card-perspective {
            perspective: 2000px;
            width: 100%;
            display: flex;
            justify-content: center;
            padding: 2rem;
        }
        .card-container {
            width: 400px;
            height: 250px;
            position: relative;
            transform-style: preserve-3d;
            transition: transform 0.6s cubic-bezier(0.23, 1, 0.32, 1);
            cursor: pointer;
        }
        .card-container.flipped {
            transform: rotateY(180deg);
        }
        .card-inner {
            width: 100%;
            height: 100%;
            position: relative;
            transform-style: preserve-3d;
        }
        .card-face {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0,0,0,0.5);
        }
        .card-back {
            transform: rotateY(180deg);
            background: linear-gradient(135deg, #1a1a1c 0%, #0a0a0c 100%);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .card-front {
            background: linear-gradient(135deg, #2a2a2e 0%, #121214 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            flex-direction: column;
            padding: 20px;
        }
        
        /* Holographic Overlay */
        .hologram {
            position: absolute;
            inset: 0;
            background: linear-gradient(
                125deg,
                rgba(255,255,255,0) 0%,
                rgba(255,255,255,0.05) 45%,
                rgba(255,255,255,0.1) 50%,
                rgba(255,255,255,0.05) 55%,
                rgba(255,255,255,0) 100%
            );
            background-size: 200% 200%;
            pointer-events: none;
            z-index: 5;
            transition: background-position 0.1s linear;
        }

        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; z-index: 10; position: relative; }
        .card-title { font-weight: 900; font-size: 14px; letter-spacing: 2px; color: #ff6600; text-shadow: 0 0 10px rgba(255,102,0,0.3); }
        .card-body-layout { display: flex; gap: 20px; flex: 1; position: relative; z-index: 10; }
        .photo-placeholder { width: 95px; height: 115px; background: #000; border: 2px solid rgba(255,102,0,0.3); border-radius: 12px; overflow: hidden; flex-shrink: 0; }
        .info-row { margin-bottom: 8px; }
        .info-label { font-[9px] font-black uppercase text-gray-500 tracking-wider; margin-bottom: 2px; }
        .info-value { font-[11px] font-bold text-white uppercase; }
        
        .shiny-overlay {
            position: absolute;
            top: -150%;
            left: -150%;
            width: 400%;
            height: 400%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
            pointer-events: none;
            z-index: 6;
        }

        .input-glass {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            color: #fff;
            padding: 0.8rem 1.25rem;
            outline: none;
            transition: all 0.3s ease;
            width: 100%;
        }
        .input-glass:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: #ff6600;
            box-shadow: 0 0 20px rgba(255, 102, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="bg-glow"></div>
    <div id="glow" class="glow-spot"></div>

    <div class="mobile-header">
        <div class="flex items-center gap-3">
            <img src="../../asset/trans.png" alt="Logo" class="h-8">
            <span class="font-bold tracking-tighter">WMA STAFF</span>
        </div>
        <button id="sidebarToggle" class="text-white text-2xl p-2"><i class="fas fa-bars"></i></button>
    </div>

    <div class="sidebar-overlay" id="overlay"></div>

    <aside class="sidebar" id="sidebar">
        <div class="flex items-center gap-4 mb-12 px-2">
            <img src="../../asset/trans.png" alt="Logo" class="h-10">
            <div>
                <h1 class="text-xl font-black bg-gradient-to-r from-orange-500 to-orange-300 bg-clip-text text-transparent tracking-tighter leading-tight">WMA HUB</h1>
                <p class="text-[8px] text-gray-500 font-bold uppercase tracking-[1px] -mt-1">We move, WMAFam</p>
            </div>
        </div>
        <nav class="flex-1">
            <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="missions.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'missions.php' ? 'active' : '' ?>"><i class="fas fa-tasks"></i> Mes Missions</a>
            <a href="project_files.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'project_files.php' ? 'active' : '' ?>"><i class="fas fa-folder-open"></i> Fichier Projet</a>
            <a href="service_card.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'service_card.php' ? 'active' : '' ?>"><i class="fas fa-id-card"></i> Carte de Service</a>
            <a href="notifications.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'notifications.php' ? 'active' : '' ?>"><i class="fas fa-bell"></i> Notifications</a>
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
                            <input type="text" name="full_name" value="<?= htmlspecialchars($card['full_name'] ?? $_SESSION['user_name']) ?>" class="input-glass" required>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-500 mb-2">Fonction / Poste</label>
                            <input type="text" name="role_title" value="<?= htmlspecialchars($card['role'] ?? 'Agent WMA') ?>" class="input-glass" required>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-500 mb-2">Matricule</label>
                            <div class="relative">
                                <input type="text" name="matricule" id="matriculeInput" value="<?= htmlspecialchars($card['matricule'] ?? '') ?>" placeholder="Ex: WMA-2024-001" class="input-glass pr-12">
                                <button type="button" onclick="copyToClipboard('matriculeInput')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-orange-500 transition-all p-2">
                                    <i class="fas fa-copy text-xs"></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-500 mb-2">Département</label>
                            <input type="text" name="department" value="<?= htmlspecialchars($card['department'] ?? 'Distribution') ?>" class="input-glass">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-500 mb-2">Groupe Sanguin</label>
                            <select name="blood_group" class="input-glass">
                                <option value="" <?= !($card['blood_group'] ?? '') ? 'selected' : '' ?>>Non spécifié</option>
                                <?php foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg): ?>
                                    <option value="<?= $bg ?>" <?= ($card['blood_group'] ?? '') === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-500 mb-2">Contact d'urgence</label>
                            <input type="text" name="emergency_contact" value="<?= htmlspecialchars($card['emergency_contact'] ?? '') ?>" placeholder="Nom & Téléphone" class="input-glass">
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

                <div class="card-perspective">
                    <div class="card-container" id="3dCard">
                        <div class="shiny-overlay" id="shinyOverlay"></div>
                        <div class="card-inner" id="serviceCard">
                            <!-- FRONT FACE -->
                            <div class="card-face card-front" id="cardFront">
                                <div class="hologram" id="hologramFront"></div>
                                <div class="card-header">
                                    <div class="card-title">WMA STAFF</div>
                                    <img src="../../asset/trans.png" class="h-8 opacity-90" alt="WMA">
                                </div>
                                <div class="card-body-layout">
                                    <div class="photo-placeholder">
                                        <img id="previewPhoto" src="<?= $card['photo_path'] ? '../../' . $card['photo_path'] : '../../asset/aspi.jpg' ?>" class="w-full h-full object-cover">
                                    </div>
                                    <div class="flex-1">
                                        <h4 id="previewName" class="text-xl font-black mb-1 uppercase tracking-tighter leading-tight"><?= htmlspecialchars($card['full_name'] ?? $_SESSION['user_name']) ?></h4>
                                        <p id="previewRole" class="text-orange-500 font-bold text-[10px] uppercase mb-6"><?= htmlspecialchars($card['role'] ?? 'Agent WMA') ?></p>
                                        
                                        <div class="info-row">
                                            <div class="info-label">Matricule</div>
                                            <div id="previewMatricule" class="info-value"><?= htmlspecialchars($card['matricule'] ?? 'N/A') ?></div>
                                        </div>
                                        <div class="info-row">
                                            <div class="info-label">Département</div>
                                            <div id="previewDept" class="info-value"><?= htmlspecialchars($card['department'] ?? 'Général') ?></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-auto pt-4 border-t border-white/5 flex justify-between items-center relative z-10">
                                    <span class="text-[7px] text-gray-500 font-black">ID OFFICIEL WMA HUB</span>
                                    <span class="text-[7px] text-orange-500 font-bold">GEN-2024</span>
                                </div>
                            </div>

                            <!-- BACK FACE -->
                            <div class="card-face card-back" id="cardBack">
                                <div class="hologram" id="hologramBack"></div>
                                <div class="p-6 h-full flex flex-col">
                                    <div class="flex justify-between items-center mb-8">
                                        <img src="../../asset/trans.png" class="h-5 opacity-40">
                                        <span class="text-[8px] font-bold text-orange-500/50">WMA-SECURE-ID</span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-6 flex-1">
                                        <div class="text-left">
                                            <div class="info-label">Groupe Sanguin</div>
                                            <div id="previewBG" class="font-bold text-xl text-red-500"><?= htmlspecialchars($card['blood_group'] ?? '??') ?></div>
                                        </div>
                                        <div class="text-left">
                                            <div class="info-label">Urgence</div>
                                            <div id="previewEmergency" class="text-[9px] font-semibold leading-tight"><?= htmlspecialchars($card['emergency_contact'] ?? 'Non fourni') ?></div>
                                        </div>
                                    </div>

                                    <div class="flex items-end justify-between mt-auto">
                                        <div class="qr-placeholder w-16 h-16 bg-white rounded-lg flex items-center justify-center">
                                            <i class="fas fa-qrcode text-3xl text-black"></i>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-[6px] text-gray-500 leading-tight">Cette carte est la propriété <br> de WMA HUB. En cas de perte <br> contactez +243 812 345 678</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex gap-4">
                    <?php if (($card['status'] ?? '') === 'approved'): ?>
                        <div class="flex gap-2">
                            <button onclick="downloadCard('jpg')" class="bg-green-500 hover:bg-green-600 text-white px-4 py-3 rounded-xl font-bold transition-all flex items-center gap-2 text-sm">
                                <i class="fas fa-file-image"></i> JPG
                            </button>
                            <button onclick="downloadCard('pdf')" class="bg-red-500 hover:bg-red-600 text-white px-4 py-3 rounded-xl font-bold transition-all flex items-center gap-2 text-sm">
                                <i class="fas fa-file-pdf"></i> PDF
                            </button>
                        </div>
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
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const toggle = document.getElementById('sidebarToggle');

        function toggleSidebar() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        if (toggle) toggle.addEventListener('click', toggleSidebar);
        if (overlay) overlay.addEventListener('click', toggleSidebar);

        function flipCard() {
            document.querySelector('.card-container').classList.toggle('flipped');
        }

        const card3d = document.getElementById('3dCard');
        const shiny = document.getElementById('shinyOverlay');
        const hologramFront = document.getElementById('hologramFront');
        const hologramBack = document.getElementById('hologramBack');

        document.addEventListener('mousemove', (e) => {
            // Glow effect
            if (glow) {
                glow.style.left = (e.clientX - 200) + 'px';
                glow.style.top = (e.clientY - 200) + 'px';
            }

            // 3D Card Tilt
            if (card3d) {
                const rect = card3d.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const rotateX = (centerY - y) / 10;
                const rotateY = (x - centerX) / 10;
                
                if (!card3d.classList.contains('flipped')) {
                    card3d.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
                } else {
                    card3d.style.transform = `rotateY(180deg) rotateX(${rotateX}deg) rotateY(${-rotateY}deg)`;
                }

                // Shiny overlay effect
                if (shiny) {
                    shiny.style.left = `${(x / rect.width) * 100 - 150}%`;
                    shiny.style.top = `${(y / rect.height) * 100 - 150}%`;
                }

                // Hologram effect
                const holoX = (x / rect.width) * 100;
                const holoY = (y / rect.height) * 100;
                if (hologramFront) hologramFront.style.backgroundPosition = `${holoX}% ${holoY}%`;
                if (hologramBack) hologramBack.style.backgroundPosition = `${holoX}% ${holoY}%`;
            }
        });

        // Reset tilt on mouse leave
        if (card3d) {
            card3d.addEventListener('mouseleave', () => {
                card3d.style.transform = card3d.classList.contains('flipped') ? 'rotateY(180deg)' : 'rotateZ(0deg)';
            });
            
            card3d.addEventListener('click', () => {
                card3d.classList.toggle('flipped');
            });
        }

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

        // Copy to clipboard
        function copyToClipboard(inputId) {
            const input = document.getElementById(inputId);
            input.select();
            document.execCommand('copy');
            
            // Visual feedback
            const btn = input.nextElementSibling;
            const icon = btn.querySelector('i');
            icon.classList.remove('fa-copy');
            icon.classList.add('fa-check', 'text-green-500');
            
            setTimeout(() => {
                icon.classList.remove('fa-check', 'text-green-500');
                icon.classList.add('fa-copy');
            }, 2000);
        }

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
            const { jsPDF } = window.jspdf;
            const isFlipped = document.getElementById('3dCard').classList.contains('flipped');
            
            // Reset rotation for capture
            document.getElementById('3dCard').style.transform = 'rotateY(0deg)';
            if (isFlipped) document.getElementById('3dCard').classList.remove('flipped');
            await new Promise(r => setTimeout(r, 100));

            // Front Capture
            const canvasFront = await html2canvas(document.getElementById('cardFront'), { scale: 2, useCORS: true });
            
            // Flip for back capture
            document.getElementById('3dCard').classList.add('flipped');
            await new Promise(r => setTimeout(r, 600));
            document.getElementById('3dCard').style.transform = 'rotateY(180deg)';
            await new Promise(r => setTimeout(r, 100));

            const canvasBack = await html2canvas(document.getElementById('cardBack'), { scale: 2, useCORS: true });
            
            if (format === 'jpg') {
                const link = document.createElement('a');
                link.download = `wma-card-front.jpg`;
                link.href = canvasFront.toDataURL("image/jpeg", 0.9);
                link.click();
                link.download = `wma-card-back.jpg`;
                link.href = canvasBack.toDataURL("image/jpeg", 0.9);
                link.click();
            } else if (format === 'pdf') {
                const pdf = new jsPDF({
                    orientation: 'landscape',
                    unit: 'mm',
                    format: [210, 297] // A4
                });

                const imgPropsFront = pdf.getImageProperties(canvasFront.toDataURL("image/jpeg", 1.0));
                const imgPropsBack = pdf.getImageProperties(canvasBack.toDataURL("image/jpeg", 1.0));
                
                const pdfWidth = pdf.internal.pageSize.getWidth();
                const pdfHeight = pdf.internal.pageSize.getHeight();
                
                // Card dimensions on PDF (approx CR80 size)
                const cardWidth = 85.6; 
                const cardHeight = 54;
                
                // Add Front
                pdf.addImage(canvasFront.toDataURL("image/jpeg", 1.0), 'JPEG', 10, 10, cardWidth, cardHeight);
                // Add Back
                pdf.addImage(canvasBack.toDataURL("image/jpeg", 1.0), 'JPEG', 10, 70, cardWidth, cardHeight);
                
                pdf.save(`wma-card-${Date.now()}.pdf`);
            }
            
            // Restore rotation state
            if (!isFlipped) {
                document.getElementById('3dCard').classList.remove('flipped');
                document.getElementById('3dCard').style.transform = 'rotateY(0deg)';
            }
        }
    </script>
</body>
</html>
