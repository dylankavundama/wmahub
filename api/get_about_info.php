<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

$about_info = [
    "title" => "QUI SOMMES NOUS ?",
    "who_we_are" => "WMA Hub est une plateforme internationale de distribution musicale qui accompagne les artistes et les labels dans leur développement. Notre mission est simple : distribuer votre musique sur plus de 200 plateformes de streaming mondiales, vous offrir une meilleure visibilité et vous permettre de générer des revenus rapidement. Nous sommes une équipe de passionnés dédiée à votre succès.",
    "our_team" => "Notre équipe est composée de professionnels expérimentés dans l'industrie musicale. En rejoignant WMA Hub, vous intégrez une communauté internationale de plus de 720 artistes et 50 labels. Nous mettons notre expertise et notre réseau à votre service pour maximiser votre visibilité et développer votre carrière musicale.",
    "distribution" => "Distribuez votre musique facilement sur plus de 200 plateformes de streaming mondiales, incluant Spotify, Apple Music, Deezer, YouTube Music et bien d'autres. Aucun abonnement requis, et recevez vos royalties dans un délai maximum de 48 heures. Un processus simple, rapide et transparent.",
    "stats" => [
        ["label" => "Plateformes", "value" => "+200", "icon" => "globe"],
        ["label" => "Artistes", "value" => "+720", "icon" => "users"],
        ["label" => "Écoutes / mois", "value" => "+80M", "icon" => "headphones"],
        ["label" => "Titres", "value" => "+100K", "icon" => "music"]
    ],
    "global_presence" => [
        "subtitle" => "IMPACT GLOBAL",
        "title" => "NOTRE PRÉSENCE MONDIALE",
        "description" => "Interaction temps réel avec nos serveurs mondiaux. Nous accompagnons des talents partout sur le globe.",
        "stats" => [
            ["label" => "Artistes actifs", "value" => "+720"],
            ["label" => "Pays couverts", "value" => "150+"]
        ]
    ],
    "socials" => [
        ["platform" => "Instagram", "url" => "https://www.instagram.com/wmaunitedafrica?igsh=aDBoM3c3anIzcXEx", "handle" => "@wmaunitedafrica"],
        ["platform" => "WhatsApp", "url" => "https://wa.me/256743297668", "handle" => "+256 743 297 668"],
        ["platform" => "TikTok", "url" => "https://www.tiktok.com/@wmaplus?_r=1&_t=ZS-95Wi0zXYH5w", "handle" => "@wmaplus"],
        ["platform" => "Facebook", "url" => "https://www.facebook.com/share/1FQHLego9Z/", "handle" => "WMA Hub Official"]
    ],
    "version" => "1.0.0",
    "developed_by" => "Next Byte Technology"
];

echo json_encode($about_info);
?>
