<?php
require_once 'includes/config.php';

try {
    $db = getDBConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS artist_projects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(255),
        title VARCHAR(255) NOT NULL,
        artist_name VARCHAR(255) NOT NULL,
        project_type VARCHAR(50),
        genre VARCHAR(100),
        cover_path VARCHAR(255),
        audio_path VARCHAR(255),
        status VARCHAR(50) DEFAULT 'En attente',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $db->exec($sql);
    echo "Table 'artist_projects' created or already exists.\n";
    
    // Create upload directory if not exists
    $uploadDir = __DIR__ . '/uploads/projects/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
        echo "Upload directory created: $uploadDir\n";
    }

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
