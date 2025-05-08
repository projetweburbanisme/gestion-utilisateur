<?php
session_start();
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/oauth/FacebookProvider.php';
require_once __DIR__ . '/../includes/oauth/GitHubProvider.php';

try {
    $pdo = Database::getConnection();
    
    if (!isset($_GET['provider'])) {
        throw new Exception('Provider not specified');
    }

    $provider = $_GET['provider'];

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

    // Obtenir l'URL d'authentification et rediriger
    $authUrl = $oauthProvider->getAuthUrl();
    header('Location: ' . $authUrl);
    exit;

} catch (Exception $e) {
    $_SESSION['error'] = 'Erreur de connexion : ' . $e->getMessage();
    header('Location: ../views/front/user/login.php');
    exit;
}
