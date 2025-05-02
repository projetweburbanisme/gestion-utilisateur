<?php
require_once __DIR__ . '/../../../config/database.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../front/login.php");
    exit;
}

$database = new Database();
$conn = $database->getConnection();

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_ride_id'])) {
    $ride_id = $_POST['delete_ride_id'];
    $delete_sql = "DELETE FROM rides WHERE ride_id = ? AND driver_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->execute([$ride_id, $_SESSION['user_id']]);
    header("Location: history.php");
    exit;
}

// Handle edit request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_ride_id'])) {
    $ride_id = $_POST['edit_ride_id'];
    $departure_location = $_POST['departure_location'];
    $destination_location = $_POST['destination_location'];
    $departure_datetime = $_POST['departure_datetime'];
    $available_seats = $_POST['available_seats'];
    $price_per_seat = $_POST['price_per_seat'];
    $additional_notes = $_POST['additional_notes'];

    $edit_sql = "UPDATE rides 
                 SET departure_location = ?, destination_location = ?, departure_datetime = ?, available_seats = ?, price_per_seat = ?, additional_notes = ? 
                 WHERE ride_id = ? AND driver_id = ?";
    $edit_stmt = $conn->prepare($edit_sql);
    $edit_stmt->execute([
        $departure_location, 
        $destination_location, 
        $departure_datetime, 
        $available_seats, 
        $price_per_seat, 
        $additional_notes, 
        $ride_id, 
        $_SESSION['user_id']
    ]);
    header("Location: history.php");
    exit;
}

$user_id = $_SESSION['user_id']; // Get the logged-in user's ID
$sql = "SELECT ride_id, departure_location, destination_location, departure_datetime, available_seats, price_per_seat, additional_notes, created_at 
        FROM rides 
        WHERE driver_id = ? 
        ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);
$rides = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>History</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    main.service-main {
      padding: 20px;
      background-color: #f9f9f9;
      border-radius: 8px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    main.service-main .service-hero {
      text-align: center;
      margin-bottom: 20px;
    }

    main.service-main .service-hero h1 {
      font-size: 2rem;
      color: #333;
    }

    main.service-main .service-hero p {
      font-size: 1rem;
      color: #666;
    }

    main.service-main .posts-section {
      margin-top: 20px;
    }

    main.service-main .posts-section h2 {
      font-size: 1.5rem;
      color: #333;
      margin-bottom: 15px;
    }

    main.service-main .posts-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
    }

    main.service-main .post-card {
      background-color: #fff;
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 15px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    main.service-main .post-card .post-header h3 {
      font-size: 1.25rem;
      color: #333;
    }

    main.service-main .post-card .post-header .post-category {
      font-size: 0.875rem;
      color: #888;
    }

    main.service-main .post-card .post-content p {
      font-size: 0.95rem;
      color: #555;
      margin: 5px 0;
    }

    main.service-main .post-card .post-footer {
      margin-top: 10px;
      display: flex;
      justify-content: space-between;
    }

    main.service-main .post-card .action-btn {
      text-decoration: none;
      padding: 8px 12px;
      border-radius: 4px;
      font-size: 0.875rem;
      color: #fff;
    }

    main.service-main .post-card .edit-btn {
      background-color: #007bff;
    }

    main.service-main .post-card .delete-btn {
      background-color: #dc3545;
    }

    main.service-main .post-card .action-btn:hover {
      opacity: 0.9;
    }

    .modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background-color: #fff;
      padding: 20px;
      border-radius: 8px;
      width: 400px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      position: relative;
    }

    .modal-content .close {
      position: absolute;
      top: 10px;
      right: 10px;
      font-size: 1.5rem;
      cursor: pointer;
    }

    .modal-content form label {
      display: block;
      margin: 10px 0 5px;
    }

    .modal-content form input,
    .modal-content form textarea {
      width: 100%;
      padding: 8px;
      margin-bottom: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
    }

    .modal-content form button {
      display: block;
      width: 100%;
      padding: 10px;
      background-color: #007bff;
      color: #fff;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }

    .modal-content form button:hover {
      background-color: #0056b3;
    }
  </style>
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
        <a href="history.php" class="active">
          <span class="fas fa-history"></span>
          <h3>History</h3>
        </a>
        <a href="chat.php">
          <span class="fas fa-comments"></span>
          <h3>Chat</h3>
        </a>
        <a href="#" id="logout-button">
          <span class="fas fa-sign-out-alt"></span>
          <h3>Logout</h3>
        </a>
      </div>
    </aside>
    <main class="service-main">
      <section class="service-hero">
        <div class="hero-content">
          <h1>History</h1>
          <p>View and manage your ride offers.</p>
        </div>
      </section>
      <section class="posts-section">
        <h2>Your Ride Offers</h2>
        <div id="posts-container" class="posts-container">
          <?php if (empty($rides)): ?>
            <p>No ride offers found.</p>
          <?php else: ?>
            <?php foreach ($rides as $ride): ?>
              <div class="post-card">
                <div class="post-header">
                  <h3><?php echo htmlspecialchars($ride['departure_location']); ?> → <?php echo htmlspecialchars($ride['destination_location']); ?></h3>
                  <span class="post-category">Created on <?php echo htmlspecialchars(date('Y-m-d', strtotime($ride['created_at']))); ?></span>
                </div>
                <div class="post-content">
                  <p><strong>Departure:</strong> <?php echo htmlspecialchars($ride['departure_datetime']); ?></p>
                  <p><strong>Seats Available:</strong> <?php echo htmlspecialchars($ride['available_seats']); ?></p>
                  <p><strong>Price per Seat:</strong> $<?php echo htmlspecialchars($ride['price_per_seat']); ?></p>
                  <p><strong>Notes:</strong> <?php echo htmlspecialchars($ride['additional_notes'] ?? 'N/A'); ?></p>
                </div>
                <div class="post-footer">
                  <button class="action-btn edit-btn" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($ride)); ?>)">Edit</button>
                  <form method="POST" action="history.php" style="display:inline;" onsubmit="return confirmDelete();">
                    <input type="hidden" name="delete_ride_id" value="<?php echo $ride['ride_id']; ?>">
                    <button type="submit" class="action-btn delete-btn">Delete</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>
    </main>
  </div>

  <!-- Edit Modal -->
  <div id="editModal" class="modal" style="display:none;">
    <div class="modal-content">
      <span class="close" onclick="closeEditModal()">&times;</span>
      <h2>Edit Ride</h2>
      <form method="POST" action="history.php">
        <input type="hidden" name="edit_ride_id" id="edit_ride_id">
        <label for="departure_location">Departure Location:</label>
        <input type="text" name="departure_location" id="departure_location" required>
        <label for="destination_location">Destination Location:</label>
        <input type="text" name="destination_location" id="destination_location" required>
        <label for="departure_datetime">Departure Date & Time:</label>
        <input type="datetime-local" name="departure_datetime" id="departure_datetime" required>
        <label for="available_seats">Available Seats:</label>
        <input type="number" name="available_seats" id="available_seats" required>
        <label for="price_per_seat">Price per Seat:</label>
        <input type="number" name="price_per_seat" id="price_per_seat" required>
        <label for="additional_notes">Additional Notes:</label>
        <textarea name="additional_notes" id="additional_notes"></textarea>
        <button type="submit" class="action-btn edit-btn">Save Changes</button>
      </form>
    </div>
  </div>

  <script>
    document.getElementById("logout-button").addEventListener("click", () => {
      localStorage.removeItem("isLoggedIn");
      localStorage.removeItem("username");
      window.location.href = "../index.php";
    });

    function openEditModal(ride) {
      document.getElementById('edit_ride_id').value = ride.ride_id;
      document.getElementById('departure_location').value = ride.departure_location;
      document.getElementById('destination_location').value = ride.destination_location;
      document.getElementById('departure_datetime').value = ride.departure_datetime.replace(' ', 'T');
      document.getElementById('available_seats').value = ride.available_seats;
      document.getElementById('price_per_seat').value = ride.price_per_seat;
      document.getElementById('additional_notes').value = ride.additional_notes || '';
      document.getElementById('editModal').style.display = 'block';
    }

    function closeEditModal() {
      document.getElementById('editModal').style.display = 'none';
    }

    function confirmDelete() {
      return confirm('Are you sure you want to delete this ride?');
    }
  </script>
</body>
</html>