<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.html");
    exit;
}

// Retrieve ride_id and recipient_id from query parameters or session
if (isset($_GET['ride_id']) && isset($_GET['recipient_id'])) {
    $_SESSION['ride_id'] = $_GET['ride_id'];
    $_SESSION['recipient_id'] = $_GET['recipient_id'];
}

$rideId = $_SESSION['ride_id'] ?? null;
$recipientId = $_SESSION['recipient_id'] ?? null;
$senderId = $_SESSION['user_id'];

if (!$rideId || !$recipientId) {
    // Redirect to covoiturage.php if ride_id or recipient_id is missing
    header("Location: ../../front/covoiturage.php");
    exit;
}

require_once '../../../config/Database.php';

$db = Database::getConnection();

// Fetch existing messages between the two users for the specific ride
$stmt = $db->prepare("
    SELECT * 
    FROM ride_messages 
    WHERE ride_id = ? 
    AND ((sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?)) 
    ORDER BY sent_at ASC
");
$stmt->execute([$rideId, $senderId, $recipientId, $recipientId, $senderId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chat</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div class="container">
    <aside>
        <div class="toggle">
            <div class="logo">
                <img src="images/logo.png">
                <h2>Cly<span class="danger">Ptor</span></h2>
            </div>
            <div class="close" id="close-btn">
                <span class="material-icons-sharp">
                    close
                </span>
            </div>
        </div>
      <div class="sidebar">
        <a href="user-info.php">
          <span class="fas fa-user"></span>
          <h3>User Information</h3>
        </a>
        <a href="history.php">
          <span class="fas fa-history"></span>
          <h3>History</h3>
        </a>
        <a href="chat.php" class="active">
          <span class="fas fa-comments"></span>
          <h3>Chat</h3>
        </a>
        <a href="#" id="logout-button">
          <span class="fas fa-sign-out-alt"></span>
          <h3>Logout</h3>
        </a>
      </div>
    </aside>
    <main>
      <h1>Chat</h1>
      <div class="chat-box">
        <div class="messages">
            <?php foreach ($messages as $message): ?>
                <div class="message <?= $message['sender_id'] == $senderId ? 'sent' : 'received' ?>">
                    <p><?= htmlspecialchars($message['message_text']) ?></p>
                    <small><?= date('Y-m-d H:i', strtotime($message['sent_at'])) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
        <form id="chat-form">
            <input type="hidden" name="ride_id" value="<?= $rideId ?>">
            <input type="hidden" name="recipient_id" value="<?= $recipientId ?>">
            <input type="text" placeholder="Type a message..." required />
            <button type="submit" class="btn btn-primary">Send</button>
        </form>
      </div>
    </main>
  </div>
  <script>
    document.getElementById("logout-button").addEventListener("click", () => {
        localStorage.removeItem("isLoggedIn");
        localStorage.removeItem("username");
        window.location.href = "../index.html";
    });

    const chatForm = document.getElementById("chat-form");
    const messages = document.querySelector(".messages");

    chatForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        const input = chatForm.querySelector("input[type='text']");
        const message = input.value.trim();

        if (message) {
            const formData = new FormData(chatForm);
            formData.append("message_text", message);

            try {
                const response = await fetch("send_message.php", {
                    method: "POST",
                    body: formData,
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();
                if (result.success) {
                    const sentMessage = document.createElement("div");
                    sentMessage.classList.add("message", "sent");
                    sentMessage.innerHTML = `<p>${message}</p><small>${new Date().toLocaleString()}</small>`;
                    messages.appendChild(sentMessage);
                    input.value = "";
                    messages.scrollTop = messages.scrollHeight;
                } else {
                    alert("Error sending message: " + result.message);
                }
            } catch (error) {
                console.error("Error sending message:", error);
                alert("An error occurred while sending the message. Please try again.");
            }
        }
    });
  </script>
</body>
</html>