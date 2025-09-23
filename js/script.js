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