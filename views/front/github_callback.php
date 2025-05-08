<?php
session_start();
require_once '../../config/database.php';

// Configuration GitHub
$github_client_id = 'YOUR_GITHUB_CLIENT_ID';
$github_client_secret = 'YOUR_GITHUB_CLIENT_SECRET';
$github_redirect_uri = 'http://localhost/wahdi/views/front/github_callback.php';

if (isset($_GET['code'])) {
    try {
        error_log("GitHub callback received with code: " . $_GET['code']);

        // Échanger le code contre un token
        $ch = curl_init('https://github.com/login/oauth/access_token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'client_id' => $github_client_id,
            'client_secret' => $github_client_secret,
            'code' => $_GET['code'],
            'redirect_uri' => $github_redirect_uri
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            error_log("GitHub curl error: " . curl_error($ch));
        }
        curl_close($ch);
        
        error_log("GitHub token response: " . $response);
        $data = json_decode($response, true);
        
        if (isset($data['access_token'])) {
            error_log("GitHub access token received");
            
            // Récupérer les informations de l'utilisateur
            $ch = curl_init('https://api.github.com/user');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: token ' . $data['access_token'],
                'User-Agent: PHP GitHub OAuth'
            ]);
            
            $userData = json_decode(curl_exec($ch), true);
            if (curl_errno($ch)) {
                error_log("GitHub user data curl error: " . curl_error($ch));
            }
            curl_close($ch);
            
            error_log("GitHub user data: " . print_r($userData, true));
            
            // Récupérer l'email de l'utilisateur
            $ch = curl_init('https://api.github.com/user/emails');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: token ' . $data['access_token'],
                'User-Agent: PHP GitHub OAuth'
            ]);
            
            $emails = json_decode(curl_exec($ch), true);
            if (curl_errno($ch)) {
                error_log("GitHub emails curl error: " . curl_error($ch));
            }
            curl_close($ch);
            
            error_log("GitHub emails: " . print_r($emails, true));
            
            $email = '';
            foreach ($emails as $emailData) {
                if ($emailData['primary']) {
                    $email = $emailData['email'];
                    break;
                }
            }
            
            if (!$email && !empty($emails)) {
                $email = $emails[0]['email'];
            }
            
            if (empty($email)) {
                error_log("No email found for GitHub user");
                header("Location: login.php?error=no_email");
                exit();
            }
            
            // Vérifier si l'utilisateur existe déjà
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email OR github_id = :github_id");
            $stmt->execute([
                'email' => $email,
                'github_id' => $userData['id']
            ]);
            $user = $stmt->fetch();
            
            if ($user) {
                error_log("Existing user found: " . print_r($user, true));
                // Mettre à jour l'ID GitHub si nécessaire
                if (!$user['github_id']) {
                    $stmt = $pdo->prepare("UPDATE users SET github_id = :github_id WHERE id = :id");
                    $stmt->execute([
                        'github_id' => $userData['id'],
                        'id' => $user['id']
                    ]);
                    error_log("Updated GitHub ID for user");
                }
            } else {
                error_log("Creating new user");
                // Créer un nouvel utilisateur
                $stmt = $pdo->prepare("INSERT INTO users (username, email, github_id, created_at) VALUES (:username, :email, :github_id, NOW())");
                $stmt->execute([
                    'username' => $userData['login'],
                    'email' => $email,
                    'github_id' => $userData['id']
                ]);
                
                $userId = $pdo->lastInsertId();
                $user = [
                    'id' => $userId,
                    'username' => $userData['login']
                ];
                error_log("New user created: " . print_r($user, true));
            }
            
            // Connecter l'utilisateur
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            error_log("User logged in, redirecting to index.php");
            
            header("Location: index.php");
            exit();
        } else {
            error_log("No access token in GitHub response: " . print_r($data, true));
            header("Location: login.php?error=no_token");
            exit();
        }
    } catch (Exception $e) {
        error_log("GitHub OAuth Error: " . $e->getMessage());
        header("Location: login.php?error=github_auth_failed");
        exit();
    }
}

error_log("No code received in GitHub callback");
header("Location: login.php");
exit(); 