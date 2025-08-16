// Corrige la hauteur vh sur mobile
function fixVH() {
    let vh = window.innerHeight * 0.01;
    document.documentElement.style.setProperty('--vh', `${vh}px`);
}

// Gestion de la boîte de dialogue WhatsApp
function openDialog() {
    document.getElementById("whatsappDialog").style.display = "flex";
    document.body.style.overflow = "hidden";
}

function closeDialog() {
    document.getElementById("whatsappDialog").style.display = "none";
    document.body.style.overflow = "auto";
}

function sendToWhatsApp() {
    const name = document.getElementById("userName").value.trim();
    const address = document.getElementById("userAddress").value.trim();

    if (!name || !address) {
        alert("Veuillez remplir tous les champs.");
        return;
    }

    const message = `Bonjour WMAHUB, je souhaite distribuer ma musique.\nNom: ${name}\nAdresse: ${address}`;
    const phoneNumber = "256743297668";

    const url = `https://wa.me/${phoneNumber}?text=${encodeURIComponent(message)}`;
    window.open(url, '_blank');

    closeDialog();
}

// Initialisation
window.addEventListener('DOMContentLoaded', () => {
    fixVH();
    window.addEventListener('resize', fixVH);
});

// Animation au scroll
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

// Ajoutez la classe reveal aux éléments que vous voulez animer au scroll
document.addEventListener('DOMContentLoaded', function() {
    // Sélectionnez les sections à animer
    const sections = document.querySelectorAll('section');
    sections.forEach((section, index) => {
        section.classList.add('reveal');
        // Délai progressif pour un effet en cascade
        section.style.transitionDelay = `${index * 0.1}s`;
    });
    
    // Animation des cartes de fonctionnalités
    const featureCards = document.querySelectorAll('.feature-card');
    featureCards.forEach((card, index) => {
        card.style.setProperty('--order', index);
    });
    
    // Écouteur pour le scroll
    window.addEventListener('scroll', revealOnScroll);
    revealOnScroll(); // Déclenche au chargement
});

// Animation spéciale pour le hero
function heroAnimation() {
    const heroContent = document.querySelector('.hero-content');
    heroContent.style.opacity = '0';
    setTimeout(() => {
        heroContent.style.opacity = '1';
    }, 500);
}

// Appeler cette fonction au chargement
window.addEventListener('load', heroAnimation);