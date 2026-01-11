-- Migration for Chat and Notifications system

CREATE TABLE IF NOT EXISTS global_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT DEFAULT NULL, -- NULL for broadcast/main chat
    message TEXT,
    image_path VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Ensure notifications table is flexible
-- Already exists in init.sql, adding message field if not present
-- ALTER TABLE notifications ADD COLUMN message TEXT AFTER type; 
-- Actually, we'll use 'type' to determine the message template and 'reference_id' for the link.
