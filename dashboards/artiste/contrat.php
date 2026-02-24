<?php
require_once __DIR__ . '/auth_artist.php';
require_once __DIR__ . '/../../includes/mailer.php';

$pageTitle = 'Contrat de Distribution - WMA Hub';
$db = getDBConnection();

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_contract'])) {
    $signature_data = $_POST['signature_data'] ?? '';
    
    if (empty($signature_data)) {
        $error_message = "Veuillez signer le contrat avant de l'envoyer.";
    } else {
        $artist_name = $_SESSION['user_name'];
        $artist_email = $_SESSION['user_email'] ?? 'Non spécifié';
        $date = date('d/m/Y H:i:s');
        
        $subject = "Contrat de Distribution Signé - " . $artist_name;
        
        $htmlBody = "
        <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee;'>
            <h2 style='color: #ff6600; text-align: center;'>CONTRAT DE DISTRIBUTION MUSICALE</h2>
            <p><strong>Artiste :</strong> $artist_name</p>
            <p><strong>Email :</strong> $artist_email</p>
            <p><strong>Date de signature :</strong> $date</p>
            <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
            <p>L'artiste mentionné ci-dessus a lu et accepté les termes du contrat de distribution de WMA HUB.</p>
            <p>Ci-dessous sa signature numérique :</p>
            <div style='text-align: center; margin-top: 20px; border: 1px solid #ccc; padding: 10px; display: inline-block;'>
                <img src='$signature_data' alt='Signature de l\'artiste' style='max-width: 100%; height: auto;'>
            </div>
            <p style='font-size: 12px; color: #777; margin-top: 30px;'>Ceci est une signature électronique valide enregistrée sur wmahub.com.</p>
        </div>
        ";
        
        $sent = sendEmail('landryxbb0@gmail.com', $subject, $htmlBody);
        
        if ($sent) {
            $success_message = "Votre contrat a été signé et envoyé avec succès à l'administration.";
            
            // Create a notification for the artist
            createNotification($_SESSION['user_id'], 'project_update', "Votre contrat de distribution a été envoyé avec succès.", null);
        } else {
            $error_message = "Une erreur est survenue lors de l'envoi de l'email. Veuillez réessayer.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="icon" type="image/jpeg" href="../../asset/placeholder.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0a0a0c; color: #fff; margin: 0; min-height: 100vh; overflow-x: hidden; }
        .bg-glow { position: fixed; inset: 0; background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a0c 100%); z-index: -1; }
        .glow-spot { position: fixed; width: 600px; height: 600px; background: radial-gradient(circle, rgba(255,102,0,0.05) 0%, transparent 70%); border-radius: 50%; pointer-events: none; z-index: -1; transition: all 0.3s ease; }
        .sidebar { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .sidebar.active { transform: translateX(0); }
        @media (max-width: 1024px) { .sidebar { transform: translateX(-100%); position: fixed; z-index: 50; height: 100vh; } }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px); border: 1px border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 20px; transition: all 0.3s ease; }
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: #94a3b8; transition: all 0.3s ease; margin-bottom: 4px; }
        .nav-link:hover:not(.active) { background: rgba(255, 255, 255, 0.03); color: #fff; }
        .nav-link.active { background: linear-gradient(135deg, #ff6600 0%, #ff8c00 100%); color: #fff; box-shadow: 0 4px 15px rgba(255, 102, 0, 0.3); }
        
        .contract-box { max-height: 400px; overflow-y: auto; background: rgba(0,0,0,0.2); border-radius: 15px; padding: 25px; border: 1px solid rgba(255,255,255,0.03); scrollbar-width: thin; scrollbar-color: #ff6600 rgba(255,255,255,0.05); }
        .contract-box::-webkit-scrollbar { width: 6px; }
        .contract-box::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); border-radius: 3px; }
        .contract-box::-webkit-scrollbar-thumb { background: #ff6600; border-radius: 3px; }
        
        #signature-pad { background: #fff; border: 2px dashed #ff6600; border-radius: 15px; cursor: crosshair; touch-action: none; }
    </style>
</head>
<body>
    <div class="bg-glow"></div>
    <div class="glow-spot" id="glow"></div>

    <!-- Mobile Header -->
    <div class="lg:hidden flex items-center justify-between p-4 bg-[#0a0a0c] border-b border-white/5 sticky top-0 z-40">
        <div class="flex items-center gap-3">
            <img src="../../asset/trans.png" alt="Logo" class="w-8 h-8 object-contain">
            <span class="text-lg font-bold tracking-tighter">WMA ARTISTE</span>
        </div>
        <button id="sidebarToggle" class="text-white text-2xl p-2"><i class="fas fa-bars"></i></button>
    </div>

    <!-- Sidebar Overlay -->
    <div id="overlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 hidden lg:hidden"></div>

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar w-72 bg-[#0d0d0f] border-r border-white/5 flex flex-col p-6 overflow-y-auto">
            <div class="flex items-center gap-4 mb-10 px-2">
                <img src="../../asset/trans.png" alt="Logo" class="w-10 h-10 object-contain">
                <div>
                    <h1 class="text-xl font-bold tracking-tighter bg-gradient-to-r from-white to-white/60 bg-clip-text text-transparent">WMA HUB</h1>
                    <p class="text-[10px] text-orange-500 font-bold uppercase tracking-widest">We move, WMAFam</p>
                </div>
            </div>

            <nav class="flex-1">
                <a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
                    <i class="fas fa-th-large"></i>
                    <span class="font-medium">Tableau de bord</span>
                </a>
                <a href="submit.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'submit.php' ? 'active' : '' ?>">
                    <i class="fas fa-plus-circle"></i>
                    <span class="font-medium">Soumettre</span>
                </a>
                <a href="services.php" class="nav-link <?= strpos(basename($_SERVER['PHP_SELF']), 'services') !== false ? 'active' : '' ?>">
                    <i class="fas fa-magic"></i>
                    <span class="font-medium">Services</span>
                </a>
                <a href="notifications.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'notifications.php' ? 'active' : '' ?>">
                    <i class="fas fa-bell"></i>
                    <span class="font-medium">Notifications</span>
                </a>
                <a href="catalogue.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'catalogue.php' ? 'active' : '' ?>">
                    <i class="fas fa-music"></i>
                    <span class="font-medium">Catalogue</span>
                </a>
                <a href="stats.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'stats.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-line"></i>
                    <span class="font-medium">Stats</span>
                </a>
                <a href="reviews.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'reviews.php' ? 'active' : '' ?>">
                    <i class="fas fa-star"></i>
                    <span class="font-medium">Laisser un avis</span>
                </a>
                <a href="revenues.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'revenues.php' ? 'active' : '' ?>">
                    <i class="fas fa-wallet"></i>
                    <span class="font-medium">Revenus</span>
                </a>
                <a href="contrat.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'contrat.php' ? 'active' : '' ?>">
                    <i class="fas fa-file-contract"></i>
                    <span class="font-medium">Contrat</span>
                </a>
            </nav>

            <div class="mt-auto pt-6 border-t border-white/5">
                <div class="flex items-center gap-3 p-3 rounded-2xl bg-white/[0.03] border border-white/5 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-orange-500 flex items-center justify-center text-white shadow-lg shadow-orange-500/20">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="flex-1 overflow-hidden">
                        <p class="text-sm font-bold truncate"><?= htmlspecialchars($_SESSION['user_name']) ?></p>
                        <p class="text-[10px] text-gray-500 uppercase font-black">Artiste</p>
                    </div>
                </div>
                <a href="../../auth/logout.php" class="nav-link text-red-500 hover:bg-red-500/10 mb-0">
                    <i class="fas fa-power-off"></i>
                    <span class="font-medium">Déconnexion</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-6 lg:p-12">
            <header class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12">
                <div>
                    <h2 class="text-3xl lg:text-4xl font-black tracking-tighter mb-2">Contrat de <span class="text-orange-500">Distribution</span></h2>
                    <p class="text-gray-500 font-medium">Lisez et signez votre contrat pour activer vos services.</p>
                </div>
                <div class="flex items-center gap-4 mt-4 md:mt-0">
                    <?php include '../../includes/header_notifications.php'; ?>
                </div>
            </header>

            <?php if ($success_message): ?>
                <div class="mb-8 p-6 rounded-2xl bg-green-500/10 border border-green-500/20 text-green-500 flex items-center gap-4">
                    <i class="fas fa-check-circle text-2xl"></i>
                    <p class="font-bold"><?= htmlspecialchars($success_message) ?></p>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="mb-8 p-6 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-500 flex items-center gap-4">
                    <i class="fas fa-exclamation-circle text-2xl"></i>
                    <p class="font-bold"><?= htmlspecialchars($error_message) ?></p>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                <!-- Contract Text -->
                <div class="glass-card p-8">
                    <h3 class="text-xl font-bold mb-6 flex items-center gap-3">
                        <i class="fas fa-file-alt text-orange-500"></i>
                        Termes et Conditions
                    </h3>
                    <div class="contract-box text-sm text-gray-400 leading-relaxed font-light">
                        <h4 class="text-white font-black mb-6 uppercase tracking-wider text-sm border-b border-orange-500/30 pb-2">CONDITIONS GÉNÉRALES DE DISTRIBUTION – WMA HUB</h4>
                        
                        <p class="mb-8 p-4 bg-orange-500/5 border-l-4 border-orange-500 italic text-white/80">
                            En créant un compte de distribution sur la plateforme WMA HUB, l’Artiste accepte sans réserve les présentes conditions.
                        </p>

                        <h5 class="text-orange-500 font-bold mb-3 uppercase tracking-widest text-[10px]">1. Objet</h5>
                        <p class="mb-6">WMA HUB fournit à l’Artiste un service de distribution musicale sur les plateformes de streaming et de téléchargement partenaires, ainsi que des outils de suivi et de gestion des revenus.</p>

                        <h5 class="text-orange-500 font-bold mb-3 uppercase tracking-widest text-[10px]">2. Droits et responsabilités</h5>
                        <p class="mb-3">L’Artiste déclare être le propriétaire légal des œuvres distribuées ou disposer de tous les droits nécessaires (droits d’auteur, droits voisins, autorisations).</p>
                        <p class="mb-6 italic">WMA HUB n’est pas responsable des litiges liés à la propriété des œuvres.</p>

                        <h5 class="text-orange-500 font-bold mb-3 uppercase tracking-widest text-[10px]">3. Partage des revenus</h5>
                        <ul class="space-y-2 mb-6 ml-4 list-disc marker:text-orange-500">
                            <li>WMA HUB conserve <strong>15 %</strong> des revenus nets générés par les œuvres distribuées.</li>
                            <li>L’Artiste reçoit <strong>85 %</strong> des revenus nets.</li>
                            <li>Ce pourcentage est fixe et s’applique à l’ensemble des revenus générés via WMA HUB.</li>
                        </ul>

                        <h5 class="text-orange-500 font-bold mb-3 uppercase tracking-widest text-[10px]">4. Paiement et retrait des revenus</h5>
                        <ul class="space-y-2 mb-6 ml-4 list-disc marker:text-orange-500">
                            <li>Les revenus sont retirables tous les <strong>deux (2) mois</strong>.</li>
                            <li>Les demandes de retrait se font exclusivement via le compte WMA HUB de l’Artiste.</li>
                            <li>Les paiements sont soumis aux délais techniques des plateformes partenaires.</li>
                        </ul>

                        <h5 class="text-orange-500 font-bold mb-3 uppercase tracking-widest text-[10px]">5. Rapports et statistiques</h5>
                        <ul class="space-y-2 mb-6 ml-4 list-disc marker:text-orange-500">
                            <li>Les rapports financiers et statistiques sont mis à disposition tous les <strong>trois (3) mois</strong>.</li>
                            <li>Les données incluent notamment : streams, téléchargements et revenus estimés.</li>
                        </ul>

                        <h5 class="text-orange-500 font-bold mb-3 uppercase tracking-widest text-[10px]">6. Durée</h5>
                        <p class="mb-6">Le présent accord entre en vigueur dès la création du compte de distribution et reste valable tant que le compte est actif sur WMA HUB.</p>

                        <h5 class="text-orange-500 font-bold mb-3 uppercase tracking-widest text-[10px]">7. Résiliation</h5>
                        <p class="mb-3 text-white/70">WMA HUB se réserve le droit de suspendre ou résilier un compte en cas de :</p>
                        <ul class="space-y-2 mb-4 ml-4 list-disc marker:text-orange-500">
                            <li>Violation des présentes conditions,</li>
                            <li>Fraude,</li>
                            <li>Diffusion de contenu illégal ou non autorisé.</li>
                        </ul>
                        <p class="mb-6">L’Artiste peut demander la fermeture de son compte conformément aux procédures internes de WMA HUB.</p>

                        <h5 class="text-orange-500 font-bold mb-3 uppercase tracking-widest text-[10px]">8. Acceptation</h5>
                        <p class="mb-8">La création du compte et l’utilisation des services de WMA HUB valent acceptation totale et définitive des présentes conditions générales.</p>

                        <div class="mt-8 pt-8 border-t border-white/5 flex flex-col items-center text-center">
                            <p class="font-bold text-white mb-1">WMA HUB</p>
                            <p class="text-[9px] uppercase tracking-[3px] text-orange-500">Plateforme de distribution et de management musical</p>
                        </div>
                    </div>
                </div>

                <!-- Signature Section -->
                <div class="glass-card p-8 flex flex-col items-center">
                    <h3 class="text-xl font-bold mb-6 self-start flex items-center gap-3">
                        <i class="fas fa-pen-nib text-orange-500"></i>
                        Signature Numérique
                    </h3>
                    
                    <div class="w-full max-w-[500px]">
                        <canvas id="signature-pad" width="500" height="250" class="w-full shadow-2xl shadow-orange-500/10 mb-6"></canvas>
                        
                        <div class="flex gap-4 mb-8">
                            <button id="clear" class="flex-1 py-3 px-6 bg-white/5 hover:bg-white/10 text-white rounded-xl font-bold transition-all border border-white/5 flex items-center justify-center gap-2">
                                <i class="fas fa-eraser"></i> Effacer
                            </button>
                            <button id="validate" class="flex-1 py-3 px-6 bg-orange-500/10 hover:bg-orange-500/20 text-orange-500 rounded-xl font-bold transition-all border border-orange-500/20 flex items-center justify-center gap-2">
                                <i class="fas fa-check"></i> Valider
                            </button>
                        </div>

                        <form method="POST" id="contractForm" class="hidden">
                            <input type="hidden" name="signature_data" id="signature_data">
                            <button type="submit" name="send_contract" class="w-full py-4 px-6 bg-gradient-to-r from-orange-500 to-orange-300 text-black font-black uppercase tracking-widest rounded-2xl transition-all shadow-xl shadow-orange-500/20 hover:scale-[1.02] flex items-center justify-center gap-3">
                                <i class="fas fa-paper-plane"></i> Envoyer le Contrat Signé
                            </button>
                        </form>

                        <p id="instruction" class="text-center text-gray-500 text-[10px] font-bold uppercase tracking-widest mt-4">
                            Veuillez signer ci-dessus puis cliquer sur "Valider"
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const canvas = document.getElementById('signature-pad');
        const ctx = canvas.getContext('2d');
        const clearBtn = document.getElementById('clear');
        const validateBtn = document.getElementById('validate');
        const contractForm = document.getElementById('contractForm');
        const signatureDataInput = document.getElementById('signature_data');
        const instruction = document.getElementById('instruction');

        let drawing = false;
        let points = 0;

        function getPos(e) {
            const rect = canvas.getBoundingClientRect();
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;
            return {
                x: (e.clientX || (e.touches ? e.touches[0].clientX : 0)) - rect.left,
                y: (e.clientY || (e.touches ? e.touches[0].clientY : 0)) - rect.top
            };
        }

        function startDrawing(e) {
            drawing = true;
            ctx.beginPath();
            const pos = getPos(e);
            ctx.moveTo(pos.x, pos.y);
            e.preventDefault();
        }

        function draw(e) {
            if (!drawing) return;
            const pos = getPos(e);
            ctx.lineTo(pos.x, pos.y);
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.strokeStyle = '#000';
            ctx.stroke();
            points++;
            e.preventDefault();
        }

        function stopDrawing() {
            drawing = false;
        }

        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        window.addEventListener('mouseup', stopDrawing);

        canvas.addEventListener('touchstart', startDrawing);
        canvas.addEventListener('touchmove', draw);
        canvas.addEventListener('touchend', stopDrawing);

        clearBtn.addEventListener('click', () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            contractForm.classList.add('hidden');
            instruction.classList.remove('hidden');
            points = 0;
        });

        validateBtn.addEventListener('click', () => {
            if (points < 20) {
                alert("Veuillez fournir une signature plus complète.");
                return;
            }
            const dataURL = canvas.toDataURL();
            signatureDataInput.value = dataURL;
            contractForm.classList.remove('hidden');
            instruction.classList.add('hidden');
            
            // Scroll to the button
            contractForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });

        // Sidebar logic
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const toggle = document.getElementById('sidebarToggle');

        function toggleSidebar() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('hidden');
        }

        if (toggle) toggle.addEventListener('click', toggleSidebar);
        if (overlay) overlay.addEventListener('click', toggleSidebar);

        // Glow mouse move
        const glow = document.getElementById('glow');
        document.onmousemove = (e) => {
            if (glow) {
                glow.style.left = (e.clientX - (glow.offsetWidth || 0) / 2) + 'px';
                glow.style.top = (e.clientY - (glow.offsetHeight || 0) / 2) + 'px';
            }
        };
    </script>
</body>
</html>
