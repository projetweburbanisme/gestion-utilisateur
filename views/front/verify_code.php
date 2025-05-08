<?php
session_start();
require_once '../../config/database.php';

$error = '';
$success = '';

if (!isset($_SESSION['reset_user_id'])) {
    header('Location: reset_password.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify_code'])) {
        $code = filter_var($_POST['code'], FILTER_SANITIZE_STRING);
        
        if (!empty($code)) {
            $pdo = getConnection();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND reset_code = ? AND reset_code_expiry > NOW()");
            $stmt->execute([$_SESSION['reset_user_id'], $code]);
            $user = $stmt->fetch();

            if ($user) {
                // Code valide, rediriger vers la page de nouveau mot de passe
                $_SESSION['verified_user_id'] = $user['id'];
                header('Location: new_password.php');
                exit();
            } else {
                $error = "Code invalide ou expiré.";
            }
        } else {
            $error = "Veuillez entrer le code de vérification.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification du code</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <main class="container" id="form-container">
        <h2>Vérification du code</h2>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="text" name="code" placeholder="Entrez le code reçu" required>
            <button type="submit" name="verify_code">Vérifier le code</button>
        </form>

        <p style="text-align:center; margin-top: 20px; color: white;">
            <a href="reset_password.php" style="color: #0ff;">Retour</a>
        </p>
    </main>
</body>
</html>
