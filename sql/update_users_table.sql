-- Ajout des colonnes pour la réinitialisation du mot de passe
ALTER TABLE users
ADD COLUMN IF NOT EXISTS reset_code VARCHAR(6) NULL,
ADD COLUMN IF NOT EXISTS reset_code_expiry DATETIME NULL,
ADD COLUMN IF NOT EXISTS reset_method ENUM('email', 'sms') DEFAULT 'email';

-- Ajout de la colonne is_admin
ALTER TABLE users
ADD COLUMN is_admin BOOLEAN DEFAULT FALSE;

-- Mettre à jour l'utilisateur taki comme admin
UPDATE users SET is_admin = TRUE WHERE username = 'taki';
UPDATE users SET is_admin = TRUE WHERE username = 'taki'; 