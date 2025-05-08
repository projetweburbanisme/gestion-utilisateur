-- Créer la table users si elle n'existe pas
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    address_line1 VARCHAR(100),
    address_line2 VARCHAR(100),
    city VARCHAR(50),
    state_province VARCHAR(50),
    postal_code VARCHAR(20),
    country VARCHAR(50),
    phone_number VARCHAR(20),
    is_verified TINYINT(1) DEFAULT 0,
    is_admin TINYINT(1) DEFAULT 0,
    github_id VARCHAR(100),
    facebook_id VARCHAR(100),
    reset_code VARCHAR(6),
    reset_code_expiry DATETIME,
    reset_method ENUM('email', 'sms') DEFAULT 'email',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);



-- Créer les index
CREATE INDEX idx_email ON users(email);
CREATE INDEX idx_github_id ON users(github_id);
CREATE INDEX idx_facebook_id ON users(facebook_id);
CREATE INDEX idx_reset_code ON users(reset_code);

-- Mettre à jour l'utilisateur avec l'email spécifié comme administrateur
UPDATE users SET is_admin = 1 WHERE email = 'amalmanai658@gmail.com'; 