<?php
$fbAppId = 'TON_APP_ID'; // Remplace avec ton ID d'application Facebook
$redirectUri = urlencode('http://localhost/wahdi/facebook_callback.php'); // URL de redirection vers le callback

// Redirige l'utilisateur vers la page d'authentification de Facebook
header("Location: https://www.facebook.com/v11.0/dialog/oauth?client_id={$fbAppId}&redirect_uri={$redirectUri}&response_type=code&scope=email");
exit();
