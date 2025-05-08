<?php
session_start();

function handleLogout() {
    $oauthProvider = $_SESSION['oauth_provider'] ?? 'local';
    
    if ($oauthProvider === 'facebook') {
        header('Location: /wahdi/views/front/facebook_logout.php');
    } else {
        // For local or other providers, just destroy session and redirect
        session_destroy();
        header('Location: /wahdi/views/front/login.php');
    }
    exit();
}

handleLogout();
?>
