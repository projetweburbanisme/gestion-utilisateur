<?php
session_start();
require_once '../../config/database.php';

// Vérifier si l'utilisateur est déjà connecté
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
if ($user && password_verify($password, $user['password'])) {
    if ($user['is_blocked']) {
        $error = "Votre compte est bloqué. Veuillez contacter l'administrateur.";
    } else {
        // Authentification réussie
        $_SESSION['user_id'] = $user['id'];
        // ...
    }
}


// Traitement de la connexion classique
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    try {
        $pdo = new PDO('mysql:host=localhost;dbname=clyptorweb;charset=utf8', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND oauth_provider = 'local'");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['oauth_provider'] = 'local';
            $_SESSION['is_admin'] = $user['is_admin'] ?? 0;
            
            header("Location: index.php");
            exit();
        } else {
            $error = "Email ou mot de passe invalide.";
        }
    } catch (PDOException $e) {
        $error = "Erreur de base de données : " . $e->getMessage();
    }
}

// Afficher le message d'erreur s'il y en a un
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
  <script type="module" src="https://unpkg.com/@splinetool/viewer@1.9.82/build/spline-viewer.js"></script>
    <link rel="stylesheet" href="css/login.css">
    <style>
        .social-login {
            margin-top: 20px;
            text-align: center;
        }
        .social-btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            font-weight: bold;
            transition: opacity 0.3s;
        }
        .social-btn:hover {
            opacity: 0.8;
        }
        .github-btn {
            background-color: #24292e;
        }
        .facebook-btn {
            background-color: #1877f2;
        }
        .divider {
            margin: 20px 0;
            text-align: center;
            color: white;
            position: relative;
        }
        .divider::before,
        .divider::after {
            content: "";
            position: absolute;
            top: 50%;
            width: 45%;
            height: 1px;
            background-color: rgba(255, 255, 255, 0.3);
        }
        .divider::before {
            left: 0;
        }
        .divider::after {
            right: 0;
        }
    </style>
</head>
<body>
  <spline-viewer url="https://prod.spline.design/NOspby6AJwzuaFUg/scene.splinecode"></spline-viewer>

  <main class="container" id="form-container">
        <h2 id="form-title">Connexion</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

    <form id="login-form" method="POST" action="">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <button type="submit" name="login">Connexion</button>
            <div class="forgot-password">
                <a href="forgot_password.php">Mot de passe oublié ?</a>
      </div>
    </form>

        <div class="divider">ou</div>

            <div class="social-login">
                <a href="../../auth/oauth_login.php?provider=github" class="social-btn github-btn">Se connecter avec GitHub</a>
                <a href="../../auth/oauth_login.php?provider=facebook" class="social-btn facebook-btn">Se connecter avec Facebook</a>
            </div>
        </div>

    <p style="text-align:center; margin-top: 20px; color: white;">
            <a href="register.php" style="color: #0ff;">Créer un compte</a>
    </p>
  </main>
</body>
</html>
