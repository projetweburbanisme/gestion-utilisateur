<?php
abstract class OAuthProvider {
    protected $config;
    protected $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->loadConfig();
    }

    protected function loadConfig() {
        $this->config = require __DIR__ . '/../../config/oauth_config.php';
    }

    abstract public function getAuthUrl();
    abstract public function handleCallback($code);
    abstract protected function getUserInfo($accessToken);

    protected function saveOAuthToken($userId, $provider, $accessToken, $refreshToken = null, $expiresAt = null) {
        $sql = "INSERT INTO oauth_tokens (user_id, provider, access_token, refresh_token, expires_at) 
                VALUES (:user_id, :provider, :access_token, :refresh_token, :expires_at)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':provider' => $provider,
            ':access_token' => $accessToken,
            ':refresh_token' => $refreshToken,
            ':expires_at' => $expiresAt
        ]);
    }

    protected function createOrUpdateUser($oauthId, $provider, $userData) {
        // Vérifier si l'utilisateur existe déjà
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE oauth_provider = ? AND oauth_id = ?");
        $stmt->execute([$provider, $oauthId]);
        $user = $stmt->fetch();

        if ($user) {
            // Mettre à jour l'utilisateur existant
            return $user['id'];
        } else {
            // Créer un nouvel utilisateur
            $sql = "INSERT INTO users (username, email, oauth_provider, oauth_id, status) 
                    VALUES (:username, :email, :provider, :oauth_id, 'active')";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':username' => $userData['username'],
                ':email' => $userData['email'],
                ':provider' => $provider,
                ':oauth_id' => $oauthId
            ]);

            return $this->pdo->lastInsertId();
        }
    }
}
