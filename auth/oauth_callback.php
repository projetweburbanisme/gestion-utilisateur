<?php
session_start();
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . 'http://localhost/clyptor/fb-callback.php
';
require_once __DIR__ . '/../includes/oauth/GitHubProvider.php';

try {
    $pdo = Database::getConnection();
    
    if (!isset($_GET['provider']) || !isset($_GET['code'])) {
        throw new Exception('Invalid request');
    }

    $provider = $_GET['provider'];
    $code = $_GET['code'];

    switch ($provider) {
        case 'facebook':
            $oauthProvider = new FacebookProvider($pdo);
            break;
        case 'github':
            $oauthProvider = new GitHubProvider($pdo);
            break;
        default:
            throw new Exception('Invalid provider');
    }

    // Gérer le callback OAuth
    $userId = $oauthProvider->handleCallback($code);

    // Connecter l'utilisateur
    $_SESSION['user_id'] = $userId;
    $_SESSION['oauth_provider'] = $provider;

    // Rediriger vers le tableau de bord
    header('Location: ../views/front/user/dashboard.php');
    exit;

} catch (Exception $e) {
    // En cas d'erreur, rediriger vers la page de connexion avec un message d'erreur
    $_SESSION['error'] = 'Erreur d\'authentification : ' . $e->getMessage();
    header('Location: ../views/front/user/login.php');
    exit;
}
