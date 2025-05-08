<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['facebook_id'], $data['name'], $data['email'])) {
    echo json_encode(['success' => false, 'message' => 'Données invalides.']);
    exit();
}

try {
    $pdo = Database::getConnection();

    // Vérifier si l'utilisateur existe déjà
    $stmt = $pdo->prepare("SELECT id FROM users WHERE facebook_id = ? OR email = ?");
    $stmt->execute([$data['facebook_id'], $data['email']]);
    $user = $stmt->fetch();

    if (!$user) {
        // Créer un nouvel utilisateur
        $stmt = $pdo->prepare("INSERT INTO users (facebook_id, username, email, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$data['facebook_id'], $data['name'], $data['email']]);
        $userId = $pdo->lastInsertId();
    } else {
        $userId = $user['id'];
    }

    // Démarrer une session pour l'utilisateur
    session_start();
    $_SESSION['user_id'] = $userId;

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}<?php

