<?php
session_start();
require_once '../../config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=clyptorweb;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Erreur de base de données : " . $e->getMessage());
    $error = "Une erreur est survenue lors de la récupération des données.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - Clyptor</title>
    <link rel="stylesheet" href="https://unpkg.com/@splinetool/viewer@1.9.82/build/spline-viewer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6c5ce7;
            --primary-dark: #5649c0;
            --secondary: #00cec9;
            --white: #ffffff;
            --black: #121212;
            --gray: #2d3436;
            --light-gray: #636e72;
            --dark: #1e1e1e;
            --darker: #151515;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--black);
            color: var(--white);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 3rem;
            color: var(--white);
        }
        
        .profile-name {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .profile-email {
            color: var(--light-gray);
            margin-bottom: 1rem;
        }
        
        .profile-section {
            background-color: var(--darker);
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: var(--primary);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .info-item {
            margin-bottom: 1rem;
        }
        
        .info-label {
            color: var(--light-gray);
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }
        
        .info-value {
            font-size: 1.1rem;
        }
        
        .edit-button {
            background-color: var(--primary);
            color: var(--white);
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
        }
        
        .edit-button:hover {
            background-color: var(--primary-dark);
        }
        
        .logout-button {
            background-color: #ff7675;
            color: var(--white);
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
            margin-left: 1rem;
        }
        
        .logout-button:hover {
            background-color: #d63031;
        }
        
        .button-group {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
            </div>
            <h1 class="profile-name"><?php echo htmlspecialchars($user['username']); ?></h1>
            <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
        </div>

        <div class="profile-section">
            <h2 class="section-title">Informations Personnelles</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Prénom</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['first_name'] ?? 'Non renseigné'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Nom</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['last_name'] ?? 'Non renseigné'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Téléphone</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['phone_number'] ?? 'Non renseigné'); ?></div>
                </div>
            </div>
        </div>

        <div class="profile-section">
            <h2 class="section-title">Adresse</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Adresse</div>
                    <div class="info-value">
                        <?php 
                        $address = [];
                        if (!empty($user['address_line1'])) $address[] = $user['address_line1'];
                        if (!empty($user['address_line2'])) $address[] = $user['address_line2'];
                        echo !empty($address) ? htmlspecialchars(implode(', ', $address)) : 'Non renseignée';
                        ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Ville</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['city'] ?? 'Non renseignée'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Code Postal</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['postal_code'] ?? 'Non renseigné'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Pays</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['country'] ?? 'Non renseigné'); ?></div>
                </div>
            </div>
        </div>

        <div class="button-group">
            <a href="edit_profile.php" class="edit-button">Modifier le profil</a>
            <a href="logout.php" class="logout-button">Déconnexion</a>
        </div>
    </div>
</body>
</html> 