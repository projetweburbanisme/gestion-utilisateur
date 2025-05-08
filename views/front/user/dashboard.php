<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: /wahdi/views/front/login.php');
    exit();
}

// Initialiser la connexion à la base de données
try {
    $pdo = new PDO('mysql:host=localhost;dbname=clyptorweb;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Vérifier si la table publications existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'publications'");
    if ($stmt->rowCount() === 0) {
        // Créer la table publications si elle n'existe pas
        $sql = "CREATE TABLE IF NOT EXISTS publications (
            id INT(11) NOT NULL AUTO_INCREMENT,
            user_id INT(11) NOT NULL,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            CONSTRAINT fk_publications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        $pdo->exec($sql);
        
        // Créer la table chat_messages si elle n'existe pas
        $sql = "CREATE TABLE IF NOT EXISTS chat_messages (
            id INT(11) NOT NULL AUTO_INCREMENT,
            user_id INT(11) NOT NULL,
            message TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            CONSTRAINT fk_chat_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        $pdo->exec($sql);
        
        // Créer la table user_history si elle n'existe pas
        $sql = "CREATE TABLE IF NOT EXISTS user_history (
            id INT(11) NOT NULL AUTO_INCREMENT,
            user_id INT(11) NOT NULL,
            action VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            CONSTRAINT fk_history_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        $pdo->exec($sql);
    }
} catch (Exception $e) {
    die('Erreur de connexion à la base de données : ' . $e->getMessage());
}

// Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Récupérer les publications de l'utilisateur
try {
    $stmt = $pdo->prepare("SELECT * FROM publications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $publications = $stmt->fetchAll();
} catch (PDOException $e) {
    // Si la table n'existe pas ou autre erreur, initialiser un tableau vide
    $publications = [];
}

// Récupérer l'historique des actions
$stmt = $pdo->prepare("SELECT * FROM user_history WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$_SESSION['user_id']]);
$history = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - <?php echo htmlspecialchars($user['username']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .chat-container {
            height: 400px;
            overflow-y: auto;
        }
        .history-item {
            border-left: 3px solid #007bff;
            margin-bottom: 10px;
            padding-left: 10px;
        }
        .publication-card {
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/wahdi">Urbanisme</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/wahdi/views/front/profile.php">
                            <i class="fas fa-user"></i> Mon profil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/wahdi/views/front/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Déconnexion
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row">
            <div class="col-md-4">
                <!-- Profil utilisateur -->
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <img src="https://via.placeholder.com/150" class="rounded-circle mb-3" alt="Photo de profil">
                        <h4><?php echo htmlspecialchars($user['username']); ?></h4>
                        <p class="text-muted">
                            <?php echo htmlspecialchars($user['email']); ?><br>
                            Membre depuis : <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                        </p>
                    </div>
                </div>

                <!-- Historique -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-history"></i> Historique récent</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($history as $item): ?>
                        <div class="history-item">
                            <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($item['created_at'])); ?></small>
                            <p class="mb-0"><?php echo htmlspecialchars($item['action']); ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <!-- Publications -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="fas fa-file-alt"></i> Mes publications</h5>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newPublicationModal">
                            <i class="fas fa-plus"></i> Nouvelle publication
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($publications)): ?>
                            <p class="text-center text-muted">Vous n'avez pas encore de publications</p>
                        <?php else: ?>
                            <?php foreach ($publications as $pub): ?>
                            <div class="publication-card card">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($pub['title']); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars($pub['content']); ?></p>
                                    <small class="text-muted">Publié le <?php echo date('d/m/Y H:i', strtotime($pub['created_at'])); ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Chat -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-comments"></i> Chat</h5>
                    </div>
                    <div class="card-body">
                        <div class="chat-container mb-3" id="chatMessages">
                            <!-- Les messages seront chargés ici via JavaScript -->
                        </div>
                        <form id="chatForm" class="d-flex">
                            <input type="text" class="form-control me-2" id="messageInput" placeholder="Votre message...">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nouvelle Publication -->
    <div class="modal fade" id="newPublicationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nouvelle publication</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="publicationForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Titre</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contenu</label>
                            <textarea class="form-control" name="content" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Publier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Fonction pour charger les messages du chat
        function loadChatMessages() {
            fetch('/wahdi/api/chat/messages.php')
                .then(response => response.json())
                .then(messages => {
                    const chatContainer = document.getElementById('chatMessages');
                    chatContainer.innerHTML = messages.map(msg => `
                        <div class="mb-2">
                            <strong>${msg.username}:</strong>
                            <span>${msg.message}</span>
                            <small class="text-muted">${msg.created_at}</small>
                        </div>
                    `).join('');
                    chatContainer.scrollTop = chatContainer.scrollHeight;
                });
        }

        // Charger les messages initiaux
        loadChatMessages();
        // Actualiser les messages toutes les 5 secondes
        setInterval(loadChatMessages, 5000);

        // Gestion de l'envoi des messages
        const chatForm = document.getElementById('chatForm');
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
            
            if (message) {
                fetch('/wahdi/api/chat/send.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ message })
                })
                .then(() => {
                    messageInput.value = '';
                    loadChatMessages();
                });
            }
        });

        // Gestion des publications
        const publicationForm = document.getElementById('publicationForm');
        publicationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('/wahdi/api/publications/create.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Erreur lors de la création de la publication');
                }
            });
        });
    });
    </script>
</body>
</html>
