<?php
session_start();

if (!isset($_POST['code'], $_POST['new_password'])) {
    exit("Incomplete form.");
}

if ($_POST['code'] != $_SESSION['reset_code']) {
    exit("Invalid code.");
}

$identifier = $_SESSION['identifier'];
$newPasswordHash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

try {
    $pdo = new PDO('mysql:host=localhost;dbname=clyptorweb;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $stmt = $pdo->prepare("UPDATE users SET password_hash = :newpass WHERE email = :id OR phone_number = :id");
    $stmt->execute(['newpass' => $newPasswordHash, 'id' => $identifier]);

    // Nettoyage session
    unset($_SESSION['reset_code'], $_SESSION['identifier']);

    echo "Password successfully reset. <a href='login.php'>Return to login</a>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
