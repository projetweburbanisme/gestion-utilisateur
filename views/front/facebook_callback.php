<?php
session_start();

// Inclure la bibliothèque Facebook SDK
require_once __DIR__ . '/vendor/autoload.php'; // Assurez-vous que le chemin vers le SDK est correct

$fbAppId = 'TON_APP_ID'; // Remplace avec ton ID d'application Facebook
$fbAppSecret = 'TON_APP_SECRET'; // Remplace avec ton secret d'application Facebook
$redirectUri = 'http://localhost/wahdi/facebook_callback.php'; // L'URL de retour

// Initialiser l'objet Facebook
$fb = new \Facebook\Facebook([
    'app_id' => $fbAppId,
    'app_secret' => $fbAppSecret,
    'default_graph_version' => 'v11.0',
]);

// Vérifier s'il y a un code dans l'URL (après la redirection de Facebook)
if (isset($_GET['code'])) {
    $helper = $fb->getRedirectLoginHelper();
    
    try {
        // Obtenir le token d'accès
        $accessToken = $helper->getAccessToken($redirectUri);
        
        if (!isset($accessToken)) {
            exit('Erreur de récupération du token d\'accès');
        }

        // Utiliser le token pour obtenir les informations de l'utilisateur
        $response = $fb->get('/me?fields=id,name,email', $accessToken);
        $user = $response->getGraphUser();
        
        // Vérifier si l'utilisateur existe déjà dans la base de données
        $pdo = new PDO('mysql:host=localhost;dbname=clyptorweb;charset=utf8mb4', 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE facebook_id = :facebook_id");
        $stmt->execute(['facebook_id' => $user['id']]);
        $existingUser = $stmt->fetch();

        if ($existingUser) {
            // Si l'utilisateur existe, on l'authentifie (connexion)
            $_SESSION['user_id'] = $existingUser['id'];
            header('Location: dashboard.php'); // Rediriger vers la page d'accueil
            exit;
        } else {
            // Si l'utilisateur n'existe pas, on l'enregistre en tant que nouvel utilisateur
            $stmt = $pdo->prepare("INSERT INTO users (facebook_id, name, email) VALUES (:facebook_id, :name, :email)");
            $stmt->execute([
                'facebook_id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
            ]);

            // Récupérer l'ID de l'utilisateur qui vient d'être ajouté
            $userId = $pdo->lastInsertId();
            
            // Authentifier l'utilisateur
            $_SESSION['user_id'] = $userId;
            
            // Rediriger vers la page d'accueil ou tableau de bord
            header('Location: dashboard.php');
            exit;
        }
    } catch(\Facebook\Exceptions\FacebookResponseException $e) {
        // Erreur de la réponse Facebook
        exit('Erreur de la réponse de Facebook: ' . $e->getMessage());
    } catch(\Facebook\Exceptions\FacebookSDKException $e) {
        // Erreur du SDK Facebook
        exit('Erreur du SDK Facebook: ' . $e->getMessage());
    }
} else {
    // Si le code n'est pas présent, rediriger à la page de login
    header("Location: login.php");
    exit();
}
