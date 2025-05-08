<?php
require_once 'OAuthProvider.php';

class FacebookProvider extends OAuthProvider {
    public function getAuthUrl() {
        $params = [
            'client_id' => $this->config['facebook']['client_id'],
            'redirect_uri' => $this->config['facebook']['redirect_uri'],
            'scope' => 'email',
            'response_type' => 'code',
            'state' => bin2hex(random_bytes(16))
        ];
        
        $_SESSION['oauth_state'] = $params['state'];
        
        return 'https://www.facebook.com/v12.0/dialog/oauth?' . http_build_query($params);
    }

    public function handleCallback($code) {
        // Vérifier le state pour la sécurité CSRF
        if (!isset($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
            throw new Exception('Invalid state parameter');
        }

        // Échanger le code contre un token d'accès
        $tokenUrl = 'https://graph.facebook.com/v12.0/oauth/access_token';
        $params = [
            'client_id' => $this->config['facebook']['client_id'],
            'client_secret' => $this->config['facebook']['client_secret'],
            'code' => $code,
            'redirect_uri' => $this->config['facebook']['redirect_uri']
        ];

        $response = file_get_contents($tokenUrl . '?' . http_build_query($params));
        $token = json_decode($response, true);

        if (!isset($token['access_token'])) {
            throw new Exception('Failed to get access token');
        }

        // Obtenir les informations de l'utilisateur
        $userInfo = $this->getUserInfo($token['access_token']);
        
        // Créer ou mettre à jour l'utilisateur
        $userId = $this->createOrUpdateUser($userInfo['id'], 'facebook', [
            'username' => $userInfo['name'],
            'email' => $userInfo['email']
        ]);

        // Sauvegarder le token
        $expiresAt = date('Y-m-d H:i:s', time() + $token['expires_in']);
        $this->saveOAuthToken($userId, 'facebook', $token['access_token'], null, $expiresAt);

        return $userId;
    }

    protected function getUserInfo($accessToken) {
        $url = 'https://graph.facebook.com/v12.0/me?fields=id,name,email&access_token=' . $accessToken;
        $response = file_get_contents($url);
        return json_decode($response, true);
    }
}
