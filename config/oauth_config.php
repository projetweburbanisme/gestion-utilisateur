<?php
// Configuration OAuth pour Facebook et GitHub
return [
    'facebook' => [
        'client_id' => 'YOUR_FACEBOOK_APP_ID',
        'client_secret' => 'YOUR_FACEBOOK_APP_SECRET',
        'redirect_uri' => 'http://localhost/wahdi/auth/oauth_callback.php?provider=facebook'
    ],
    'github' => [
        'client_id' => 'YOUR_GITHUB_CLIENT_ID',
        'client_secret' => 'YOUR_GITHUB_CLIENT_SECRET',
        'redirect_uri' => 'http://localhost/wahdi/auth/oauth_callback.php?provider=github'
    ]
];
