<?php
session_start();
require_once '../../config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

try {
    $pdo = new PDO('mysql:host=localhost;dbname=clyptorweb;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Vérifier que l'utilisateur existe toujours
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        session_destroy();
        header("Location: login.php");
        exit();
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];

        // Récupérer le mot de passe actuel de l'utilisateur
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        // Vérifier le mot de passe actuel
        if (!password_verify($currentPassword, $user['password_hash'])) {
            $error = "Le mot de passe actuel est incorrect.";
        }
        // Vérifier que le nouveau mot de passe est différent de l'ancien
        elseif (password_verify($newPassword, $user['password_hash'])) {
            $error = "Le nouveau mot de passe doit être différent de l'ancien.";
        }
        // Vérifier la longueur du nouveau mot de passe
        elseif (strlen($newPassword) < 8) {
            $error = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
        }
        // Vérifier que les mots de passe correspondent
        elseif ($newPassword !== $confirmPassword) {
            $error = "Les nouveaux mots de passe ne correspondent pas.";
        }
        else {
            // Hasher et mettre à jour le nouveau mot de passe
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$newPasswordHash, $_SESSION['user_id']]);

            $success = "Mot de passe modifié avec succès !";
        }
    }
} catch (PDOException $e) {
    error_log("Erreur de changement de mot de passe : " . $e->getMessage());
    $error = "Une erreur est survenue lors du changement de mot de passe.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Changer le mot de passe</title>
    <script type="module" src="https://unpkg.com/@splinetool/viewer@1.9.82/build/spline-viewer.js"></script>
    <link rel="stylesheet" href="css/login.css">
    <style>
        .password-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .back-btn {
            color: #0ff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .back-btn:hover {
            text-decoration: underline;
        }
        .success {
            background-color: rgba(0, 255, 0, 0.1);
            color: #00ff00;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        .error {
            background-color: rgba(255, 0, 0, 0.1);
            color: #ff0000;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        .password-requirements {
            color: #888;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <spline-viewer url="https://prod.spline.design/NOspby6AJwzuaFUg/scene.splinecode"></spline-viewer>

    <main class="container" id="form-container">
        <div class="password-header">
            <h2>Changer le mot de passe</h2>
            <a href="index.php" class="back-btn" onclick="return confirm('Voulez-vous vraiment retourner à l\'accueil ? Les modifications non sauvegardées seront perdues.');">
                <i class="fas fa-arrow-left"></i>
                Retour à l'accueil
            </a>
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="password" name="current_password" placeholder="Mot de passe actuel" required>
            <input type="password" name="new_password" placeholder="Nouveau mot de passe" required>
            <input type="password" name="confirm_password" placeholder="Confirmer le nouveau mot de passe" required>
            <div class="password-requirements">
                Le mot de passe doit contenir au moins 8 caractères.
            </div>
            <button type="submit" name="change_password">Changer le mot de passe</button>
        </form>
    </main>

    <script>
        // Vérification en temps réel de la correspondance des mots de passe
        document.querySelector('form').addEventListener('input', function(e) {
            const newPassword = document.querySelector('input[name="new_password"]');
            const confirmPassword = document.querySelector('input[name="confirm_password"]');
            const submitButton = document.querySelector('button[type="submit"]');

            if (newPassword.value && confirmPassword.value) {
                if (newPassword.value === confirmPassword.value) {
                    confirmPassword.style.borderColor = '#00ff00';
                    submitButton.disabled = false;
                } else {
                    confirmPassword.style.borderColor = '#ff0000';
                    submitButton.disabled = true;
                }
            }
        });
    </script>
</body>
</html> 