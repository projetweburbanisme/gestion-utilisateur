<?php
require_once 'OAuthProvider.php';

class GitHubProvider extends OAuthProvider {
    public function getAuthUrl() {
        $params = [
            'client_id' => $this->config['github']['client_id'],
            'redirect_uri' => $this->config['github']['redirect_uri'],
            'scope' => 'user:email',
            'state' => bin2hex(random_bytes(16))
        ];
        
        $_SESSION['oauth_state'] = $params['state'];
        
        return 'https://github.com/login/oauth/authorize?' . http_build_query($params);
    }

    public function handleCallback($code) {
        // Vérifier le state pour la sécurité CSRF
        if (!isset($_SESSION['oauth_state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
            throw new Exception('Invalid state parameter');
        }

        // Échanger le code contre un token d'accès
        $tokenUrl = 'https://github.com/login/oauth/access_token';
        $params = [
            'client_id' => $this->config['github']['client_id'],
            'client_secret' => $this->config['github']['client_secret'],
            'code' => $code,
            'redirect_uri' => $this->config['github']['redirect_uri']
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $token = json_decode($response, true);

        if (!isset($token['access_token'])) {
            throw new Exception('Failed to get access token');
        }

        // Obtenir les informations de l'utilisateur
        $userInfo = $this->getUserInfo($token['access_token']);
        
        // Créer ou mettre à jour l'utilisateur
        $userId = $this->createOrUpdateUser($userInfo['id'], 'github', [
            'username' => $userInfo['login'],
            'email' => $userInfo['email']
        ]);

        // Sauvegarder le token
        $this->saveOAuthToken($userId, 'github', $token['access_token']);

        return $userId;
    }

    protected function getUserInfo($accessToken) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/user');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'User-Agent: PHP OAuth Client'
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $userInfo = json_decode($response, true);
        
        // Obtenir l'email de l'utilisateur
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/user/emails');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'User-Agent: PHP OAuth Client'
        ]);
        
        $emailResponse = curl_exec($ch);
        curl_close($ch);
        
        $emails = json_decode($emailResponse, true);
        foreach ($emails as $email) {
            if ($email['primary']) {
                $userInfo['email'] = $email['email'];
                break;
            }
        }
        
        return $userInfo;
    }
}
