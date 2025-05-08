<?php
require_once '../../config/database.php';

try {
    $pdo = Database::getConnection();
    
    // Vérifier si l'utilisateur existe
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(['amalmanai658@gmail.com']);
    $user = $stmt->fetch();
    
    if ($user) {
        // Générer un nouveau mot de passe
        $newPassword = 'Admin123!';
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Mettre à jour le mot de passe
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
        $stmt->execute([$passwordHash, 'amalmanai658@gmail.com']);
        
        echo "Mot de passe réinitialisé avec succès!<br>";
        echo "Email: amalmanai658@gmail.com<br>";
        echo "Nouveau mot de passe: " . $newPassword . "<br>";
        echo "<a href='login.php'>Aller à la page de connexion</a>";
    } else {
        echo "L'utilisateur n'existe pas. Création d'un nouveau compte administrateur...<br>";
        
        // Créer un nouveau compte administrateur
        $username = 'admin';
        $email = 'amalmanai658@gmail.com';
        $password = 'Admin123!';
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, is_admin, is_verified) VALUES (?, ?, ?, 1, 1)");
        $stmt->execute([$username, $email, $passwordHash]);
        
        echo "Compte administrateur créé avec succès!<br>";
        echo "Email: " . $email . "<br>";
        echo "Mot de passe: " . $password . "<br>";
        echo "<a href='login.php'>Aller à la page de connexion</a>";
    }
} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage();
}
?> 