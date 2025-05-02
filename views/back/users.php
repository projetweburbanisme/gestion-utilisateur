<?php
// Database connection
$dsn = 'mysql:host=localhost;dbname=clyptorweb;charset=utf8mb4';
$pdo = new PDO($dsn, 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// Handle search functionality
$searchQuery = '';
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    $searchQuery = $_GET['search'];
    $stmt = $pdo->prepare("SELECT id, username, email, first_name, last_name, phone_number, address_line1, address_line2, city, country, verification_status, created_at FROM users WHERE username LIKE :search OR email LIKE :search");
    $stmt->execute(['search' => '%' . $searchQuery . '%']);
    $users = $stmt->fetchAll();
} else {
    // Fetch all users if no search query
    $stmt = $pdo->prepare("SELECT id, username, email, first_name, last_name, phone_number, address_line1, address_line2, city, country, verification_status, created_at FROM users");
    $stmt->execute();
    $users = $stmt->fetchAll();
}

// Handle form submission for updating user information
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $user_id = $_POST['user_id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $phone_number = $_POST['phone_number'];
    $address_line1 = $_POST['address_line1'];
    $address_line2 = $_POST['address_line2'];
    $city = $_POST['city'];
    $country = $_POST['country'];

    $stmt = $pdo->prepare("UPDATE users SET username = :username, email = :email, first_name = :first_name, last_name = :last_name, phone_number = :phone_number, address_line1 = :address_line1, address_line2 = :address_line2, city = :city, country = :country WHERE id = :id");
    $stmt->execute([
        'username' => $username,
        'email' => $email,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'phone_number' => $phone_number,
        'address_line1' => $address_line1,
        'address_line2' => $address_line2,
        'city' => $city,
        'country' => $country,
        'id' => $user_id
    ]);

    // Refresh the page after updating
    header("Location: users.php");
    exit();
}

// Handle form submission for deleting a user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute(['id' => $user_id]);

    // Refresh the page after deletion
    header("Location: users.php");
    exit();
}

// Handle form submission for deleting a car
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_car'])) {
    $car_id = $_POST['car_id'];

    $stmt = $pdo->prepare("DELETE FROM cars WHERE id = :id");
    $stmt->execute(['id' => $car_id]);

    echo "<script>alert('Car deleted successfully!');</script>";
    header("Location: users.php");
    exit();
}

// Handle form submission for editing a car
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_car'])) {
    $car_id = $_POST['car_id'];
    $car_make = $_POST['car_make'];
    $car_model = $_POST['car_model'];
    $car_year = $_POST['car_year'];
    $car_registration_number = $_POST['car_registration_number'];

    $stmt = $pdo->prepare("UPDATE cars SET car_make = :car_make, car_model = :car_model, car_year = :car_year, car_registration_number = :car_registration_number WHERE id = :id");
    $stmt->execute([
        'car_make' => $car_make,
        'car_model' => $car_model,
        'car_year' => $car_year,
        'car_registration_number' => $car_registration_number,
        'id' => $car_id
    ]);

    echo "<script>alert('Car details updated successfully!');</script>";
    header("Location: users.php");
    exit();
}

// Handle car verification approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_car'])) {
    $car_id = $_POST['car_id'];

    $stmt = $pdo->prepare("UPDATE cars SET car_verification_status = 'Approved', car_verified_at = NOW() WHERE id = :id");
    $stmt->execute(['id' => $car_id]);

    echo "<script>alert('Car verification approved successfully!');</script>";
    header("Location: users.php");
    exit();
}

// Handle user verification approval or rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $user_id = $_POST['user_id'];
    $action = $_POST['action']; // 'approve' or 'reject'

    $verificationStatus = $action === 'approve' ? 'Approved' : 'Rejected';

    $stmt = $pdo->prepare("UPDATE users SET verification_status = :verification_status, verified_at = NOW() WHERE id = :id");
    $stmt->execute([
        'verification_status' => $verificationStatus,
        'id' => $user_id
    ]);

    echo "<script>alert('User verification $verificationStatus successfully!');</script>";
    header("Location: users.php");
    exit();
}

// Handle form submission for creating a new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $address_line1 = $_POST['address_line1'];
    $address_line2 = $_POST['address_line2'];
    $city = $_POST['city'];
    $state_province = $_POST['state_province'];
    $postal_code = $_POST['postal_code'];
    $country = $_POST['country'];
    $phone_number = $_POST['phone_number'];

    // Hash the password
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    // Insert the new user into the database
    $stmt = $pdo->prepare("INSERT INTO users (
        username, email, password_hash, first_name, last_name, address_line1, address_line2, city, state_province, postal_code, country, phone_number, created_at
    ) VALUES (
        :username, :email, :password_hash, :first_name, :last_name, :address_line1, :address_line2, :city, :state_province, :postal_code, :country, :phone_number, NOW()
    )");

    $stmt->execute([
        'username' => $username,
        'email' => $email,
        'password_hash' => $passwordHash,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'address_line1' => $address_line1,
        'address_line2' => $address_line2,
        'city' => $city,
        'state_province' => $state_province,
        'postal_code' => $postal_code,
        'country' => $country,
        'phone_number' => $phone_number,
    ]);

    echo "<script>alert('User created successfully!');</script>";
    header("Location: users.php");
    exit();
}

// Fetch all cars
$stmt = $pdo->prepare("SELECT cars.*, users.username FROM cars INNER JOIN users ON cars.user_id = users.id");
$stmt->execute();
$cars = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Sharp" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <title>Users</title>
    <style>
        /* Add custom styles */
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
        .actions button {
            margin-right: 5px;
        }
        .form-container {
            margin: 20px 0;
        }
        .form-container input, .form-container button {
            padding: 10px;
            margin-right: 10px;
        }

        /* Modal styles */
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
        .close-modal:hover,
        .close-modal:focus {
            color: black;
            text-decoration: none;
        }

        /* Add styles for the Create User modal */
        .modal-content form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .modal-content form input, .modal-content form button {
            padding: 10px;
            font-size: 16px;
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
        .btn-danger {
            background-color: #dc3545;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .btn-primary {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .btn-success {
            background-color: #28a745;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .btn-success:hover {
            background-color: #218838;
        }
    </style>
</head>

<body>

    <div class="container">
        <!-- Sidebar Section -->
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
                <a href="admin.php">
                    <span class="material-icons-sharp">
                        dashboard
                    </span>
                    <h3>Dashboard</h3>
                </a>
                <a href="users.php" class="active">
                    <span class="material-icons-sharp">
                        person_outline
                    </span>
                    <h3>Users</h3>
                </a>
                <div class="submenu">
                    <a href="admincar.php">car-rent</a>
                    <a href="history2.php">covoiturage</a>
                    <a href="history3.php">home-rent</a>
                    <a href="history4.php">deliver-package</a>
                    <a href="history4.php">deliver-package</a>
                </div>
                <a href="ticket.php">
                    <span class="material-icons-sharp">
                        mail_outline
                    </span>
                    <h3>Tickets</h3>
                    <span class="message-count">27</span>
                </a>
                <a href="logout.php">
                    <span class="material-icons-sharp">
                        logout
                    </span>
                    <h3>Logout</h3>
                </a>
            </div>
        </aside>
        <!-- End of Sidebar Section -->

        <!-- Main Content -->
        <main>
            <h1>All Users</h1>

            <!-- Search Bar -->
            <form method="GET" action="users.php" style="margin-bottom: 20px;">
                <input type="text" name="search" placeholder="Search by username or email" value="<?= htmlspecialchars($searchQuery) ?>" style="padding: 10px; width: 300px; border: 1px solid #ccc; border-radius: 5px;">
                <button type="submit" class="btn btn-primary" style="padding: 10px 20px; border: none; background-color: #007bff; color: #fff; border-radius: 5px; cursor: pointer;">Search</button>
            </form>

            <!-- Create User Button -->
            <button id="create-user-btn" class="btn btn-primary" style="margin-bottom: 20px;">Create User</button>

            <!-- Create User Modal -->
            <div id="create-user-modal" class="modal">
                <div class="modal-content">
                    <span class="close-modal">&times;</span>
                    <h2>Create User</h2>
                    <form method="POST" action="">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" required>

                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required>

                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>

                        <label for="first_name">First Name:</label>
                        <input type="text" id="first_name" name="first_name">

                        <label for="last_name">Last Name:</label>
                        <input type="text" id="last_name" name="last_name">

                        <label for="address_line1">Address Line 1:</label>
                        <input type="text" id="address_line1" name="address_line1">

                        <label for="address_line2">Address Line 2:</label>
                        <input type="text" id="address_line2" name="address_line2">

                        <label for="city">City:</label>
                        <input type="text" id="city" name="city">

                        <label for="state_province">State/Province:</label>
                        <input type="text" id="state_province" name="state_province">

                        <label for="postal_code">Postal Code:</label>
                        <input type="text" id="postal_code" name="postal_code">

                        <label for="country">Country:</label>
                        <input type="text" id="country" name="country">

                        <label for="phone_number">Phone Number:</label>
                        <input type="text" id="phone_number" name="phone_number">

                        <button type="submit" name="create_user" class="btn btn-primary">Create User</button>
                    </form>
                </div>
            </div>

            <table class="styled-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Full Name</th>
                        <th>Phone</th>
                        <th>Address Line 1</th>
                        <th>Address Line 2</th>
                        <th>City</th>
                        <th>Country</th>
                        <th>Verification Status</th>
                        <th>Account Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                            <td><?= htmlspecialchars($user['phone_number']) ?></td>
                            <td><?= htmlspecialchars($user['address_line1']) ?></td>
                            <td><?= htmlspecialchars($user['address_line2']) ?></td>
                            <td><?= htmlspecialchars($user['city']) ?></td>
                            <td><?= htmlspecialchars($user['country']) ?></td>
                            <td><?= htmlspecialchars($user['verification_status']) ?></td>
                            <td><?= htmlspecialchars(date("F j, Y, g:i a", strtotime($user['created_at']))) ?></td>
                            <td>
                                <?php if ($user['verification_status'] === 'Pending'): ?>
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" name="action" value="approve" class="btn btn-success">Approve</button>
                                        <button type="submit" name="action" value="reject" class="btn btn-danger">Reject</button>
                                    </form>
                                <?php endif; ?>
                                <button class="btn btn-warning edit-btn" data-user='<?= json_encode($user) ?>'>Edit</button>
                                <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" name="delete_user" class="btn btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Edit User Popup -->
            <div id="edit-user-modal" class="modal">
                <div class="modal-content">
                    <span class="close-modal">&times;</span>
                    <h2>Edit User</h2>
                    <form id="edit-user-form" method="POST" action="">
                        <input type="hidden" name="user_id" id="edit-user-id">
                        <label for="edit-username">Username:</label>
                        <input type="text" id="edit-username" name="username" required>
                        
                        <label for="edit-email">Email:</label>
                        <input type="email" id="edit-email" name="email" required>
                        
                        <label for="edit-first-name">First Name:</label>
                        <input type="text" id="edit-first-name" name="first_name" required>
                        
                        <label for="edit-last-name">Last Name:</label>
                        <input type="text" id="edit-last-name" name="last_name" required>
                        
                        <label for="edit-phone-number">Phone:</label>
                        <input type="text" id="edit-phone-number" name="phone_number" required>
                        
                        <label for="edit-address-line1">Address Line 1:</label>
                        <input type="text" id="edit-address-line1" name="address_line1" required>
                        
                        <label for="edit-address-line2">Address Line 2:</label>
                        <input type="text" id="edit-address-line2" name="address_line2">
                        
                        <label for="edit-city">City:</label>
                        <input type="text" id="edit-city" name="city" required>
                        
                        <label for="edit-country">Country:</label>
                        <input type="text" id="edit-country" name="country" required>
                        
                        <button type="submit" name="edit_user" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>

            <h2>All Cars</h2>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Username</th>
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
                            <td><?= htmlspecialchars($car['username']) ?></td>
                            <td><?= htmlspecialchars($car['car_make']) ?></td>
                            <td><?= htmlspecialchars($car['car_model']) ?></td>
                            <td><?= htmlspecialchars($car['car_year']) ?></td>
                            <td><?= htmlspecialchars($car['car_registration_number']) ?></td>
                            <td><?= htmlspecialchars($car['car_verification_status']) ?></td>
                            <td>
                                <?php if ($car['car_verification_status'] === 'Pending'): ?>
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="car_id" value="<?= $car['id'] ?>">
                                        <button type="submit" name="approve_car" class="btn btn-success">Approve</button>
                                    </form>
                                <?php endif; ?>
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
        </main>
        <!-- End of Main Content -->

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const editButtons = document.querySelectorAll('.edit-btn');
            const editModal = document.getElementById('edit-user-modal');
            const closeModal = document.querySelector('.close-modal');
            const editForm = document.getElementById('edit-user-form');

            editButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const user = JSON.parse(button.getAttribute('data-user'));
                    document.getElementById('edit-user-id').value = user.id;
                    document.getElementById('edit-username').value = user.username;
                    document.getElementById('edit-email').value = user.email;
                    document.getElementById('edit-first-name').value = user.first_name;
                    document.getElementById('edit-last-name').value = user.last_name;
                    document.getElementById('edit-phone-number').value = user.phone_number;
                    document.getElementById('edit-address-line1').value = user.address_line1;
                    document.getElementById('edit-address-line2').value = user.address_line2;
                    document.getElementById('edit-city').value = user.city;
                    document.getElementById('edit-country').value = user.country;
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

            const editCarButtons = document.querySelectorAll('.edit-car-btn');
            const editCarModal = document.getElementById('edit-car-modal');

            editCarButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const car = JSON.parse(button.getAttribute('data-car'));
                    document.getElementById('edit-car-id').value = car.id;
                    document.getElementById('edit-car-make').value = car.car_make;
                    document.getElementById('edit-car-model').value = car.car_model;
                    document.getElementById('edit-car-year').value = car.car_year;
                    document.getElementById('edit-car-registration-number').value = car.car_registration_number;
                    editCarModal.style.display = 'block';
                });
            });

            closeModal.addEventListener('click', () => {
                editCarModal.style.display = 'none';
            });

            window.addEventListener('click', (e) => {
                if (e.target === editCarModal) {
                    editCarModal.style.display = 'none';
                }
            });

            const createUserBtn = document.getElementById('create-user-btn');
            const createUserModal = document.getElementById('create-user-modal');

            createUserBtn.addEventListener('click', () => {
                createUserModal.style.display = 'block';
            });

            closeModal.addEventListener('click', () => {
                createUserModal.style.display = 'none';
            });

            window.addEventListener('click', (e) => {
                if (e.target === createUserModal) {
                    createUserModal.style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>