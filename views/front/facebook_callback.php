

<?php
session_start();

require_once 'vendor/autoload.php'; // Inclure le SDK de Facebook

$fb = new \Facebook\Facebook([
  'app_id' => '1261250658752853', // Remplacez par votre App ID
  'app_secret' => '609dcf11d687b685150f28513dc6eb44', // Remplacez par votre App Secret
  'default_graph_version' => 'v12.0',
]);

$helper = $fb->getRedirectLoginHelper();

// URL de redirection après la connexion Facebook
$redirectLoginUrl = 'http://localhost/wahdi/views/front/index.php';

// Permissions demandées à l'utilisateur (ajoutez d'autres permissions si nécessaire)
$permissions = ['email']; 

// Si nous avons un code de redirection, on peut récupérer l'access token
if (isset($_GET['state'])) {
    $helper->getPersistentDataHandler()->set('state', $_GET['state']);
}

try {
    // Si nous avons un code de redirection, échanger contre un access token
    if (isset($_GET['code'])) {
        $accessToken = $helper->getAccessToken($redirectLoginUrl);
    }
} catch(Facebook\Exceptions\FacebookResponseException $e) {
    echo 'Erreur de réponse de Facebook : ' . $e->getMessage();
    exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
    echo 'Erreur du SDK de Facebook : ' . $e->getMessage();
    exit;
}

if (isset($accessToken)) {
    // Enregistrer l'access token dans la session
    $_SESSION['facebook_access_token'] = (string) $accessToken;

    // Utilisez l'access token pour récupérer les informations de l'utilisateur
    try {
        $response = $fb->get('/me?fields=id,name,email', $accessToken);
        $user = $response->getGraphUser();
        echo 'Nom: ' . $user['name'];
        echo 'Email: ' . $user['email'];
    } catch(Facebook\Exceptions\FacebookResponseException $e) {
        echo 'Erreur de récupération des informations de l’utilisateur : ' . $e->getMessage();
        exit;
    }
} else {
    // Si on n'a pas encore de token, afficher le bouton de connexion
    $loginUrl = $helper->getLoginUrl($redirectLoginUrl, $permissions);
    echo '<a href="' . $loginUrl . '">Se connecter avec Facebook</a>';
}
?>
