<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../front/login.php');
    exit();
}

try {
    $pdo = Database::getConnection();
    
    // Gestion de la recherche et du filtrage
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? 'all';
    $sort = $_GET['sort'] ?? 'created_at';
    $order = $_GET['order'] ?? 'DESC';
    
    // Construction de la requête SQL
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
    
    $sql .= " ORDER BY $sort $order";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
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
    <title>Gestion des Utilisateurs - Clyptor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.5/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #6c5ce7;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
        }
        .status-pending { background-color: #ffeaa7; color: #d35400; }
        .status-approved { background-color: #55efc4; color: #00b894; }
        .status-rejected { background-color: #fab1a0; color: #d63031; }
        .table-actions .btn { margin: 0 0.25rem; }
        .filters { margin-bottom: 2rem; }
    </style>
</head>
<body class="bg-light">
    <?php include '../../includes/admin_navbar.php'; ?>
    
    <div class="container-fluid py-4">
        <!-- Messages de notification -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type'] ?? 'info' ?> alert-dismissible fade show" role="alert">
                <?= $_SESSION['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
        <?php endif; ?>

        <!-- En-tête avec filtres -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Gestion des Utilisateurs</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="fas fa-plus"></i> Nouvel Utilisateur
            </button>
        </div>

        <!-- Filtres et recherche -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
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

        <!-- Tableau des utilisateurs -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Utilisateur</th>
                                <th>Contact</th>
                                <th>Statut</th>
                                <th>Inscription</th>
                                <th>Dernière connexion</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-3">
                                            <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($user['username']) ?></div>
                                            <small class="text-muted">
                                                <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($user['email']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($user['phone_number'] ?? 'Non renseigné') ?></small>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = match($user['verification_status']) {
                                        'Pending' => 'status-pending',
                                        'Approved' => 'status-approved',
                                        'Rejected' => 'status-rejected',
                                        default => ''
                                    };
                                    ?>
                                    <span class="status-badge <?= $statusClass ?>">
                                        <?= htmlspecialchars($user['verification_status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div><?= date('d/m/Y', strtotime($user['created_at'])) ?></div>
                                    <small class="text-muted"><?= date('H:i', strtotime($user['created_at'])) ?></small>
                                </td>
                                <td>
                                    <?php if ($user['last_login']): ?>
                                        <div><?= date('d/m/Y', strtotime($user['last_login'])) ?></div>
                                        <small class="text-muted"><?= date('H:i', strtotime($user['last_login'])) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">Jamais connecté</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <?php if ($user['verification_status'] === 'Pending'): ?>
                                        <form method="POST" class="d-inline verify-form">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        
                                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" 
                                                data-bs-target="#editUserModal" data-user='<?= json_encode($user) ?>'>
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <form method="POST" class="d-inline delete-form">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" name="delete_user" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de création d'utilisateur -->
    <div class="modal fade" id="createUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nouvel Utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="createUserForm">
                    <div class="modal-body">
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
                                <label class="form-label">Confirmer le mot de passe *</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Prénom</label>
                                <input type="text" class="form-control" name="first_name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nom</label>
                                <input type="text" class="form-control" name="last_name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" name="phone_number">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Adresse</label>
                                <input type="text" class="form-control" name="address_line1">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Créer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de modification d'utilisateur -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier l'utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editUserForm">
                    <input type="hidden" name="edit_user" value="1">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nom d'utilisateur *</label>
                                <input type="text" class="form-control" name="username" id="edit_username" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" id="edit_email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Prénom</label>
                                <input type="text" class="form-control" name="first_name" id="edit_first_name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nom</label>
                                <input type="text" class="form-control" name="last_name" id="edit_last_name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" name="phone_number" id="edit_phone_number">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Adresse</label>
                                <input type="text" class="form-control" name="address_line1" id="edit_address_line1">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.5/dist/sweetalert2.all.min.js"></script>
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

        // Gestion du modal d'édition
        const editUserModal = document.getElementById('editUserModal');
        if (editUserModal) {
            editUserModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const userData = JSON.parse(button.getAttribute('data-user'));
                
                // Remplir le formulaire avec les données de l'utilisateur
                this.querySelector('#edit_user_id').value = userData.id;
                this.querySelector('#edit_username').value = userData.username;
                this.querySelector('#edit_email').value = userData.email;
                this.querySelector('#edit_first_name').value = userData.first_name || '';
                this.querySelector('#edit_last_name').value = userData.last_name || '';
                this.querySelector('#edit_phone_number').value = userData.phone_number || '';
                this.querySelector('#edit_address_line1').value = userData.address_line1 || '';
                
                this.querySelector('.modal-title').textContent = `Modifier l'utilisateur ${userData.username}`;
            });
        }

        // Validation du formulaire de création
        const createUserForm = document.getElementById('createUserForm');
        if (createUserForm) {
            createUserForm.addEventListener('submit', function(e) {
                const password = this.querySelector('input[name="password"]').value;
                const confirmPassword = this.querySelector('input[name="confirm_password"]').value;
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Erreur',
                        text: 'Les mots de passe ne correspondent pas',
                        icon: 'error'
                    });
                }
            });
        }
    });
    </script>
</body>
</html>
