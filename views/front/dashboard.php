<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Récupérer les informations de l'utilisateur
try {
    $pdo = new PDO('mysql:host=localhost;dbname=clyptorweb;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare("SELECT *, COALESCE(is_admin, 0) as is_admin FROM users WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();

    // Vérifier si l'utilisateur existe
    if (!$user) {
        session_destroy();
        header("Location: login.php");
        exit();
    }

    // Vérifier si l'utilisateur est admin
    if (!$user['is_admin']) {
        header("Location: index.php");
        exit();
    }

    // Récupérer tous les utilisateurs
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();

    // Traiter les actions sur les utilisateurs
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action']) && isset($_POST['user_id'])) {
            $user_id = $_POST['user_id'];
            
            switch ($_POST['action']) {
                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND id != ?");
                    $stmt->execute([$user_id, $_SESSION['user_id']]);
                    break;
                    
                case 'toggle_admin':
                    $stmt = $pdo->prepare("UPDATE users SET is_admin = NOT is_admin WHERE id = ? AND id != ?");
                    $stmt->execute([$user_id, $_SESSION['user_id']]);
                    break;
                    
                case 'toggle_verified':
                    $stmt = $pdo->prepare("UPDATE users SET is_verified = NOT is_verified WHERE id = ?");
                    $stmt->execute([$user_id]);
                    break;
            }
            
            // Rediriger pour éviter la soumission multiple du formulaire
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }

    // Récupérer les statistiques
    $stats = [
        'total_users' => 0,
        'total_rides' => 0,
        'total_bookings' => 0,
        'total_messages' => 0
    ];

    // Statistiques pour tous les utilisateurs
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $stats['total_users'] = $stmt->fetchColumn();

    // Statistiques pour les trajets
    $stmt = $pdo->query("SELECT COUNT(*) FROM rides");
    $stats['total_rides'] = $stmt->fetchColumn();

    // Statistiques pour les réservations
    $stmt = $pdo->query("SELECT COUNT(*) FROM bookings");
    $stats['total_bookings'] = $stmt->fetchColumn();

    // Statistiques pour les messages
    $stmt = $pdo->query("SELECT COUNT(*) FROM messages");
    $stats['total_messages'] = $stmt->fetchColumn();

    // Récupérer les dernières activités
    $activities = [];
    $stmt = $pdo->query("
        SELECT 'ride' as type, created_at, 'Nouveau trajet créé' as description 
        FROM rides 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $activities = array_merge($activities, $stmt->fetchAll());

    $stmt = $pdo->query("
        SELECT 'booking' as type, created_at, 'Nouvelle réservation' as description 
        FROM bookings 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $activities = array_merge($activities, $stmt->fetchAll());

    // Trier les activités par date
    usort($activities, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    // Limiter à 5 activités
    $activities = array_slice($activities, 0, 5);

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
    <title>Tableau de bord - Clyptor</title>
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
            --success: #00b894;
            --warning: #fdcb6e;
            --danger: #d63031;
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

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background-color: var(--darker);
            padding: 1.5rem;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .welcome-message {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .admin-badge {
            background-color: var(--primary);
            color: var(--white);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-left: 1rem;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .dashboard-card {
            background-color: var(--darker);
            border-radius: 10px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: transform 0.3s ease;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .card-icon {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            transition: transform 0.3s ease;
        }

        .stat-item:hover {
            transform: translateY(-3px);
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--light-gray);
            font-size: 0.9rem;
        }

        .recent-activity {
            margin-top: 1rem;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.8rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            background-color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
        }

        .activity-details {
            flex: 1;
        }

        .activity-title {
            font-weight: 600;
            margin-bottom: 0.2rem;
        }

        .activity-time {
            color: var(--light-gray);
            font-size: 0.9rem;
        }

        .btn {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            margin-left: 0.5rem;
        }

        .btn-primary {
            background-color: var(--primary);
            color: var(--white);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-outline {
            background-color: transparent;
            border: 2px solid var(--white);
            color: var(--white);
        }

        .btn-outline:hover {
            background-color: var(--white);
            color: var(--black);
        }

        .btn-admin {
            background-color: var(--danger);
            color: var(--white);
        }

        .btn-admin:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
        }

        .admin-section {
            margin-top: 2rem;
            padding: 1.5rem;
            background-color: var(--darker);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .admin-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--danger);
        }

        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .admin-card {
            background-color: var(--dark);
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
        }

        .admin-card i {
            font-size: 2rem;
            color: var(--danger);
            margin-bottom: 0.5rem;
        }

        .admin-card h3 {
            margin-bottom: 0.5rem;
        }

        .admin-card p {
            color: var(--light-gray);
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1rem;
            }

            .dashboard-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .btn {
                margin: 0.5rem 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div>
                <h1 class="welcome-message">
                    Bienvenue, <?php echo htmlspecialchars($user['username']); ?>
                    <?php if ($user['is_admin']): ?>
                        <span class="admin-badge">Administrateur</span>
                    <?php endif; ?>
                </h1>
            </div>
            <div>
                <a href="profile.php" class="btn btn-outline">Mon Profil</a>
                <a href="logout.php" class="btn btn-primary">Déconnexion</a>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="card-header">
                    <h2 class="card-title">Statistiques</h2>
                    <i class="fas fa-chart-bar card-icon"></i>
                </div>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number" data-count="<?php echo $stats['total_users']; ?>">0</div>
                        <div class="stat-label">Utilisateurs</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" data-count="<?php echo $stats['total_rides']; ?>">0</div>
                        <div class="stat-label">Trajets</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" data-count="<?php echo $stats['total_bookings']; ?>">0</div>
                        <div class="stat-label">Réservations</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" data-count="<?php echo $stats['total_messages']; ?>">0</div>
                        <div class="stat-label">Messages</div>
                    </div>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-header">
                    <h2 class="card-title">Activité Récente</h2>
                    <i class="fas fa-history card-icon"></i>
                </div>
                <div class="recent-activity">
                    <?php if (empty($activities)): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-info"></i>
                            </div>
                            <div class="activity-details">
                                <div class="activity-title">Aucune activité récente</div>
                                <div class="activity-time">Commencez à utiliser nos services !</div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($activities as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-<?php echo $activity['type'] === 'ride' ? 'car' : 'calendar'; ?>"></i>
                                </div>
                                <div class="activity-details">
                                    <div class="activity-title"><?php echo htmlspecialchars($activity['description']); ?></div>
                                    <div class="activity-time"><?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($user['is_admin']): ?>
            <div class="admin-section">
                <h2 class="admin-title">Panneau d'administration</h2>
                <div class="admin-grid">
                    <a href="admin/users.php" class="admin-card">
                        <i class="fas fa-users"></i>
                        <h3>Gestion des utilisateurs</h3>
                        <p>Gérer les comptes utilisateurs</p>
                    </a>
                    <a href="admin/rides.php" class="admin-card">
                        <i class="fas fa-car"></i>
                        <h3>Gestion des trajets</h3>
                        <p>Gérer les trajets et réservations</p>
                    </a>
                    <a href="admin/reports.php" class="admin-card">
                        <i class="fas fa-chart-line"></i>
                        <h3>Rapports</h3>
                        <p>Voir les statistiques détaillées</p>
                    </a>
                    <a href="admin/settings.php" class="admin-card">
                        <i class="fas fa-cog"></i>
                        <h3>Paramètres</h3>
                        <p>Configurer le système</p>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <div class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title">Services Disponibles</h2>
                <i class="fas fa-th-large card-icon"></i>
            </div>
            <div class="stats-grid">
                <a href="covoiturage.php" class="stat-item">
                    <div class="stat-number"><i class="fas fa-car-alt"></i></div>
                    <div class="stat-label">Covoiturage</div>
                </a>
                <a href="home-rent.php" class="stat-item">
                    <div class="stat-number"><i class="fas fa-home"></i></div>
                    <div class="stat-label">Location de Maison</div>
                </a>
                <a href="car-rent.php" class="stat-item">
                    <div class="stat-number"><i class="fas fa-car"></i></div>
                    <div class="stat-label">Location de Voiture</div>
                </a>
            </div>
        </div>
    </div>

    <script>
        // Animation des compteurs
        document.addEventListener('DOMContentLoaded', function() {
            const counters = document.querySelectorAll('.stat-number');
            const speed = 200;

            counters.forEach(counter => {
                const target = +counter.getAttribute('data-count') || 0;
                const count = +counter.innerText;
                const increment = target / speed;

                if (count < target) {
                    counter.innerText = Math.ceil(count + increment);
                    setTimeout(updateCount, 1);
                } else {
                    counter.innerText = target;
                }

                function updateCount() {
                    const current = +counter.innerText;
                    if (current < target) {
                        counter.innerText = Math.ceil(current + increment);
                        setTimeout(updateCount, 1);
                    } else {
                        counter.innerText = target;
                    }
                }
            });
        });
    </script>
</body>
</html> 