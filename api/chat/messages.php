<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

try {
    // Récupérer les 50 derniers messages
    $stmt = $pdo->query("
        SELECT c.*, u.username 
        FROM chat_messages c 
        JOIN users u ON c.user_id = u.id 
        ORDER BY c.created_at DESC 
        LIMIT 50
    ");
    $messages = $stmt->fetchAll();
    
    // Inverser l'ordre pour afficher les plus anciens en premier
    $messages = array_reverse($messages);
    
    echo json_encode($messages);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}
