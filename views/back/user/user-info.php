<?php
session_start();

try {
    // Database connection
    $dsn = 'mysql:host=localhost;dbname=clyptorweb;charset=utf8mb4';
    $pdo = new PDO($dsn, 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../../front/login.php");
        exit();
    }

    // Handle form submission for updating user information
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['add_car']) && !isset($_POST['delete_car']) && !isset($_POST['edit_car']) && !isset($_POST['submit_verification'])) {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $phone_number = $_POST['phone_number'];
        $address_line1 = $_POST['address_line1'];
        $address_line2 = $_POST['address_line2'];
        $country = $_POST['country'];
        $city = $_POST['city'];

        $stmt = $pdo->prepare("UPDATE users SET username = :username, email = :email, first_name = :first_name, last_name = :last_name, phone_number = :phone_number, address_line1 = :address_line1, address_line2 = :address_line2, country = :country, city = :city WHERE id = :id");
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'phone_number' => $phone_number,
            'address_line1' => $address_line1,
            'address_line2' => $address_line2,
            'country' => $country,
            'city' => $city,
            'id' => $_SESSION['user_id']
        ]);

        // Refresh user data after update
        header("Location: user-info.php");
        exit();
    }

    // Handle account deletion
    if (isset($_POST['delete_account'])) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['user_id']]);

        // Destroy session and redirect to the login page
        session_destroy();
        header("Location: ../../front/login.php");
        exit();
    }

    // Handle form submission for adding a car
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_car'])) {
        $car_make = $_POST['car_make'];
        $car_model = $_POST['car_model'];
        $car_year = $_POST['car_year'];
        $car_registration_number = $_POST['car_registration_number'];

        $stmt = $pdo->prepare("INSERT INTO cars (user_id, car_make, car_model, car_year, car_registration_number, created_at) VALUES (:user_id, :car_make, :car_model, :car_year, :car_registration_number, NOW())");
        $stmt->execute([
            'user_id' => $_SESSION['user_id'],
            'car_make' => $car_make,
            'car_model' => $car_model,
            'car_year' => $car_year,
            'car_registration_number' => $car_registration_number
        ]);

        echo "<script>alert('Car details submitted successfully!');</script>";
        header("Location: user-info.php");
        exit();
    }

    // Handle form submission for deleting a car
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_car'])) {
        $car_id = $_POST['car_id'];

        $stmt = $pdo->prepare("DELETE FROM cars WHERE id = :id AND user_id = :user_id");
        $stmt->execute(['id' => $car_id, 'user_id' => $_SESSION['user_id']]);

        echo "<script>alert('Car deleted successfully!');</script>";
        header("Location: user-info.php");
        exit();
    }

    // Handle form submission for editing a car
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_car'])) {
        $car_id = $_POST['car_id'];
        $car_make = $_POST['car_make'];
        $car_model = $_POST['car_model'];
        $car_year = $_POST['car_year'];
        $car_registration_number = $_POST['car_registration_number'];

        $stmt = $pdo->prepare("UPDATE cars SET car_make = :car_make, car_model = :car_model, car_year = :car_year, car_registration_number = :car_registration_number WHERE id = :id AND user_id = :user_id");
        $stmt->execute([
            'car_make' => $car_make,
            'car_model' => $car_model,
            'car_year' => $car_year,
            'car_registration_number' => $car_registration_number,
            'id' => $car_id,
            'user_id' => $_SESSION['user_id']
        ]);

        echo "<script>alert('Car details updated successfully!');</script>";
        header("Location: user-info.php");
        exit();
    }

    // Handle form submission for uploading verification documents
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_verification'])) {
        $uploadDir = 'uploads/verification/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $idCardOrPassport = $_FILES['id_card_or_passport']['name'];
        $driverLicenseFront = $_FILES['driver_license_front']['name'];
        $driverLicenseBack = $_FILES['driver_license_back']['name'];

        $idCardOrPassportPath = $uploadDir . basename($idCardOrPassport);
        $driverLicenseFrontPath = $uploadDir . basename($driverLicenseFront);
        $driverLicenseBackPath = $uploadDir . basename($driverLicenseBack);

        if (move_uploaded_file($_FILES['id_card_or_passport']['tmp_name'], $idCardOrPassportPath) &&
            move_uploaded_file($_FILES['driver_license_front']['tmp_name'], $driverLicenseFrontPath) &&
            move_uploaded_file($_FILES['driver_license_back']['tmp_name'], $driverLicenseBackPath)) {
            
            $stmt = $pdo->prepare("UPDATE users SET 
                id_card_or_passport = :id_card_or_passport, 
                driver_license_front = :driver_license_front, 
                driver_license_back = :driver_license_back, 
                verification_status = 'Pending', 
                verification_submitted_at = NOW() 
                WHERE id = :id");
            $stmt->execute([
                'id_card_or_passport' => $idCardOrPassportPath,
                'driver_license_front' => $driverLicenseFrontPath,
                'driver_license_back' => $driverLicenseBackPath,
                'id' => $_SESSION['user_id']
            ]);

            echo "<script>alert('Verification documents submitted successfully!');</script>";
            header("Location: user-info.php");
            exit();
        } else {
            echo "<script>alert('Failed to upload files. Please try again.');</script>";
        }
    }

    // Fetch user information
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "User not found.";
        exit();
    }

    // Fetch user's cars
    $stmt = $pdo->prepare("SELECT * FROM cars WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $cars = $stmt->fetchAll();

    // Format the creation date
    $created_at = date("F j, Y, g:i a", strtotime($user['created_at']));

    // Fetch user's verification status
    $stmt = $pdo->prepare("SELECT verification_status FROM users WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $userVerification = $stmt->fetch();
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Information</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .popup {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }
    .popup-content {
        background: #fff;
        padding: 30px;
        border-radius: 10px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        position: relative;
        animation: fadeIn 0.3s ease-in-out;
    }
    .close-popup {
        position: absolute;
        top: 15px;
        right: 15px;
        cursor: pointer;
        font-size: 24px;
        color: #333;
        transition: color 0.3s;
    }
    .close-popup:hover {
        color: #ff0000;
    }
    .popup-content h2 {
        margin-bottom: 20px;
        font-size: 24px;
        color: #333;
        text-align: center;
    }
    .popup-content form label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
        color: #555;
    }
    .popup-content form input {
        width: 100%;
        padding: 10px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 16px;
    }
    .popup-content form button {
        width: 100%;
        padding: 10px;
        background-color: #007bff;
        color: #fff;
        border: none;
        border-radius: 5px;
        font-size: 16px;
        cursor: pointer;
        transition: background-color 0.3s;
    }
    .popup-content form button:hover {
        background-color: #0056b3;
    }
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: scale(0.9);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
    .popup:not(.hidden) {
        display: flex;
    }
    .btn-danger {
        background-color: #dc3545;
        color: #fff;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        transition: background-color 0.3s;
    }
    .btn-danger:hover {
        background-color: #c82333;
    }
    .styled-table {
        width: 100%;
        border-collapse: collapse;
    }
    .styled-table th, .styled-table td {
        padding: 10px;
        text-align: left;
        border: 1px solid #ddd;
    }
    .styled-table th {
        background-color: #f4f4f4;
    }
    .car-form {
        margin-top: 20px;
        padding: 20px;
        border: 1px solid #ddd;
        border-radius: 8px;
        background-color: #f9f9f9;
    }
    .car-form label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
    }
    .car-form input {
        width: 100%;
        padding: 10px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 16px;
    }
    .car-form button {
        background-color: #007bff;
        color: #fff;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        transition: background-color 0.3s;
    }
    .car-form button:hover {
        background-color: #0056b3;
    }
    .btn-warning {
        background-color: #ffc107;
        color: #fff;
        border: none;
        padding: 5px 10px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        transition: background-color 0.3s;
    }
    .btn-warning:hover {
        background-color: #e0a800;
    }
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.5);
    }
    .modal-content {
        background-color: #fff;
        margin: 10% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 50%;
        border-radius: 10px;
    }
    .close-modal {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    .close-modal:hover {
        color: black;
    }
    .verification-form {
        margin-top: 20px;
        padding: 20px;
        border: 1px solid #ddd;
        border-radius: 8px;
        background-color: #f9f9f9;
    }
    .verification-form label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
    }
    .verification-form input {
        width: 100%;
        padding: 10px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 16px;
    }
    .verification-form button {
        background-color: #007bff;
        color: #fff;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        transition: background-color 0.3s;
    }
    .verification-form button:hover {
        background-color: #0056b3;
    }
    .verification-status {
        margin-top: 20px;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
        background-color: #f9f9f9;
    }
    .verification-status p {
        margin: 0;
        font-size: 16px;
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
            <a href="user-info.php" class="active">
                <span class="fas fa-user"></span>
                <h3>User Information</h3>
            </a>
            <a href="history.php">
                <span class="fas fa-history"></span>
                <h3>History</h3>
            </a>
            <a href="chat.php">
                <span class="fas fa-comments"></span>
                <h3>Chat</h3>
            </a>
            <a href="../../front/index.php">
                <span class="fas fa-home"></span>
                <h3>Back Home</h3>
            </a>
            <a href="#" id="logout-button">
                <span class="fas fa-sign-out-alt"></span>
                <h3>Logout</h3>
            </a>
        </div>
    </aside>
    <main>
      <h1>User Information</h1>
      <div class="card user-info-card">
        <h3><i class="fas fa-user-circle"></i> Username: <span id="username-display"><?php echo htmlspecialchars($user['username']); ?></span></h3>
        <h3><i class="fas fa-envelope"></i> Email: <span id="email-display"><?php echo htmlspecialchars($user['email']); ?></span></h3>
        <h3><i class="fas fa-user"></i> Full Name: <span id="fullname-display"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span></h3>
        <h3><i class="fas fa-phone"></i> Phone: <span id="phone-display"><?php echo htmlspecialchars($user['phone_number']); ?></span></h3>
        <h3><i class="fas fa-map-marker-alt"></i> Address Line 1: <span id="address-line1-display"><?php echo htmlspecialchars($user['address_line1']); ?></span></h3>
        <h3><i class="fas fa-map-marker-alt"></i> Address Line 2: <span id="address-line2-display"><?php echo htmlspecialchars($user['address_line2']); ?></span></h3>
        <h3><i class="fas fa-flag"></i> Country: <span id="country-display"><?php echo htmlspecialchars($user['country']); ?></span></h3>
        <h3><i class="fas fa-city"></i> City: <span id="city-display"><?php echo htmlspecialchars($user['city']); ?></span></h3>
        <h3><i class="fas fa-calendar-alt"></i> Account Created On: <span id="created-at-display"><?php echo htmlspecialchars($created_at); ?></span></h3>
        <button class="btn btn-primary" id="edit-info-button">Edit Information</button>
        <form method="POST" style="margin-top: 20px;">
          <button type="submit" name="delete_account" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete your account? This action cannot be undone.')">Delete Account</button>
        </form>
      </div>

      <!-- Popup form for editing user information -->
      <div id="edit-popup" class="popup hidden">
          <div class="popup-content">
              <span class="close-popup" id="close-popup">&times;</span>
              <h2>Edit User Information</h2>
              <form id="edit-form" method="POST" action="">
                  <label for="username">Username:</label>
                  <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                  
                  <label for="email">Email:</label>
                  <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                  
                  <label for="first_name">First Name:</label>
                  <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                  
                  <label for="last_name">Last Name:</label>
                  <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                  
                  <label for="phone_number">Phone:</label>
                  <input type="text" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number']); ?>" required>
                  
                  <label for="address_line1">Address Line 1:</label>
                  <input type="text" id="address_line1" name="address_line1" value="<?php echo htmlspecialchars($user['address_line1']); ?>" required>
                  
                  <label for="address_line2">Address Line 2:</label>
                  <input type="text" id="address_line2" name="address_line2" value="<?php echo htmlspecialchars($user['address_line2']); ?>">
                  
                  <label for="country">Country:</label>
                  <input type="text" id="country" name="country" value="<?php echo htmlspecialchars($user['country']); ?>" required>
                  
                  <label for="city">City:</label>
                  <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($user['city']); ?>" required>
                  
                  <button type="submit" class="btn btn-primary">Save Changes</button>
              </form>
          </div>
      </div>

      <h2>Verification Status</h2>
      <div class="verification-status">
        <p>
          Your verification status: 
          <strong style="color: 
            <?= $userVerification['verification_status'] === 'Approved' ? 'green' : ($userVerification['verification_status'] === 'Rejected' ? 'red' : 'orange'); ?>;">
            <?= htmlspecialchars($userVerification['verification_status']) ?>
          </strong>
        </p>
        <?php if ($userVerification['verification_status'] === 'Rejected'): ?>
          <p style="color: red;">Your verification was rejected. Please resubmit your documents.</p>
        <?php elseif ($userVerification['verification_status'] === 'Pending'): ?>
          <p style="color: orange;">Your verification is under review. Please wait for approval.</p>
        <?php elseif ($userVerification['verification_status'] === 'Approved'): ?>
          <p style="color: green;">Your verification has been approved. You can now access all features.</p>
        <?php endif; ?>
      </div>

      <h2>Your Cars</h2>
      <table class="styled-table">
        <thead>
          <tr>
            <th>Car Make</th>
            <th>Car Model</th>
            <th>Car Year</th>
            <th>Registration Number</th>
            <th>Verification Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($cars as $car): ?>
            <tr>
              <td><?= htmlspecialchars($car['car_make']) ?></td>
              <td><?= htmlspecialchars($car['car_model']) ?></td>
              <td><?= htmlspecialchars($car['car_year']) ?></td>
              <td><?= htmlspecialchars($car['car_registration_number']) ?></td>
              <td><?= htmlspecialchars($car['car_verification_status']) ?></td>
              <td>
                <button class="btn btn-warning edit-car-btn" data-car='<?= json_encode($car) ?>'>Edit</button>
                <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this car?');">
                  <input type="hidden" name="car_id" value="<?= $car['id'] ?>">
                  <button type="submit" name="delete_car" class="btn btn-danger">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <h2>Add a Car</h2>
      <form method="POST" action="" class="car-form">
        <label for="car_make">Car Make:</label>
        <input type="text" id="car_make" name="car_make" required>

        <label for="car_model">Car Model:</label>
        <input type="text" id="car_model" name="car_model" required>

        <label for="car_year">Car Year:</label>
        <input type="number" id="car_year" name="car_year" required>

        <label for="car_registration_number">Registration Number:</label>
        <input type="text" id="car_registration_number" name="car_registration_number" required>

        <button type="submit" name="add_car" class="btn btn-primary">Submit Car Details</button>
      </form>

      <!-- Edit Car Popup -->
      <div id="edit-car-modal" class="modal">
        <div class="modal-content">
          <span class="close-modal">&times;</span>
          <h2>Edit Car</h2>
          <form method="POST" action="">
            <input type="hidden" name="car_id" id="edit-car-id">
            <label for="edit-car-make">Car Make:</label>
            <input type="text" id="edit-car-make" name="car_make" required>
            
            <label for="edit-car-model">Car Model:</label>
            <input type="text" id="edit-car-model" name="car_model" required>
            
            <label for="edit-car-year">Car Year:</label>
            <input type="number" id="edit-car-year" name="car_year" required>
            
            <label for="edit-car-registration-number">Registration Number:</label>
            <input type="text" id="edit-car-registration-number" name="car_registration_number" required>
            
            <button type="submit" name="edit_car" class="btn btn-primary">Save Changes</button>
          </form>
        </div>
      </div>

      <h2>Submit Verification Documents</h2>
      <form method="POST" action="" enctype="multipart/form-data" class="verification-form">
        <label for="id_card_or_passport">ID Card or Passport:</label>
        <input type="file" id="id_card_or_passport" name="id_card_or_passport" required>

        <label for="driver_license_front">Driver's License (Front):</label>
        <input type="file" id="driver_license_front" name="driver_license_front" required>

        <label for="driver_license_back">Driver's License (Back):</label>
        <input type="file" id="driver_license_back" name="driver_license_back" required>

        <button type="submit" name="submit_verification" class="btn btn-primary">Submit Documents</button>
      </form>
    </main>
  </div>
  <script>
    document.getElementById("logout-button").addEventListener("click", () => {
      localStorage.removeItem("isLoggedIn");
      localStorage.removeItem("username");
      window.location.href = "../../../index.php";
    });

    const editButton = document.getElementById("edit-info-button");
    const popup = document.getElementById("edit-popup");
    const closePopup = document.getElementById("close-popup");

    editButton.addEventListener("click", () => {
        popup.classList.remove("hidden");
    });

    closePopup.addEventListener("click", () => {
        popup.classList.add("hidden");
    });

    window.addEventListener("click", (event) => {
        if (event.target === popup) {
            popup.classList.add("hidden");
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
      const editButtons = document.querySelectorAll('.edit-car-btn');
      const editModal = document.getElementById('edit-car-modal');
      const closeModal = document.querySelector('.close-modal');

      editButtons.forEach(button => {
        button.addEventListener('click', () => {
          const car = JSON.parse(button.getAttribute('data-car'));
          document.getElementById('edit-car-id').value = car.id;
          document.getElementById('edit-car-make').value = car.car_make;
          document.getElementById('edit-car-model').value = car.car_model;
          document.getElementById('edit-car-year').value = car.car_year;
          document.getElementById('edit-car-registration-number').value = car.car_registration_number;
          editModal.style.display = 'block';
        });
      });

      closeModal.addEventListener('click', () => {
        editModal.style.display = 'none';
      });

      window.addEventListener('click', (e) => {
        if (e.target === editModal) {
          editModal.style.display = 'none';
        }
      });
    });
  </script>
</body>
</html>