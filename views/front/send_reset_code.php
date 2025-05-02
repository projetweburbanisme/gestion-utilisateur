<?php
session_start();

require 'vendor/autoload.php';
use Twilio\Rest\Client;

$identifier = $_POST['identifier'];
$method = $_POST['method'];
$code = rand(100000, 999999);

// Enregistrer en session
$_SESSION['reset_code'] = $code;
$_SESSION['identifier'] = $identifier;

try {
    $pdo = new PDO('mysql:host=localhost;dbname=clyptorweb;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :id OR phone_number = :id");
    $stmt->execute(['id' => $identifier]);
    $user = $stmt->fetch();

    if (!$user) {
        exit("No account found with this identifier.");
    }

    if ($method === 'email') {
        mail($user['email'], "Your reset code", "Your password reset code is: $code");
    } elseif ($method === 'phone') {
        $twilio = require 'twilio_config.php';
        $client = new Client($twilio['sid'], $twilio['token']);
        $client->messages->create(
            $user['phone_number'],
            [
                'from' => $twilio['from'],
                'body' => "Your password reset code is: $code"
            ]
        );
    }

    header("Location: verify_code.php");
    exit;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
