<?php
session_start();
$pdo = new PDO('mysql:host=localhost;dbname=clyptorweb', 'root', '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['reset_user_id'];
    $newPassword = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if ($newPassword !== $confirm) {
        echo "Passwords do not match.";
        exit();
    }

    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
    $stmt->execute(['hash' => $hash, 'id' => $userId]);

    // Clear session
    session_unset();
    session_destroy();

    echo "Password successfully updated. <a href='login.php'>Login</a>";
}
?>
