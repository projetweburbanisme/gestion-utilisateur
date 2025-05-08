<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Vérification de la session admin
if (!isAdmin()) {
    header('Location: ../front/login.php');
    exit();
}

// Protection CSRF
$_SESSION['csrf_token'] = generateCsrfToken();
$message = '';
$messageType = 'info';

try {
    $pdo = Database::getConnection();

    // Déblocage d'utilisateur
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unblock_user'])) {
        // Vérification CSRF
        if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
            throw new Exception('Erreur de sécurité CSRF');
        }

        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        if (!$userId) throw new Exception('ID utilisateur invalide');

        $stmt = $pdo->prepare("SELECT id, is_blocked FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) throw new Exception("Utilisateur non trouvé");

        if (!$user['is_blocked']) {
            throw new Exception("L'utilisateur n'est pas bloqué");
        }

        // Déblocage de l'utilisateur
        $stmt = $pdo->prepare("UPDATE users SET is_blocked = 0 WHERE id = ?");
        $stmt->execute([$userId]);

        $message = 'Utilisateur débloqué avec succès';
        $messageType = 'success';
    }

    header('Location: users.php');
    exit();
} catch (Exception $e) {
    error_log("Erreur : " . $e->getMessage());
    $message = $e->getMessage();
    $messageType = 'danger';

    header('Location: users.php');
    exit();
}
?>
