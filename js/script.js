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