<?php
session_start();
require_once '../../config/database.php';

$error = '';
$success = '';

if (!isset($_SESSION['verified_user_id'])) {
    header('Location: reset_password.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['set_password'])) {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (strlen($password) < 8) {
            $error = "Le mot de passe doit contenir au moins 8 caractères.";
        } elseif ($password !== $confirm_password) {
            $error = "Les mots de passe ne correspondent pas.";
        } else {
            $pdo = getConnection();
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Mise à jour du mot de passe et suppression du code de réinitialisation
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_code = NULL, reset_code_expiry = NULL WHERE id = ?");
            $stmt->execute([$hashed_password, $_SESSION['verified_user_id']]);
            
            // Nettoyage des sessions
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['verified_user_id']);
            
            $success = "Votre mot de passe a été réinitialisé avec succès.";
            header("refresh:3;url=login.php");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau mot de passe</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <main class="container" id="form-container">
        <h2>Définir un nouveau mot de passe</h2>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="password" name="password" placeholder="Nouveau mot de passe" required>
            <input type="password" name="confirm_password" placeholder="Confirmer le mot de passe" required>
            <button type="submit" name="set_password">Définir le nouveau mot de passe</button>
        </form>

        <p style="text-align:center; margin-top: 20px; color: white;">
            <a href="login.php" style="color: #0ff;">Retour à la connexion</a>
        </p>
    </main>
</body>
</html> 