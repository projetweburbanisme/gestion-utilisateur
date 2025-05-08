<?php
require_once '../../config/oauth_config.php';
require_once '../../vendor/autoload.php';

session_start();

// Initialize Facebook SDK
$fb = new Facebook\Facebook([
    'app_id' => FB_APP_ID,
    'app_secret' => FB_APP_SECRET,
    'default_graph_version' => 'v12.0'
]);

// Get the redirect helper
$helper = $fb->getRedirectLoginHelper();

// Get access token from session
$accessToken = isset($_SESSION['facebook_access_token']) ? $_SESSION['facebook_access_token'] : null;

if ($accessToken) {
    // Get logout URL
    $logoutUrl = $helper->getLogoutUrl($accessToken, 'http://' . $_SERVER['HTTP_HOST'] . '/wahdi/views/front/login.php');
    
    // Clear Facebook session
    unset($_SESSION['facebook_access_token']);
    unset($_SESSION['facebook_user']);
    
    // Clear all session data
    session_destroy();
    
    // Redirect to Facebook logout URL
    header('Location: ' . $logoutUrl);
    exit;
} else {
    // If no access token, just clear session and redirect
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
