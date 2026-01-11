-- Migration script pour ajouter la fonctionnalité d'encaissement de salaire
-- Exécuter ce script pour mettre à jour la base de données existante

-- Ajouter la colonne salary à la table users si elle n'existe pas
ALTER TABLE users ADD COLUMN IF NOT EXISTS salary DECIMAL(10, 2) DEFAULT 0;

-- Ajouter la colonne promo_pack à la table projects si elle n'existe pas
ALTER TABLE projects ADD COLUMN IF NOT EXISTS promo_pack VARCHAR(50);

-- Créer la table tasks si elle n'existe pas
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image_path VARCHAR(255),
    status ENUM('en_cours', 'termine') DEFAULT 'en_cours',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Créer la table expenses si elle n'existe pas
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT,
    motif VARCHAR(255) NOT NULL,
    montant DECIMAL(10, 2) NOT NULL,
    date_depense DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
);

-- Créer la table incomes si elle n'existe pas
CREATE TABLE IF NOT EXISTS incomes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    motif VARCHAR(255) NOT NULL,
    montant DECIMAL(10, 2) NOT NULL,
    date_entree DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Créer la table salary_withdrawals (nouvelle table pour l'encaissement)
CREATE TABLE IF NOT EXISTS salary_withdrawals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    montant DECIMAL(10, 2) NOT NULL,
    date_encaissement DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Créer la table notifications si elle n'existe pas
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    reference_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
