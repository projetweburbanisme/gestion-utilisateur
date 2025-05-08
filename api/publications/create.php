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
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (empty($title) || empty($content)) {
        throw new Exception('Le titre et le contenu sont requis');
    }

    $stmt = $pdo->prepare("
        INSERT INTO publications (user_id, title, content, created_at) 
        VALUES (:user_id, :title, :content, NOW())
    ");
    
    $stmt->execute([
        'user_id' => $_SESSION['user_id'],
        'title' => $title,
        'content' => $content
    ]);

    // Ajouter à l'historique
    $stmt = $pdo->prepare("
        INSERT INTO user_history (user_id, action, created_at)
        VALUES (:user_id, :action, NOW())
    ");
    
    $stmt->execute([
        'user_id' => $_SESSION['user_id'],
        'action' => "A créé une nouvelle publication : " . $title
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
