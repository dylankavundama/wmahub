//==================================================
// GESTION DES TAILLES D'ÉCRAN ET DES ANIMATIONS
//==================================================

/**
 * Corrige la hauteur vh sur les appareils mobiles.
 * Définit la variable CSS --vh pour une hauteur de vue précise.
 */
function fixVH() {
    let vh = window.innerHeight * 0.01;
    document.documentElement.style.setProperty('--vh', `${vh}px`);
}

/**
 * Gère l'animation de l'élément "hero" au chargement de la page.
 */
function heroAnimation() {
    const heroContent = document.querySelector('.hero-content');
    if (heroContent) {
        heroContent.style.opacity = '0';
        setTimeout(() => {
            heroContent.style.opacity = '1';
        }, 500);
    }
}

/**
 * Révèle les éléments avec la classe .reveal lorsqu'ils deviennent visibles à l'écran.
 */
function revealOnScroll() {
    const reveals = document.querySelectorAll('.reveal');
    for (let i = 0; i < reveals.length; i++) {
        const windowHeight = window.innerHeight;
        const elementTop = reveals[i].getBoundingClientRect().top;
        const elementVisible = 150;
        
        if (elementTop < windowHeight - elementVisible) {
            reveals[i].classList.add('active');
        }
    }
}

// Initialisation des animations et de la hauteur vh
window.addEventListener('DOMContentLoaded', () => {
    fixVH();
    window.addEventListener('resize', fixVH);

    // Ajoutez la classe 'reveal' aux sections pour l'animation au scroll
    const sections = document.querySelectorAll('section');
    sections.forEach((section, index) => {
        section.classList.add('reveal');
        // Délai pour un effet en cascade
        section.style.transitionDelay = `${index * 0.1}s`;
    });

    // Écouteur pour le scroll
    window.addEventListener('scroll', revealOnScroll);
    revealOnScroll(); // Déclenche l'animation au chargement
});

window.addEventListener('load', heroAnimation);


//==================================================
// GESTION DU FORMULAIRE ET WHATSAPP
//==================================================

/**
 * Ouvre une nouvelle page pour le formulaire de soumission de projet.
 */
function openDialog() {
    const nouvellePageURL = 'projet.php'; 
    window.location.href = nouvellePageURL;
}

/**
 * Ferme la boîte de dialogue (fonction non utilisée dans le code fourni, mais conservée).
 */
function closeDialog() {
    const dialog = document.getElementById("whatsappDialog");
    if (dialog) {
        dialog.style.display = "none";
        document.body.style.overflow = "auto";
    }
}

/**
 * Envoie un message WhatsApp avec le nom et l'adresse de l'utilisateur.
 * @deprecated Remplacé par le formulaire plus complet.
 */
function sendToWhatsApp() {
    const name = document.getElementById("userName").value.trim();
    const address = document.getElementById("userAddress").value.trim();

    if (!name || !address) {
        alert("Veuillez remplir votre nom et votre adresse pour continuer.");
        return;
    }

    const message = `Bonjour WMAHUB, je souhaite distribuer ma musique.\nNom: ${name}\nAdresse: ${address}`;
    const phoneNumber = "243975203080";

    const url = `https://wa.me/${phoneNumber}?text=${encodeURIComponent(message)}`;
    window.open(url, '_blank');
    
    closeDialog();
}


//==================================================
// RÉCUPÉRATION ET AFFICHAGE DES ACTUALITÉS
//==================================================

/**
 * Récupère les actualités depuis une API et les affiche sur la page.
 */


// Appeler la fonction au chargement de la page
// window.addEventListener('load', fetchNews);

//==================================================
// ANIMATION DU GRAPHIQUE DONUT CHART
//==================================================

/**
 * Anime les segments du graphique donut chart
 */
function animateDonutChart() {
    const segments = document.querySelectorAll('.donut-segment');
    const circumference = 2 * Math.PI * 80; // r = 80
    let currentOffset = 0;
    
    // Trier les segments par ordre d'apparition (dans l'ordre du DOM)
    segments.forEach((segment, index) => {
        const percent = parseFloat(segment.getAttribute('data-percent'));
        const length = (percent / 100) * circumference;
        
        // Initialiser à 0 pour l'animation
        segment.style.strokeDasharray = `0 ${circumference}`;
        segment.style.strokeDashoffset = -currentOffset;
        
        // Animer vers la valeur finale
        setTimeout(() => {
            segment.style.transition = 'stroke-dasharray 1.5s ease-out';
            segment.style.strokeDasharray = `${length} ${circumference}`;
        }, index * 150);
        
        currentOffset += length;
    });
}

/**
 * Anime les nombres avec un effet de compteur
 */
function animateNumbers() {
    const numberElements = document.querySelectorAll('[data-target]');
    
    numberElements.forEach(element => {
        const target = parseFloat(element.getAttribute('data-target'));
        const duration = 2000; // 2 secondes
        const increment = target / (duration / 16); // 60 FPS
        let current = 0;
        
        const updateNumber = () => {
            current += increment;
            if (current < target) {
                // Formater le nombre (pas de décimales pour les pourcentages)
                if (element.classList.contains('legend-value') || element.classList.contains('total-number')) {
                    element.textContent = Math.floor(current);
                } else {
                    element.textContent = Math.floor(current);
                }
                requestAnimationFrame(updateNumber);
            } else {
                element.textContent = Math.floor(target);
            }
        };
        
        // Démarrer l'animation quand l'élément est visible
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    updateNumber();
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        
        observer.observe(element);
    });
}

/**
 * Initialise toutes les animations du graphique
 */
function initChartAnimations() {
    const chartSection = document.querySelector('.analytics-section');
    if (!chartSection) return;
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Animer le graphique donut
                setTimeout(() => {
                    animateDonutChart();
                }, 300);
                
                // Animer les nombres
                setTimeout(() => {
                    animateNumbers();
                }, 500);
                
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.3 });
    
    observer.observe(chartSection);
}

// Initialiser les animations au chargement
document.addEventListener('DOMContentLoaded', () => {
    initChartAnimations();
    initGlobe3D();
});

//==================================================
// GLOBE 3D INTERACTIF
//==================================================

/**
 * Calcule la position 3D d'un point sur le globe basé sur latitude/longitude
 */
function calculate3DPosition(lat, lng, radius) {
    const latRad = (lat * Math.PI) / 180;
    const lngRad = (lng * Math.PI) / 180;
    
    const x = Math.cos(latRad) * Math.cos(lngRad) * radius;
    const y = Math.sin(latRad) * radius;
    const z = Math.cos(latRad) * Math.sin(lngRad) * radius;
    
    return { x, y, z };
}

/**
 * Positionne les points sur le globe 3D
 */
function positionGlobePoints() {
    const globe = document.getElementById('globe3d');
    if (!globe) return;
    
    const points = globe.querySelectorAll('.globe-point');
    const globeSize = 400; // Taille du globe
    const radius = globeSize / 2; // Rayon du globe
    
    points.forEach(point => {
        const lat = parseFloat(point.style.getPropertyValue('--lat'));
        const lng = parseFloat(point.style.getPropertyValue('--lng'));
        
        const { x, y, z } = calculate3DPosition(lat, lng, radius);
        
        // Positionner le point sur la surface du globe
        point.style.left = `calc(50% + ${x}px - 10px)`;
        point.style.top = `calc(50% + ${y}px - 10px)`;
        point.style.transform = `translateZ(${z}px)`;
        
        // Stocker les coordonnées pour la rotation
        point.dataset.x = x;
        point.dataset.y = y;
        point.dataset.z = z;
    });
}

/**
 * Anime les statistiques de présence
 */
function animatePresenceStats() {
    const statNumbers = document.querySelectorAll('.stat-number-3d[data-target]');
    
    statNumbers.forEach(element => {
        const target = parseFloat(element.getAttribute('data-target'));
        const duration = 2000;
        const increment = target / (duration / 16);
        let current = 0;
        
        const updateNumber = () => {
            current += increment;
            if (current < target) {
                element.textContent = Math.floor(current);
                requestAnimationFrame(updateNumber);
            } else {
                element.textContent = Math.floor(target);
            }
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    updateNumber();
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        
        observer.observe(element);
    });
}

/**
 * Ajoute l'interactivité au globe (manipulable avec la souris)
 */
function addGlobeInteractivity() {
    const globe = document.getElementById('globe3d');
    if (!globe) return;
    
    let rotationY = 0;
    let rotationX = -20;
    let isDragging = false;
    let startX = 0;
    let startY = 0;
    let startRotationY = 0;
    let startRotationX = 0;
    let velocityY = 0;
    let velocityX = 0;
    let lastTime = 0;
    let lastX = 0;
    let lastY = 0;
    let animationFrameId = null;
    let autoRotate = true;
    let autoRotateSpeed = 0.6; // degrés par frame
    
    // Arrêter l'animation CSS automatique
    globe.style.animation = 'none';
    
    // Fonction de mise à jour de la rotation
    function updateRotation() {
        globe.style.transform = `rotateX(${rotationX}deg) rotateY(${rotationY}deg)`;
    }
    
    // Fonction d'animation avec inertie
    function animate() {
        if (autoRotate && !isDragging) {
            rotationY += autoRotateSpeed;
            if (rotationY >= 360) rotationY -= 360;
            if (rotationY < 0) rotationY += 360;
        } else if (!isDragging && (Math.abs(velocityY) > 0.1 || Math.abs(velocityX) > 0.1)) {
            // Appliquer l'inertie
            rotationY += velocityY;
            rotationX += velocityX;
            
            // Réduire progressivement la vélocité (friction)
            velocityY *= 0.95;
            velocityX *= 0.95;
            
            // Limiter la rotation X
            rotationX = Math.max(-60, Math.min(20, rotationX));
        }
        
        updateRotation();
        animationFrameId = requestAnimationFrame(animate);
    }
    
    // Démarrer l'animation
    animate();
    
    // Gestion de la souris
    globe.addEventListener('mousedown', (e) => {
        isDragging = true;
        autoRotate = false;
        startX = e.clientX;
        startY = e.clientY;
        startRotationY = rotationY;
        startRotationX = rotationX;
        lastX = e.clientX;
        lastY = e.clientY;
        lastTime = Date.now();
        velocityY = 0;
        velocityX = 0;
        globe.style.cursor = 'grabbing';
        e.preventDefault();
    });
    
    document.addEventListener('mousemove', (e) => {
        if (isDragging) {
            const deltaX = e.clientX - startX;
            const deltaY = e.clientY - startY;
            
            rotationY = startRotationY + deltaX * 0.5;
            rotationX = Math.max(-60, Math.min(20, startRotationX - deltaY * 0.3));
            
            // Calculer la vélocité pour l'inertie
            const currentTime = Date.now();
            const deltaTime = currentTime - lastTime;
            if (deltaTime > 0) {
                velocityY = ((e.clientX - lastX) * 0.5) / deltaTime * 16;
                velocityX = -((e.clientY - lastY) * 0.3) / deltaTime * 16;
            }
            
            lastX = e.clientX;
            lastY = e.clientY;
            lastTime = currentTime;
            
            updateRotation();
        }
    });
    
    document.addEventListener('mouseup', () => {
        if (isDragging) {
            isDragging = false;
            globe.style.cursor = 'grab';
            // L'inertie continuera grâce à la fonction animate
        }
    });
    
    // Gestion du touch pour mobile
    let touchStartX = 0;
    let touchStartY = 0;
    let touchStartRotationY = 0;
    let touchStartRotationX = 0;
    
    globe.addEventListener('touchstart', (e) => {
        if (e.touches.length === 1) {
            isDragging = true;
            autoRotate = false;
            touchStartX = e.touches[0].clientX;
            touchStartY = e.touches[0].clientY;
            touchStartRotationY = rotationY;
            touchStartRotationX = rotationX;
            globe.style.cursor = 'grabbing';
            e.preventDefault();
        }
    });
    
    globe.addEventListener('touchmove', (e) => {
        if (isDragging && e.touches.length === 1) {
            const deltaX = e.touches[0].clientX - touchStartX;
            const deltaY = e.touches[0].clientY - touchStartY;
            
            rotationY = touchStartRotationY + deltaX * 0.5;
            rotationX = Math.max(-60, Math.min(20, touchStartRotationX - deltaY * 0.3));
            
            updateRotation();
            e.preventDefault();
        }
    });
    
    globe.addEventListener('touchend', () => {
        isDragging = false;
        globe.style.cursor = 'grab';
    });
    
    // Curseur grab par défaut
    globe.style.cursor = 'grab';
    
    // Reprendre la rotation automatique après un délai d'inactivité
    let inactivityTimeout;
    function resetAutoRotate() {
        clearTimeout(inactivityTimeout);
        inactivityTimeout = setTimeout(() => {
            if (!isDragging) {
                autoRotate = true;
            }
        }, 3000); // Reprendre après 3 secondes d'inactivité
    }
    
    globe.addEventListener('mouseleave', () => {
        resetAutoRotate();
    });
}

/**
 * Initialise le globe 3D
 */
function initGlobe3D() {
    const globeSection = document.querySelector('.global-presence');
    if (!globeSection) return;
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Positionner les points
                positionGlobePoints();
                
                // Animer les statistiques
                setTimeout(() => {
                    animatePresenceStats();
                }, 500);
                
                // Ajouter l'interactivité
                setTimeout(() => {
                    addGlobeInteractivity();
                }, 1000);
                
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.3 });
    
    observer.observe(globeSection);
    
    // Recalculer les positions au redimensionnement
    window.addEventListener('resize', () => {
        positionGlobePoints();
    });
}