<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification de la session utilisateur
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'You must be logged in to send a message.']);
        exit;
    }

    // Récupération des données envoyées par le formulaire
    $senderId = $_SESSION['user_id'];
    $rideId = $_POST['ride_id'] ?? null;
    $recipientId = $_POST['recipient_id'] ?? null;
    $messageText = $_POST['message_text'] ?? null;

    // Vérification des champs nécessaires
    if (!$rideId || !$recipientId || !$messageText) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit;
    }

    // Sécurisation du texte du message
    $messageText = htmlspecialchars($messageText, ENT_QUOTES, 'UTF-8');

    // Inclusion de la configuration de la base de données
    require_once '../../../config/Database.php';
    $db = Database::getConnection();

    try {
        // Préparation et exécution de la requête d'insertion
        $stmt = $db->prepare("INSERT INTO ride_messages (ride_id, sender_id, recipient_id, message_text) VALUES (?, ?, ?, ?)");
        $stmt->execute([$rideId, $senderId, $recipientId, $messageText]);

        // Réponse en cas de succès
        echo json_encode(['success' => true, 'message' => 'Message sent successfully.']);
    } catch (Exception $e) {
        // En cas d'erreur, enregistrer dans les logs et retourner un message d'erreur
        error_log('Error in send_message.php: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An internal error occurred.']);
    }
}
?>
