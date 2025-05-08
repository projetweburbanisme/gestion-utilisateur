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
    $data = json_decode(file_get_contents('php://input'), true);
    $message = trim($data['message'] ?? '');

    if (empty($message)) {
        throw new Exception('Le message ne peut pas être vide');
    }

    $stmt = $pdo->prepare("
        INSERT INTO chat_messages (user_id, message, created_at) 
        VALUES (:user_id, :message, NOW())
    ");
    
    $stmt->execute([
        'user_id' => $_SESSION['user_id'],
        'message' => $message
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
