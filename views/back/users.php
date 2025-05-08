<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Vérification de la session admin
if (!isAdmin()) {
    header('Location: ../front/login.php');
    exit();
}

// Vérification des emails autorisés pour l'accès admin
$allowedAdmins = ['amalmanai658@gmail.com', 'taki.mejri001@gmail.com'];
if (!isset($_SESSION['email']) || !in_array($_SESSION['email'], $allowedAdmins)) {
    // Rediriger vers la page de connexion ou afficher un message d'erreur
    header('Location: ../front/login.php');
    exit();
}

// Protection CSRF
$_SESSION['csrf_token'] = generateCsrfToken();
$message = '';
$messageType = 'info';

function setFlashMessage($type, $message) {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

try {
    $pdo = Database::getConnection();

    // Traitement des formulaires
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Vérification CSRF
        if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
            throw new Exception('Erreur de sécurité CSRF');
        }

        // Création d'utilisateur
        if (isset($_POST['create_user'])) {
            $userData = [
                'username' => filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING),
                'email' => filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL),
                'password' => $_POST['password'],
                'first_name' => filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING),
                'last_name' => filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING),
                'phone_number' => filter_input(INPUT_POST, 'phone_number', FILTER_SANITIZE_STRING),
                'address_line1' => filter_input(INPUT_POST, 'address_line1', FILTER_SANITIZE_STRING)
            ];

            $errors = validateUserData($userData);
            if (!empty($errors)) {
                throw new Exception(implode(', ', $errors));
            }

            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$userData['email']]);
            if ($stmt->fetch()) {
                throw new Exception('Cet email est déjà utilisé');
            }

            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name, phone_number, address_line1, verification_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
            $stmt->execute([
                $userData['username'],
                $userData['email'],
                $hashedPassword,
                $userData['first_name'],
                $userData['last_name'],
                $userData['phone_number'],
                $userData['address_line1']
            ]);

            $message = 'Utilisateur créé avec succès';
            $messageType = 'success';
        }

        // Modification d'utilisateur
        if (isset($_POST['edit_user'])) {
            $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
            $userData = [
                'username' => filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING),
                'email' => filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL),
                'first_name' => filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING),
                'last_name' => filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING),
                'phone_number' => filter_input(INPUT_POST, 'phone_number', FILTER_SANITIZE_STRING),
                'address_line1' => filter_input(INPUT_POST, 'address_line1', FILTER_SANITIZE_STRING)
            ];

            $errors = validateUserData($userData);
            if (!empty($errors)) {
                throw new Exception(implode(', ', $errors));
            }

            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$userData['email'], $userId]);
            if ($stmt->fetch()) {
                throw new Exception('Cet email est déjà utilisé');
            }

            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ?, phone_number = ?, address_line1 = ? WHERE id = ?");
            $stmt->execute([
                $userData['username'],
                $userData['email'],
                $userData['first_name'],
                $userData['last_name'],
                $userData['phone_number'],
                $userData['address_line1'],
                $userId
            ]);

            $message = 'Utilisateur modifié avec succès';
            $messageType = 'success';
        }

        // Suppression d'utilisateur
        if (isset($_POST['delete_user'])) {
            $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
            if (!$userId) throw new Exception('ID utilisateur invalide');

            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);

            $message = 'Utilisateur supprimé avec succès';
            $messageType = 'success';
        }



        // Vérification d'utilisateur
        if (isset($_POST['action'])) {
            $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
            $action = $_POST['action'];

            if (!$userId || !in_array($action, ['approve', 'reject'])) {
                throw new Exception('Données invalides');
            }

            $status = $action === 'approve' ? 'Approved' : 'Rejected';
            $stmt = $pdo->prepare("UPDATE users SET verification_status = ?, verified_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $userId]);

            $message = 'Statut utilisateur mis à jour';
            $messageType = 'success';
        }

        header('Location: users.php');
        exit();
    }

    // Statistiques
    $stats = [
        'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'active_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE verification_status = 'Approved'")->fetchColumn(),
        'pending_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE verification_status = 'Pending'")->fetchColumn(),
        'new_users_today' => $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn()
    ];

    // Pagination
    $page = max(1, $_GET['page'] ?? 1);
    $perPage = 10;
    $offset = ($page - 1) * $perPage;

    // Recherche et filtrage
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? 'all';
    $sort = $_GET['sort'] ?? 'created_at';
    $order = $_GET['order'] ?? 'DESC';

    $allowedSorts = ['created_at', 'username', 'email', 'verification_status'];
    $allowedOrders = ['ASC', 'DESC'];
    if (!in_array($sort, $allowedSorts)) $sort = 'created_at';
    if (!in_array($order, $allowedOrders)) $order = 'DESC';

    $sql = "SELECT * FROM users WHERE 1=1";
    $params = [];

    if (!empty($search)) {
        $sql .= " AND (username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }

    if ($status !== 'all') {
        $sql .= " AND verification_status = ?";
        $params[] = $status;
    }

    $countStmt = $pdo->prepare(str_replace('SELECT *', 'SELECT COUNT(*)', $sql));
    $countStmt->execute($params);
    $totalUsers = $countStmt->fetchColumn();
    $totalPages = ceil($totalUsers / $perPage);

    $sql .= " ORDER BY $sort $order LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();

} catch (Exception $e) {
    error_log("Erreur : " . $e->getMessage());
    $message = $e->getMessage();
    $messageType = 'danger';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Location: users.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.0.19/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        .stats-card {
            background: linear-gradient(45deg, #4b6cb7, #182848);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        .stats-card:hover {
            transform: scale(1.02);
        }
    </style>
</head>
<body>
    header('Location: users.php');
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .user-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
        }
        .status-pending { background-color: #ffeeba; color: #856404; }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-blocked { background-color: #d4edda; color: #155724; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
        .action-buttons .btn { margin: 0 2px; }
        .pagination { margin-bottom: 0; }
        .modal-header { background-color: #f8f9fa; }
        .required::after { content: ' *'; color: red; }
    </style>
</head>

<?php include '../../includes/admin_navbar.php'; ?>

<div class="container-fluid py-4">
    <!-- Messages de notification -->
    <?php if (isset($message)): ?>
        <div class="alert alert-<?= $messageType ?? 'info' ?> alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <h3 class="h5">Total Utilisateurs</h3>
                <h2 class="display-4"><?= $stats['total_users'] ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <h3 class="h5">Utilisateurs Actifs</h3>
                <h2 class="display-4"><?= $stats['active_users'] ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <h3 class="h5">En Attente</h3>
                <h2 class="display-4"><?= $stats['pending_users'] ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <h3 class="h5">Nouveaux Aujourd'hui</h3>
                <h2 class="display-4"><?= $stats['new_users_today'] ?></h2>
            </div>
        </div>
    </div>

    <!-- En-tête avec filtres -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="h3 mb-0">Gestion des Utilisateurs</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                    <i class="fas fa-plus"></i> Nouvel Utilisateur
                </button>
            </div>

            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Rechercher...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Tous les statuts</option>
                        <option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>En attente</option>
                        <option value="Approved" <?= $status === 'Approved' ? 'selected' : '' ?>>Approuvé</option>
                        <option value="Rejected" <?= $status === 'Rejected' ? 'selected' : '' ?>>Rejeté</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="sort">
                        <option value="created_at" <?= $sort === 'created_at' ? 'selected' : '' ?>>Date d'inscription</option>
                        <option value="username" <?= $sort === 'username' ? 'selected' : '' ?>>Nom d'utilisateur</option>
                        <option value="email" <?= $sort === 'email' ? 'selected' : '' ?>>Email</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                </div>
            </form>
        </div>
    </div>
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['message']; 
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-users"></i> Gestion des Utilisateurs</h2>
        </div>
    </div>

    <!-- Search Box -->
    <div class="row mb-4">
        <div class="col-md-6">
            <form action="" method="GET" class="search-box">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Rechercher par nom d'utilisateur ou email" value="<?php echo htmlspecialchars($searchQuery); ?>">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>
        <div class="col-md-6 text-end">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="fas fa-user-plus"></i> Nouvel Utilisateur
            </button>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nom d'utilisateur</th>
                            <th>Email</th>
                            <th>Nom complet</th>
                            <th>Téléphone</th>
                            <th>Statut</th>
                            <th>Date création</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['phone_number']); ?></td>
                            <td><?php echo htmlspecialchars($user['status']); ?></td>
                            <td>
                                <span class="verification-status status-<?php echo strtolower($user['verification_status'] ?? 'pending'); ?>">
                                    <?php echo htmlspecialchars($user['verification_status'] ?? 'En attente'); ?>
                                </span>
                            </td> 
                            <td>
                            <td>
    <a href="edit_user.php?id=<?= $user['id']; ?>">Edit</a> |
    <a href="delete_user.php?id=<?= $user['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a> |
    <?php if($user['status'] == 'active'): ?>
        <a href="block_user.php?id=<?= $user['id']; ?>" class="btn btn-warning btn-sm">Block</a>
    <?php else: ?>
        <a href="unblock_user.php?id=<?= $user['id']; ?>" class="btn btn-success btn-sm">Unblock</a>
    <?php endif; ?>
</td>

        <a href="edit_user.php?id=<?= $user['id']; ?>">Edit</a> |
        <a href="delete_user.php?id=<?= $user['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a> |
        <?php if($user['status'] == 'active'): ?>
            <a href="block_user.php?id=<?= $user['id']; ?>">Block</a>
        <?php else: ?>
            <a href="unblock_user.php?id=<?= $user['id']; ?>">Unblock</a>
        <?php endif; ?>
    </td>
                            <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($user['created_at']))); ?></td>
                            <td class="action-buttons">
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editUser<?php echo $user['id']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteUser<?php echo $user['id']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                                </button>
    <form method="POST" style="display: inline;">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
        <button type="submit" name="toggle_block" class="btn btn-sm <?php echo $user['is_blocked'] ? 'btn-success' : 'btn-warning'; ?>">
            <?php echo $user['is_blocked'] ? 'Débloquer' : 'Bloquer'; ?>
        </button>
    </form>
                                <?php if (($user['verification_status'] ?? '') !== 'Approved'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Confirmer l\'approbation ?')">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Edit User Modal -->
                        <div class="modal fade" id="editUser<?php echo $user['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Modifier l'utilisateur <?= htmlspecialchars($user['username']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST" name="editUserForm">
                                        <div class="modal-body">
                                            <input type="hidden" name="edit_user" value="1">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <div class="alert alert-info">
                                                Les champs marqués d'un * sont obligatoires
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Nom d'utilisateur *</label>
                                                    <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Email *</label>
                                                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Prénom</label>
                                                    <input type="text" class="form-control" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Nom</label>
                                                    <input type="text" class="form-control" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Téléphone</label>
                                                    <input type="tel" class="form-control" name="phone_number" value="<?= htmlspecialchars($user['phone_number']) ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Adresse 1</label>
                                                    <input type="text" class="form-control" name="address_line1" value="<?= htmlspecialchars($user['address_line1']) ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Adresse 2</label>
                                                    <input type="text" class="form-control" name="address_line2" value="<?= htmlspecialchars($user['address_line2']) ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Ville</label>
                                                    <input type="text" class="form-control" name="city" value="<?= htmlspecialchars($user['city']) ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Pays</label>
                                                    <input type="text" class="form-control" name="country" value="<?= htmlspecialchars($user['country']) ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                            <button type="submit" class="btn btn-primary" onclick="return confirm('Voulez-vous vraiment modifier cet utilisateur ?')">Enregistrer</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <form method="POST" style="display:inline;">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
    <button type="submit" name="toggle_block" class="btn btn-sm <?= $user['is_blocked'] ? 'btn-success' : 'btn-warning' ?>">
        <?= $user['is_blocked'] ? 'Débloquer' : 'Bloquer' ?>
    </button>
</form>

                        <!-- Delete User Modal -->
                        <div class="modal fade" id="deleteUser<?php echo $user['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Confirmer la suppression</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        Êtes-vous sûr de vouloir supprimer l'utilisateur "<?php echo htmlspecialchars($user['username']); ?>" ?
                                    </div>
                                    <div class="modal-footer">
                                        <form method="POST" name="editUserForm">
                                            <input type="hidden" name="delete_user">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                            <button type="submit" class="btn btn-danger">Supprimer</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nouvel Utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" name="editUserForm">
                    <div class="modal-body">
                        <input type="hidden" name="create_user">
                        <div class="alert alert-info">
                            Les champs marqués d'un * sont obligatoires
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nom d'utilisateur *</label>
                                <input type="text" class="form-control" name="username" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mot de passe *</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" name="phone_number">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Prénom</label>
                                <input type="text" class="form-control" name="first_name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nom</label>
                                <input type="text" class="form-control" name="last_name">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Adresse 1</label>
                                <input type="text" class="form-control" name="address_line1">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Adresse 2</label>
                                <input type="text" class="form-control" name="address_line2">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Ville</label>
                                <input type="text" class="form-control" name="city">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Province/État</label>
                                <input type="text" class="form-control" name="state_province">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Code postal</label>
                                <input type="text" class="form-control" name="postal_code">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Pays</label>
                                <input type="text" class="form-control" name="country">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-success">Créer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestion des formulaires de vérification
        const verifyForms = document.querySelectorAll('.verify-form');
        verifyForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const action = this.querySelector('button[type="submit"]:focus').value;
                const actionText = action === 'approve' ? 'approuver' : 'rejeter';
                
                Swal.fire({
                    title: 'Confirmer l\'action',
                    text: `Êtes-vous sûr de vouloir ${actionText} cet utilisateur ?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Oui',
                    cancelButtonText: 'Annuler'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        });

        // Gestion des formulaires de suppression
        const deleteForms = document.querySelectorAll('.delete-form');
        deleteForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Attention !',
                    text: 'Cette action est irréversible. Voulez-vous vraiment supprimer cet utilisateur ?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Oui, supprimer',
                    cancelButtonText: 'Annuler'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        });
        // Gestion des modales d'édition
        const editModals = document.querySelectorAll('[id^="editUser"]');
        editModals.forEach(modal => {
            modal.addEventListener('shown.bs.modal', function() {
                this.querySelector('input[name="username"]').focus();
            });
        });

        // Gestion des modales de suppression
        const deleteModals = document.querySelectorAll('[id^="deleteUser"]');
        deleteModals.forEach(modal => {
            const form = modal.querySelector('form');
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Êtes-vous sûr ?',
                    text: 'Cette action est irréversible !',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Oui, supprimer',
                    cancelButtonText: 'Annuler'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });

        // Gestion du formulaire de création
        const createForm = document.querySelector('#createUserModal form');
        if (createForm) {
            createForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const password = this.querySelector('input[name="password"]').value;
                const email = this.querySelector('input[name="email"]').value;
                const username = this.querySelector('input[name="username"]').value;

                if (!password || !email || !username) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: 'Veuillez remplir tous les champs obligatoires (nom d\'utilisateur, email et mot de passe)'
                    });
                    return;
                }

                Swal.fire({
                    title: 'Confirmer la création',
                    text: 'Êtes-vous sûr de vouloir créer cet utilisateur ?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Oui, créer',
                    cancelButtonText: 'Annuler'
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.submit();
                    }
                });
            });
        }

        // Gestion des formulaires d'édition
        const editForms = document.querySelectorAll('form[name="editUserForm"]');
        editForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const email = this.querySelector('input[name="email"]').value;
                const username = this.querySelector('input[name="username"]').value;

                if (!email || !username) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: 'Le nom d\'utilisateur et l\'email sont obligatoires'
                    });
                    return;
                }

                Swal.fire({
                    title: 'Confirmer la modification',
                    text: 'Êtes-vous sûr de vouloir modifier cet utilisateur ?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Oui, modifier',
                    cancelButtonText: 'Annuler'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });

        // Fermeture des alertes
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    alert.remove();
                });
            }
        });
    });
    </script>
</body>
                        
                        <label for="edit-phone-number">Phone:</label>
                        <input type="text" id="edit-phone-number" name="phone_number" required>
                        
                        <label for="edit-address-line1">Address Line 1:</label>
                        <input type="text" id="edit-address-line1" name="address_line1" required>
                        
                        <label for="edit-address-line2">Address Line 2:</label>
                        <input type="text" id="edit-address-line2" name="address_line2">
                        
                        <label for="edit-city">City:</label>
                        <input type="text" id="edit-city" name="city" required>
                        
                        <label for="edit-country">Country:</label>
                        <input type="text" id="edit-country" name="country" required>
                        
                        <button type="submit" name="edit_user" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>

            <h2>All Cars</h2>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Car Make</th>
                        <th>Car Model</th>
                        <th>Car Year</th>
                        <th>Registration Number</th>
                        <th>Verification Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cars as $car): ?>
                        <tr>
                            <td><?= htmlspecialchars($car['username']) ?></td>
                            <td><?= htmlspecialchars($car['car_make']) ?></td>
                            <td><?= htmlspecialchars($car['car_model']) ?></td>
                            <td><?= htmlspecialchars($car['car_year']) ?></td>
                            <td><?= htmlspecialchars($car['car_registration_number']) ?></td>
                            <td><?= htmlspecialchars($car['car_verification_status']) ?></td>
                            <td>
                                <?php if ($car['car_verification_status'] === 'Pending'): ?>
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="car_id" value="<?= $car['id'] ?>">
                                        <button type="submit" name="approve_car" class="btn btn-success">Approve</button>
                                    </form>
                                <?php endif; ?>
                                <button class="btn btn-warning edit-car-btn" data-car='<?= json_encode($car) ?>'>Edit</button>
                                <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this car?');">
                                    <input type="hidden" name="car_id" value="<?= $car['id'] ?>">
                                    <button type="submit" name="delete_car" class="btn btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            
                        
                        <label for="edit-car-model">Car Model:</label>
                        <input type="text" id="edit-car-model" name="car_model" required>
                        
                        <label for="edit-car-year">Car Year:</label>
                        <input type="number" id="edit-car-year" name="car_year" required>
                        
                        <label for="edit-car-registration-number">Registration Number:</label>
                        <input type="text" id="edit-car-registration-number" name="car_registration_number" required>
                        
                        <button type="submit" name="edit_car" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </main>
        <!-- End of Main Content -->

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const editButtons = document.querySelectorAll('.edit-btn');
            const editModal = document.getElementById('edit-user-modal');
            const closeModal = document.querySelector('.close-modal');
            const editForm = document.getElementById('edit-user-form');

            editButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const user = JSON.parse(button.getAttribute('data-user'));
                    document.getElementById('edit-user-id').value = user.id;
                    document.getElementById('edit-username').value = user.username;
                    document.getElementById('edit-email').value = user.email;
                    document.getElementById('edit-first-name').value = user.first_name;
                    document.getElementById('edit-last-name').value = user.last_name;
                    document.getElementById('edit-phone-number').value = user.phone_number;
                    document.getElementById('edit-address-line1').value = user.address_line1;
                    document.getElementById('edit-address-line2').value = user.address_line2;
                    document.getElementById('edit-city').value = user.city;
                    document.getElementById('edit-country').value = user.country;
                    editModal.style.display = 'block';
                });
            });

            closeModal.addEventListener('click', () => {
                editModal.style.display = 'none';
            });

            window.addEventListener('click', (e) => {
                if (e.target === editModal) {
                    editModal.style.display = 'none';
                }
            };

            const editCarButtons = document.querySelectorAll('.edit-car-btn');
            const editCarModal = document.getElementById('edit-car-modal');

            editCarButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const car = JSON.parse(button.getAttribute('data-car'));
                    document.getElementById('edit-car-id').value = car.id;
                    document.getElementById('edit-car-make').value = car.car_make;
                    document.getElementById('edit-car-model').value = car.car_model;
                    document.getElementById('edit-car-year').value = car.car_year;
                    document.getElementById('edit-car-registration-number').value = car.car_registration_number;
                    editCarModal.style.display = 'block';
                });
            });

            closeModal.addEventListener('click', () => {
                editCarModal.style.display = 'none';
            });

            window.addEventListener('click', (e) => {
                if (e.target === editCarModal) {
                    editCarModal.style.display = 'none';
                }
            });

            const createUserBtn = document.getElementById('create-user-btn');
            const createUserModal = document.getElementById('create-user-modal');

            createUserBtn.addEventListener('click', () => {
                createUserModal.style.display = 'block';
            });

            closeModal.addEventListener('click', () => {
                createUserModal.style.display = 'none';
            });

            window.addEventListener('click', (e) => {
                createUserModal.style.display = 'none';
            });
        });
    </script>
</body>  
</html>