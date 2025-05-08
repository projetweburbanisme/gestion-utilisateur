<!-- filepath: C:\xampp\htdocs\wahdi\views\front\forgot_password.php -->
<?php
session_start();
require_once '../../config/database.php';
require_once '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Twilio\Rest\Client;

// Configuration Gmail
$gmail_username = 'amalmanai658@gmail.com'; // Votre email Gmail
$gmail_password = 'nnqw yylx khgw grgc'; // Mot de passe d'application Gmail

// Instructions pour configurer Gmail :
// 1. Allez sur https://myaccount.google.com/security
// 2. Activez la "Validation en deux étapes" si ce n'est pas déjà fait
// 3. Allez dans "Mots de passe des applications"
// 4. Sélectionnez "Autre" comme type d'application
// 5. Nommez-la "Clyptor Password Reset"
// 6. Copiez le mot de passe généré et collez-le dans la variable $gmail_password ci-dessus

// Configuration Twilio
$twilio_config = require_once __DIR__ . '/../../config/twilio.php';
$twilio_sid = $twilio_config['account_sid'];
$twilio_token = $twilio_config['auth_token'];
$twilio_number = $twilio_config['from_number'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['request_reset'])) {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
        $reset_method = $_POST['reset_method'];
        
        error_log("Tentative de réinitialisation pour l'email: " . $email);
        error_log("Méthode choisie: " . $reset_method);
        
        try {
            $pdo = Database::getConnection();
            
            // Vérifier si l'utilisateur existe
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();
            
            if ($user) {
                error_log("Utilisateur trouvé: " . print_r($user, true));
                
                // Générer un code de vérification
                $verification_code = sprintf("%06d", mt_rand(0, 999999));
                $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                error_log("Code généré: " . $verification_code);
                error_log("Expiration: " . $expiry);
                
                // Sauvegarder le code dans la base de données
                $stmt = $pdo->prepare("UPDATE users SET reset_code = :code, reset_code_expiry = :expiry, reset_method = :method WHERE id = :id");
                $result = $stmt->execute([
                    'code' => $verification_code,
                    'expiry' => $expiry,
                    'method' => $reset_method,
                    'id' => $user['id']
                ]);
                
                if (!$result) {
                    error_log("Erreur lors de la mise à jour de la base de données: " . print_r($stmt->errorInfo(), true));
                    throw new Exception("Erreur lors de la mise à jour de la base de données");
                }
                
                if ($reset_method === 'email') {
                    error_log("Tentative d'envoi par email");
                    // Envoyer le code par email
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = $gmail_username;
                        $mail->Password = $gmail_password;
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;
                        $mail->CharSet = 'UTF-8';
                        
                        $mail->setFrom($gmail_username, 'Clyptor');
                        $mail->addAddress($email);
                        $mail->isHTML(true);
                        $mail->Subject = 'Réinitialisation de votre mot de passe';
                        $mail->Body = "
                            <h2>Réinitialisation de votre mot de passe</h2>
                            <p>Bonjour {$user['username']},</p>
                            <p>Vous avez demandé la réinitialisation de votre mot de passe. Voici votre code de vérification :</p>
                            <h1 style='color: #6c5ce7; font-size: 24px;'>{$verification_code}</h1>
                            <p>Ce code expirera dans 1 heure.</p>
                            <p>Si vous n'avez pas demandé cette réinitialisation, veuillez ignorer cet email.</p>
                            <p>Cordialement,<br>L'équipe Clyptor</p>
                        ";
                        
                        $mail->send();
                        error_log("Email envoyé avec succès");
                        $success = "Un code de vérification a été envoyé à votre adresse email.";
                    } catch (Exception $e) {
                        error_log("Erreur d'envoi d'email : " . $mail->ErrorInfo);
                        $error = "Erreur lors de l'envoi de l'email. Veuillez réessayer.";
                    }
                } else {
                    error_log("Tentative d'envoi par SMS");
                    // Envoyer le code par SMS
                    try {
                        // Charger la configuration Twilio
                        $twilio_config = require_once '../../config/twilio.php';
                        $twilio_sid = $twilio_config['account_sid'];
                        $twilio_token = $twilio_config['auth_token'];
                        $twilio_number = $twilio_config['from_number'];

                        if (empty($twilio_sid) || empty($twilio_token) || empty($twilio_number)) {
                            throw new Exception('La configuration Twilio n\'est pas complète');
                        }

                        if (empty($phone)) {
                            throw new Exception('Le numéro de téléphone est requis pour l\'envoi de SMS');
                        }

                        // Formater le numéro de téléphone au format E.164
                        $phone = preg_replace('/[^0-9]/', '', $phone);
                        if (!preg_match('/^[0-9]{8,15}$/', $phone)) {
                            throw new Exception('Numéro de téléphone invalide');
                        }
                        // Ajouter le préfixe +216 si nécessaire
                        if (strlen($phone) === 8) {
                            $phone = '+216' . $phone;
                        } elseif (!preg_match('/^\+/', $phone)) {
                            $phone = '+' . $phone;
                        }

                        error_log("Tentative d'envoi de SMS au numéro : " . $phone);
                        
                        $client = new Client($twilio_sid, $twilio_token);
                        $message = $client->messages->create(
                            $phone,
                            [
                                'from' => $twilio_number,
                                'body' => "Votre code de vérification Clyptor est : {$verification_code}. Ce code expirera dans 1 heure."
                            ]
                        );
                        error_log("SMS envoyé avec succès. SID: " . $message->sid);
                        $success = "Un code de vérification a été envoyé à votre numéro de téléphone.";
                    } catch (Exception $e) {
                        error_log("Erreur d'envoi SMS : " . $e->getMessage());
                        $error = "Erreur lors de l'envoi du SMS. Veuillez réessayer.";
                    }
                }
                
                // Stocker l'email dans la session pour la vérification
                $_SESSION['reset_email'] = $email;
            } else {
                error_log("Aucun utilisateur trouvé pour l'email: " . $email);
                $error = "Aucun compte n'est associé à cette adresse email.";
            }
        } catch (Exception $e) {
            error_log("Erreur générale : " . $e->getMessage());
            $error = "Une erreur est survenue. Veuillez réessayer.";
        }
    }
    
    // Vérification du code et réinitialisation du mot de passe
    if (isset($_POST['verify_code'])) {
        $code = filter_var($_POST['code'], FILTER_SANITIZE_STRING);
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        error_log("Tentative de vérification du code: " . $code);
        
        if ($new_password !== $confirm_password) {
            $error = "Les mots de passe ne correspondent pas.";
        } else {
            try {
                $pdo = Database::getConnection();
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND reset_code = :code AND reset_code_expiry > NOW()");
                $stmt->execute([
                    'email' => $_SESSION['reset_email'],
                    'code' => $code
                ]);
                $user = $stmt->fetch();
                
                if ($user) {
                    error_log("Code vérifié avec succès pour l'utilisateur: " . $user['id']);
                    // Mettre à jour le mot de passe
                    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password_hash = :password, reset_code = NULL, reset_code_expiry = NULL WHERE id = :id");
                    $result = $stmt->execute([
                        'password' => $password_hash,
                        'id' => $user['id']
                    ]);
                    
                    if (!$result) {
                        error_log("Erreur lors de la mise à jour du mot de passe: " . print_r($stmt->errorInfo(), true));
                        throw new Exception("Erreur lors de la mise à jour du mot de passe");
                    }
                    
                    // Supprimer l'email de la session
                    unset($_SESSION['reset_email']);
                    
                    $success = "Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.";
                } else {
                    error_log("Code invalide ou expiré pour l'email: " . $_SESSION['reset_email']);
                    $error = "Code de vérification invalide ou expiré.";
                }
            } catch (Exception $e) {
                error_log("Erreur lors de la vérification du code : " . $e->getMessage());
                $error = "Une erreur est survenue. Veuillez réessayer.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - Clyptor</title>
    <script type="module" src="https://unpkg.com/@splinetool/viewer@1.9.82/build/spline-viewer.js"></script>
  <link rel="stylesheet" href="css/login.css">
    <style>
        .forgot-password-container {
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
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #fff;
        }
        
        .radio-group {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #fff;
        }
    </style>
</head>
<body>
    <spline-viewer url="https://prod.spline.design/NOspby6AJwzuaFUg/scene.splinecode"></spline-viewer>

    <main class="container" id="form-container">
        <div class="forgot-password-container">
            <h2>Mot de passe oublié</h2>
            
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (!isset($_SESSION['reset_email'])): ?>
    <form method="POST" action="">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Numéro de téléphone</label>
                        <input type="tel" id="phone" name="phone" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Méthode de réinitialisation</label>
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="reset_method" value="email" checked>
                                Email
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="reset_method" value="sms">
                                SMS
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" name="request_reset">Envoyer le code de vérification</button>
    </form>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="code">Code de vérification</label>
                        <input type="text" id="code" name="code" required>
  </div>

                    <div class="form-group">
                        <label for="new_password">Nouveau mot de passe</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirmer le mot de passe</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" name="verify_code">Réinitialiser le mot de passe</button>
                </form>
            <?php endif; ?>
            
            <div class="back-to-login">
                <a href="login.php">Retour à la connexion</a>
            </div>
        </div>
    </main>
</body>
</html>
