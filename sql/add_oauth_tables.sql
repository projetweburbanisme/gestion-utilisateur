-- Ajout des colonnes OAuth à la table users
ALTER TABLE users
ADD COLUMN IF NOT EXISTS oauth_provider ENUM('facebook', 'github', 'local') DEFAULT 'local',
ADD COLUMN IF NOT EXISTS oauth_id VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS oauth_token TEXT NULL,
ADD COLUMN IF NOT EXISTS oauth_token_expires DATETIME NULL,
ADD UNIQUE KEY unique_oauth_id (oauth_provider, oauth_id);

-- Table pour stocker les jetons d'accès OAuth
CREATE TABLE IF NOT EXISTS oauth_tokens (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    provider ENUM('facebook', 'github') NOT NULL,
    access_token TEXT NOT NULL,
    refresh_token TEXT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_user_id (user_id),
    CONSTRAINT fk_oauth_tokens_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
