<?php
session_start();

require __DIR__ . '/../../vendor/autoload.php'; // Vérifie que le chemin est correct

use Twilio\Rest\Client;

$identifier = $_POST['identifier'] ?? '';
$method = $_POST['method'] ?? '';
$code = rand(100000, 999999);

// Enregistrement en session
$_SESSION['reset_code'] = $code;
$_SESSION['identifier'] = $identifier;

try {
    // Connexion à la base de données
    $pdo = new PDO('mysql:host=localhost;dbname=clyptorweb;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Vérifier si l'utilisateur existe avec cet identifiant (email ou téléphone)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :id OR phone_number = :id");
    $stmt->execute(['id' => $identifier]);
    $user = $stmt->fetch();

    if (!$user) {
        exit("No account found with this identifier.");
    }

    // Envoi du code par email ou par SMS
    if ($method === 'email') {
        // Envoi par email
        mail($user['email'], "Your reset code", "Your password reset code is: $code");
    } elseif ($method === 'phone') {
        // Envoi par SMS via Twilio
        $twilio = require __DIR__ . '/../../twilio_config.php';
        $client = new Client($twilio['sid'], $twilio['token']);
        $client->messages->create(
            $user['phone_number'],
            [
                'from' => $twilio['from'],
                'body' => "Your password reset code is: $code"
            ]
        );
    } else {
        exit("Invalid method selected.");
    }

    // Redirection vers la page de vérification du code
    header("Location: verify_code.php");
    exit;

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
