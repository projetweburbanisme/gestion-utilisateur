<?php
// Affichage des erreurs (à retirer en production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Redirection si non connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../front/login.php");
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$user_id = $_SESSION['user_id'];

// Récupération des trajets de l'utilisateur
$sql = "SELECT ride_id, departure_datetime, available_seats, price_per_seat, additional_notes, created_at 
        FROM carpool_rides 
        WHERE driver_id = ? 
        ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$rides = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Ride History</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body { font-family: Arial, sans-serif; background-color: #f0f2f5; margin: 0; }
    .container { display: flex; min-height: 100vh; }
    aside { width: 250px; background: #111; color: #fff; padding: 20px; }
    .sidebar a { display: block; padding: 10px; color: #fff; text-decoration: none; margin-bottom: 10px; }
    .sidebar a.active, .sidebar a:hover { background: #333; }
    main { flex: 1; padding: 20px; background: #fff; }
    .post-card { background: #f9f9f9; padding: 15px; margin-bottom: 15px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .post-actions { margin-top: 10px; }
    .post-actions a { margin-right: 10px; text-decoration: none; color: #007BFF; }
    .post-actions a.delete-btn { color: red; }
  </style>
</head>
<body>
  <div class="container">
    <aside>
      <div class="logo">
        <h2>Cly<span style="color: red;">Ptor</span></h2>
      </div>
      <div class="sidebar">
        <a href="user-info.php"><i class="fas fa-user"></i> User Info</a>
        <a href="history.php" class="active"><i class="fas fa-history"></i> History</a>
        <a href="chat.php"><i class="fas fa-comments"></i> Chat</a>
        <a href="#" id="logout-button"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </div>
    </aside>
    <main>
      <h1>Your Ride History</h1>
      <?php if (empty($rides)): ?>
        <p>No ride offers found.</p>
      <?php else: ?>
        <?php foreach ($rides as $ride): ?>
          <div class="post-card">
            <h3>Ride ID: <?= htmlspecialchars($ride['ride_id']) ?></h3>
            <p><strong>Date:</strong> <?= htmlspecialchars($ride['departure_datetime']) ?></p>
            <p><strong>Seats:</strong> <?= htmlspecialchars($ride['available_seats']) ?></p>
            <p><strong>Price:</strong> $<?= htmlspecialchars($ride['price_per_seat']) ?></p>
            <p><strong>Notes:</strong> <?= htmlspecialchars($ride['additional_notes'] ?? 'N/A') ?></p>
            <div class="post-actions">
              <a href="edit-ride.php?id=<?= $ride['ride_id'] ?>">Edit</a>
              <a href="delete-ride.php?id=<?= $ride['ride_id'] ?>" class="delete-btn" onclick="return confirm('Are you sure?')">Delete</a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </main>
  </div>

  <script>
    document.getElementById("logout-button").addEventListener("click", () => {
      localStorage.clear();
      window.location.href = "../../front/login.php";
    });
  </script>
</body>
</html>
