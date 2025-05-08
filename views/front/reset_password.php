<?php
session_start();
require_once '../../config/database.php';

$error = '';
$success = '';
$validToken = false;
$email = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT email FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if ($user) {
            $validToken = true;
            $email = $user['email'];
        } else {
            $error = "Le lien de réinitialisation est invalide ou a expiré.";
        }
    } catch (PDOException $e) {
        $error = "Une erreur est survenue. Veuillez réessayer plus tard.";
        error_log("Erreur de vérification du token : " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (strlen($password) < 8) {
        $error = "Le mot de passe doit contenir au moins 8 caractères.";
    } elseif ($password !== $confirmPassword) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expiry = NULL WHERE email = ?");
            $stmt->execute([$passwordHash, $email]);
            
            $success = "Votre mot de passe a été réinitialisé avec succès.";
        } catch (PDOException $e) {
            $error = "Une erreur est survenue lors de la réinitialisation du mot de passe.";
            error_log("Erreur de réinitialisation du mot de passe : " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation du mot de passe - Clyptor</title>
    <script type="module" src="https://unpkg.com/@splinetool/viewer@1.9.82/build/spline-viewer.js"></script>
    <link rel="stylesheet" href="css/login.css">
    <style>
        .reset-password-container {
            max-width: 400px;
            margin: 0 auto;
            padding: 2rem;
            background-color: rgba(0, 0, 0, 0.8);
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
        }
        
        .back-to-login {
            text-align: center;
            margin-top: 1rem;
        }
        
        .back-to-login a {
            color: #0ff;
            text-decoration: none;
        }
        
        .back-to-login a:hover {
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
        <div class="reset-password-container">
            <h2>Réinitialisation du mot de passe</h2>
            
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success">
                    <?php echo $success; ?>
                    <div class="back-to-login">
                        <a href="login.php">Retour à la connexion</a>
                    </div>
                </div>
            <?php elseif ($validToken): ?>
                <form method="POST" action="">
                    <input type="password" name="password" placeholder="Nouveau mot de passe" required>
                    <input type="password" name="confirm_password" placeholder="Confirmer le mot de passe" required>
                    <div class="password-requirements">
                        Le mot de passe doit contenir au moins 8 caractères.
                    </div>
                    <button type="submit">Réinitialiser le mot de passe</button>
                </form>
            <?php else: ?>
                <div class="back-to-login">
                    <a href="forgot_password.php">Demander un nouveau lien</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Vérification en temps réel de la correspondance des mots de passe
        document.querySelector('form')?.addEventListener('input', function(e) {
            const password = document.querySelector('input[name="password"]');
            const confirmPassword = document.querySelector('input[name="confirm_password"]');
            const submitButton = document.querySelector('button[type="submit"]');

            if (password.value && confirmPassword.value) {
                if (password.value === confirmPassword.value) {
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