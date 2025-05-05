<?php
$clientId = 'TON_CLIENT_ID'; // Remplace par ton ID d'application GitHub
$redirectUri = urlencode('http://localhost/wahdi/github_callback.php'); // URL de redirection vers le callback

// Redirige l'utilisateur vers la page d'authentification de GitHub
header("Location: https://github.com/login/oauth/authorize?client_id={$clientId}&redirect_uri={$redirectUri}&scope=user:email");
exit();
