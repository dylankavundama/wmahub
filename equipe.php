<?php
require_once __DIR__ . '/includes/config.php';

$db = getDBConnection();

// Récupérer les employés dont la carte de service est approuvée
try {
    $stmt = $db->query("SELECT * FROM service_cards WHERE status = 'approved' ORDER BY full_name ASC");
    $employees = $stmt->fetchAll();
} catch (Exception $e) {
    $employees = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notre Équipe - WMA Hub</title>
    <link rel="icon" type="image/jpeg" href="asset/placeholder.jpg">
    <title>Notre Équipe - WMA Hub</title>
    <link rel="icon" type="image/jpeg" href="asset/placeholder.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/equipe.css">
</head>
<body>
    <div id="wma-global-loader">
        <div class="loader-spin"></div>
    </div>
    <div class="bg-glow"></div>
    
    <div class="container">
        <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Retour à l'accueil</a>
        
        <header class="mb-16">
            <h1 class="page-title">Notre <span class="text-orange-500">Équipe</span></h1>
            <p class="page-subtitle">Découvrez les talents derrière WMA Hub qui travaillent sans relâche pour propulser votre carrière musicale.</p>
        </header>

        <?php if (empty($employees)): ?>
            <div class="glass-card empty-state">
                <i class="fas fa-users"></i>
                <p>Notre équipe est en cours de déploiement.</p>
            </div>
        <?php else: ?>
            <div class="employees-grid">
                <?php foreach ($employees as $emp): ?>
                    <div class="glass-card group agent-card" 
                         data-name="<?= htmlspecialchars($emp['full_name']) ?>"
                         data-role="<?= htmlspecialchars($emp['role'] ?: 'Membre Staff') ?>"
                         data-dept="<?= htmlspecialchars($emp['department'] ?: 'Général') ?>"
                         data-photo="<?= htmlspecialchars($emp['photo_path'] ?: 'asset/aspi.jpg') ?>"
                         data-matricule="<?= htmlspecialchars($emp['matricule'] ?: 'WMA-' . str_pad($emp['id'], 4, '0', STR_PAD_LEFT)) ?>">
                        <div class="card-image-wrap">
                            <img src="<?= htmlspecialchars($emp['photo_path'] ?: 'asset/aspi.jpg') ?>" 
                                 alt="<?= htmlspecialchars($emp['full_name']) ?>">
                            <div class="image-overlay"></div>
                            <div class="matricule-wrap">
                                <span class="matricule-badge">#<?= htmlspecialchars($emp['matricule'] ?: 'WMA-' . str_pad($emp['id'], 4, '0', STR_PAD_LEFT)) ?></span>
                            </div>
                        </div>
                        <div class="card-info">
                            <h3><?= htmlspecialchars($emp['full_name']) ?></h3>
                            <p class="dept text-orange-500"><?= htmlspecialchars($emp['department'] ?: 'Général') ?></p>
                            <div class="card-role-meta">
                                <i class="fas fa-briefcase"></i>
                                <span><?= htmlspecialchars($emp['role'] ?: 'Membre Staff') ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <footer class="page-footer">
            <p class="footer-quote text-orange-500">We move, WMAFam</p>
            <p class="footer-copy">© 2026 WMA Hub. Propulsé par l'innovation.</p>
        </footer>
    </div>

    <!-- Modal 3D Card -->
    <div class="modal-overlay" id="modalOverlay">
        <i class="fas fa-times close-modal" id="closeModal"></i>
        <div class="card-3d-wrap" id="cardWrap">
            <div class="card-3d-inner" id="cardInner">
                <div class="modal-header">
                    <img src="asset/trans.png" alt="WMA Hub">
                    <div class="modal-header-text">
                        <p class="type text-orange-500">Service Card</p>
                        <p class="label">Official Staff</p>
                    </div>
                </div>
                
                <img src="" id="modalPhoto" class="card-photo" alt="Photo">
                
                <div class="modal-info">
                    <p id="modalName"></p>
                    <p class="role" id="modalRole"></p>
                    <p class="dept text-orange-500" id="modalDept"></p>
                </div>

                <div class="modal-footer">
                    <div class="modal-footer-meta">
                        <p class="matricule" id="modalMatricule"></p>
                        <p class="validity">VALIDE : 2026</p>
                    </div>
                    <div class="card-qr">
                        <i class="fas fa-qrcode"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('load', () => {
            const loader = document.getElementById('wma-global-loader');
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(() => loader.style.display = 'none', 500);
            }
        });

        const modal = document.getElementById('modalOverlay');
        const cards = document.querySelectorAll('.agent-card');
        const closeBtn = document.getElementById('closeModal');
        const cardInner = document.getElementById('cardInner');
        const cardWrap = document.getElementById('cardWrap');

        cards.forEach(card => {
            card.addEventListener('click', () => {
                document.getElementById('modalName').innerText = card.dataset.name;
                document.getElementById('modalRole').innerText = card.dataset.role;
                document.getElementById('modalDept').innerText = card.dataset.dept;
                document.getElementById('modalMatricule').innerText = card.dataset.matricule;
                document.getElementById('modalPhoto').src = card.dataset.photo;
                
                modal.classList.add('active');
            });
        });

        const closeModal = () => modal.classList.remove('active');
        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => { if(e.target === modal) closeModal(); });

        // 3D Tilt Effect
        modal.addEventListener('mousemove', (e) => {
            if(!modal.classList.contains('active')) return;

            const rect = cardWrap.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            const centerX = rect.width / 2;
            const centerY = rect.height / 2;

            const rotateX = (centerY - y) / 10;
            const rotateY = (x - centerX) / 10;

            cardInner.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
        });

        modal.addEventListener('mouseleave', () => {
            cardInner.style.transform = 'rotateX(0deg) rotateY(0deg)';
        });
    </script>
</body>
</html>
