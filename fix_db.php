<?php
require_once __DIR__ . '/includes/config.php';

try {
    $db = getDBConnection();
    
    // Check if columns exist first
    $stmt = $db->query("DESCRIBE projects");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('audio_file', $columns)) {
        $db->exec("ALTER TABLE projects ADD COLUMN audio_file VARCHAR(255) AFTER provided_files");
        echo "Column 'audio_file' added.<br>";
    } else {
        echo "Column 'audio_file' already exists.<br>";
    }
    
    if (!in_array('cover_file', $columns)) {
        $db->exec("ALTER TABLE projects ADD COLUMN cover_file VARCHAR(255) AFTER audio_file");
        echo "Column 'cover_file' added.<br>";
    } else {
        echo "Column 'cover_file' already exists.<br>";
    }
    
    echo "Migration completed successfully.";
} catch (Exception $e) {
    die("Error during migration: " . $e->getMessage());
}
?>
