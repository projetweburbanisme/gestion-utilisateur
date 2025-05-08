<?php
/**
 * Fonctions utilitaires pour l'application
 */

/**
 * Affiche un message de notification
 * @param string $message Le message à afficher
 * @param string $type Le type de message (success, danger, warning, info)
 * @return void
 */
function showMessage($message, $type = 'success') {
    echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
            {$message}
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
          </div>";
}

/**
 * Valide les données d'un utilisateur
 * @param array $data Les données à valider
 * @return array Liste des erreurs trouvées
 */
function validateUserData($data) {
    $errors = [];
    
    if (empty($data['username']) || strlen($data['username']) < 3) {
        $errors[] = "Le nom d'utilisateur doit faire au moins 3 caractères";
    }
    
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email invalide";
    }
    
    if (isset($data['password']) && strlen($data['password']) < 8) {
        $errors[] = "Le mot de passe doit faire au moins 8 caractères";
    }
    
    return $errors;
}

/**
 * Vérifie si l'utilisateur est connecté et est un admin
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin';
}

/**
 * Génère un jeton CSRF s'il n'existe pas déjà
 * @return string Le jeton CSRF
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie si le jeton CSRF est valide
 * @param string $token Le jeton à vérifier
 * @return bool
 */
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
