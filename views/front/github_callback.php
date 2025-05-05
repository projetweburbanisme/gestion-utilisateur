<?php
session_start();

$clientId = 'TON_CLIENT_ID'; // Remplace par ton ID d'application GitHub
$clientSecret = 'TON_CLIENT_SECRET'; // Remplace par ton secret d'application GitHub
$redirectUri = 'http://localhost/wahdi/github_callback.php'; // URL de redirection vers le callback

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // Échange le code d'autorisation contre un access token
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query([
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $code,
                'redirect_uri' => $redirectUri
            ]),
        ],
    ];
    $context = stream_context_create($options);
    $response = file_get_contents('https://github.com/login/oauth/access_token', false, $context);

    parse_str($response, $params);
    $accessToken = $params['access_token'] ?? null;

    if ($accessToken) {
        // Récupère les informations de l'utilisateur GitHub
        $opts = ['http' => ['header' => "User-Agent: PHP\r\nAuthorization: token {$accessToken}\r\n"]];
        $context = stream_context_create($opts);
        $userData = json_decode(file_get_contents('https://api.github.com/user', false, $context), true);
        $emails = json_decode(file_get_contents('https://api.github.com/user/emails', false, $context), true);

        $email = $emails[0]['email'] ?? null;
        $username = $userData['login'] ?? 'GitHubUser';

        if ($email) {
            try {
                $pdo = new PDO('mysql:host=localhost;dbname=clyptorweb;charset=utf8mb4', 'root', '', [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);

                // Recherche l'utilisateur dans la base de données
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $existingUser = $stmt->fetch();

                if ($existingUser) {
                    $_SESSION['user_id'] = $existingUser['id'];
                    $_SESSION['username'] = $existingUser['username'];
                } else {
                    // Enregistre l'utilisateur dans la base de données
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, '')");
                    $stmt->execute([$username, $email]);
                    $_SESSION['user_id'] = $pdo->lastInsertId();
                    $_SESSION['username'] = $username;
                }

                // Redirige vers la page d'accueil
                header('Location: index.php');
                exit();
            } catch (PDOException $e) {
                echo "Erreur DB : " . $e->getMessage();
            }
        }
    }
}
?>
