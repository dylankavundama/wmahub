<?php
/**
 * DB SYNC - WMA Hub
 * Ce script vérifie la présence des tables essentielles et les crée si elles manquent.
 * Utile pour synchroniser la base de données locale avec la base en ligne.
 */

require_once __DIR__ . '/includes/config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<style>
    body { font-family: sans-serif; background: #0a0a0c; color: #fff; padding: 40px; }
    .success { color: #4ade80; margin-bottom: 10px; }
    .info { color: #60a5fa; margin-bottom: 10px; }
    .error { color: #f87171; margin-bottom: 10px; }
    .card { background: rgba(255,255,255,0.05); padding: 20px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.1); }
    h1 { color: #ff6600; border-bottom: 1px solid #333; padding-bottom: 10px; }
</style>";

echo "<div class='card'>";
echo "<h1>Synchronisation de la Base de Données</h1>";

try {
    $db = getDBConnection();
    echo "<p class='info'>Connexion à la base de données établie.</p>";

    // 1. Table DISTRIBUTIONS (Nouveauté)
    $sqlDistributions = "CREATE TABLE IF NOT EXISTS distributions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        artist VARCHAR(255) NOT NULL,
        image_url VARCHAR(255),
        link VARCHAR(255),
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    $db->exec($sqlDistributions);
    echo "<p class='success'>[OK] Table 'distributions' vérifiée/créée.</p>";

    // 2. Table HERO_SLIDES (Slider de l'accueil)
    $sqlHeroSlides = "CREATE TABLE IF NOT EXISTS hero_slides (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        subtitle VARCHAR(255),
        image_path VARCHAR(255) NOT NULL,
        button_text VARCHAR(50) DEFAULT 'En savoir plus',
        button_link VARCHAR(255) DEFAULT '#',
        display_order INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    $db->exec($sqlHeroSlides);
    echo "<p class='success'>[OK] Table 'hero_slides' vérifiée/créée.</p>";

    // 3. Vérification des colonnes manquantes (Migration douce)
    // Exemple : Vérifier si 'provided_files' existe dans 'projects'
    try {
        $db->query("SELECT provided_files FROM projects LIMIT 1");
    } catch (Exception $e) {
        $db->exec("ALTER TABLE projects ADD COLUMN provided_files TEXT NULL AFTER languages");
        echo "<p class='info'>[MAJ] Colonne 'provided_files' ajoutée à la table 'projects'.</p>";
    }

    echo "<br><h2 class='success'>Terminé avec succès !</h2>";
    echo "<p>Votre base de données est maintenant à jour avec les dernières fonctionnalités.</p>";
    echo "<a href='index.php' style='color: #ff6600; text-decoration: none;'>← Retour au site</a>";

} catch (PDOException $e) {
    echo "<p class='error'>ERREUR : " . $e->getMessage() . "</p>";
}

echo "</div>";
?>
