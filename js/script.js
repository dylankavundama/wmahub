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
    const nouvellePageURL = 'auth/login.php';
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
// GLOBAL-IMPACT REDIRECTION
//==================================================
// L'initialisation du nouveau Globe 3D se fait directement dans index.php
