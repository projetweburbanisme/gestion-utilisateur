<!-- filepath: c:\xampp1\htdocs\ghodwa\views\front\register.php -->
<?php
session_start();
require_once '../../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    try {
        $pdo = Database::getConnection();

        // Validation des données
        $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $firstName = filter_var($_POST['first_name'], FILTER_SANITIZE_STRING);
        $lastName = filter_var($_POST['last_name'], FILTER_SANITIZE_STRING);
        $addressLine1 = filter_var($_POST['address_line1'], FILTER_SANITIZE_STRING);
        $addressLine2 = filter_var($_POST['address_line2'], FILTER_SANITIZE_STRING);
        $city = filter_var($_POST['city'], FILTER_SANITIZE_STRING);
        $stateProvince = filter_var($_POST['state_province'], FILTER_SANITIZE_STRING);
        $postalCode = filter_var($_POST['postal_code'], FILTER_SANITIZE_STRING);
        $country = filter_var($_POST['country'], FILTER_SANITIZE_STRING);
        $phoneNumber = filter_var($_POST['phone_number'], FILTER_SANITIZE_STRING);

        // Validation du mot de passe
        if (strlen($password) < 8) {
            $error = "Le mot de passe doit contenir au moins 8 caractères.";
        } else {
            // Vérification si l'email existe déjà
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->rowCount() > 0) {
                $error = "Cet email est déjà utilisé. Veuillez en utiliser un autre.";
            } else {
                // Hashage du mot de passe
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                // Insertion de l'utilisateur
                $stmt = $pdo->prepare("INSERT INTO users (
                    username, email, password_hash, first_name, last_name, 
                    address_line1, address_line2, city, state_province, 
                    postal_code, country, phone_number, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
                )");

                $stmt->execute([
                    $username, $email, $passwordHash, $firstName, $lastName,
                    $addressLine1, $addressLine2, $city, $stateProvince,
                    $postalCode, $country, $phoneNumber
                ]);

                $success = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
                header("refresh:2;url=login.php");
            }
        }
    } catch (PDOException $e) {
        error_log("Erreur d'inscription : " . $e->getMessage());
        $error = "Une erreur est survenue lors de l'inscription. Veuillez réessayer.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
    <script type="module" src="https://unpkg.com/@splinetool/viewer@1.9.82/build/spline-viewer.js"></script>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <spline-viewer url="https://prod.spline.design/NOspby6AJwzuaFUg/scene.splinecode"></spline-viewer>

    <main class="container" id="form-container">
        <h2>Inscription</h2>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="text" name="username" placeholder="Nom d'utilisateur" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <input type="text" name="first_name" placeholder="Prénom">
            <input type="text" name="last_name" placeholder="Nom">
            <input type="text" name="address_line1" placeholder="Adresse ligne 1">
            <input type="text" name="address_line2" placeholder="Adresse ligne 2">
            <input type="text" name="city" placeholder="Ville">
            <input type="text" name="state_province" placeholder="Région/Département">
            <input type="text" name="postal_code" placeholder="Code postal">
            <input type="text" name="country" placeholder="Pays">
            <input type="tel" name="phone_number" placeholder="Numéro de téléphone">
            <button type="submit" name="register">S'inscrire</button>
        </form>

        <p style="text-align:center; margin-top: 20px; color: white;">
            Déjà un compte ? <a href="login.php" style="color: #0ff;">Se connecter</a>
        </p>
    </main>
</body>
</html>