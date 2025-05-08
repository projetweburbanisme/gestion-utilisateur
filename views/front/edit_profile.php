<?php
session_start();
require_once '../../config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

try {
    $pdo = new PDO('mysql:host=localhost;dbname=clyptorweb;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupérer les informations actuelles de l'utilisateur
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
        $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $firstName = filter_var($_POST['first_name'], FILTER_SANITIZE_STRING);
        $lastName = filter_var($_POST['last_name'], FILTER_SANITIZE_STRING);
        $addressLine1 = filter_var($_POST['address_line1'], FILTER_SANITIZE_STRING);
        $addressLine2 = filter_var($_POST['address_line2'], FILTER_SANITIZE_STRING);
        $city = filter_var($_POST['city'], FILTER_SANITIZE_STRING);
        $stateProvince = filter_var($_POST['state_province'], FILTER_SANITIZE_STRING);
        $postalCode = filter_var($_POST['postal_code'], FILTER_SANITIZE_STRING);
        $country = filter_var($_POST['country'], FILTER_SANITIZE_STRING);
        $phoneNumber = filter_var($_POST['phone_number'], FILTER_SANITIZE_STRING);

        // Vérifier si l'email est déjà utilisé par un autre utilisateur
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $error = "Cette adresse email est déjà utilisée.";
        } else {
            // Mettre à jour les informations de l'utilisateur
            $stmt = $pdo->prepare("UPDATE users SET 
                username = ?, 
                email = ?, 
                first_name = ?, 
                last_name = ?, 
                address_line1 = ?, 
                address_line2 = ?, 
                city = ?, 
                state_province = ?, 
                postal_code = ?, 
                country = ?, 
                phone_number = ? 
                WHERE id = ?");
            $stmt->execute([
                $username, 
                $email, 
                $firstName, 
                $lastName, 
                $addressLine1, 
                $addressLine2, 
                $city, 
                $stateProvince, 
                $postalCode, 
                $country, 
                $phoneNumber, 
                $_SESSION['user_id']
            ]);

            // Mettre à jour le nom d'utilisateur dans la session
            $_SESSION['username'] = $username;

            $_SESSION['message'] = "Profil mis à jour avec succès !";
            $_SESSION['message_type'] = 'success';
            
            header('Location: ../../index.php');
            exit();
        }
    }
} catch (PDOException $e) {
    error_log("Erreur de mise à jour du profil : " . $e->getMessage());
    $error = "Une erreur est survenue lors de la mise à jour du profil.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le profil</title>
    <script type="module" src="https://unpkg.com/@splinetool/viewer@1.9.82/build/spline-viewer.js"></script>
    <link rel="stylesheet" href="css/login.css">
    <style>
        .profile-header {
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
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #fff;
        }
        .form-group input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #444;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }
        .form-group input:focus {
            border-color: #0ff;
            outline: none;
        }
        .password-link {
            color: #0ff;
            text-decoration: none;
            display: inline-block;
            margin-top: 1rem;
        }
        .password-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <spline-viewer url="https://prod.spline.design/NOspby6AJwzuaFUg/scene.splinecode"></spline-viewer>

    <main class="container" id="form-container">
        <div class="profile-header">
            <h2>Modifier le profil</h2>
            <a href="dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Retour au tableau de bord
            </a>
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>

            <div class="form-group">
                <label for="first_name">Prénom</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="last_name">Nom</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="address_line1">Adresse ligne 1</label>
                <input type="text" id="address_line1" name="address_line1" value="<?php echo htmlspecialchars($user['address_line1'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="address_line2">Adresse ligne 2</label>
                <input type="text" id="address_line2" name="address_line2" value="<?php echo htmlspecialchars($user['address_line2'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="city">Ville</label>
                <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="state_province">État/Province</label>
                <input type="text" id="state_province" name="state_province" value="<?php echo htmlspecialchars($user['state_province'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="postal_code">Code postal</label>
                <input type="text" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($user['postal_code'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="country">Pays</label>
                <input type="text" id="country" name="country" value="<?php echo htmlspecialchars($user['country'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="phone_number">Téléphone</label>
                <input type="tel" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>">
            </div>

            <button type="submit" name="update_profile">Mettre à jour le profil</button>

            <a href="change_password.php" class="password-link">
                Changer le mot de passe
            </a>
        </form>
    </main>
</body>
</html> 