<?php
require_once '../../controllers/CovoiturageController.php';

session_start();

// Redirect to login if user is not logged in
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'You must be logged in to perform this action.']);
        exit;
    }

    $controller = new CovoiturageController();
    $action = $_GET['action'] ?? 'create';

    if ($action === 'create') {
        try {
            // Log the incoming POST data for debugging
            error_log('POST Data: ' . print_r($_POST, true));
            error_log('FILES Data: ' . print_r($_FILES, true));

            // Validate required fields
            $requiredFields = ['departure_location', 'destination_location', 'departure_datetime', 'available_seats', 'price_per_seat', 'car_id'];
            foreach ($requiredFields as $field) {
                if (empty($_POST[$field])) {
                    echo json_encode(['success' => false, 'message' => "The field '$field' is required."]);
                    exit;
                }
            }

            // Ensure "smoking_allowed" and "pets_allowed" are set to 0 if not checked
            $_POST['smoking_allowed'] = isset($_POST['smoking_allowed']) ? 1 : 0;
            $_POST['pets_allowed'] = isset($_POST['pets_allowed']) ? 1 : 0;

            // Call the controller to create the ride offer
            $result = $controller->createRideOffer($_POST, $_FILES);
            echo json_encode($result);
        } catch (Exception $e) {
            error_log('Error in createRideOffer: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'An internal error occurred. Please try again later.']);
        }
        exit;
    } elseif ($action === 'delete') {
        try {
            $rideId = $_GET['ride_id'] ?? null;
            if (!$rideId) {
                echo json_encode(['success' => false, 'message' => 'Ride ID is required.']);
                exit;
            }

            $result = $controller->deleteRideOffer($rideId);
            echo json_encode($result);
        } catch (Exception $e) {
            error_log('Error in deleteRideOffer: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'An internal error occurred. Please try again later.']);
        }
        exit;
    } elseif ($action === 'edit') {
        try {
            $rideId = $_GET['ride_id'] ?? null;
            if (!$rideId) {
                echo json_encode(['success' => false, 'message' => 'Ride ID is required.']);
                exit;
            }

            // Validate required fields
            $requiredFields = ['departure_location', 'destination_location', 'departure_datetime', 'available_seats', 'price_per_seat', 'car_id'];
            foreach ($requiredFields as $field) {
                if (empty($_POST[$field])) {
                    echo json_encode(['success' => false, 'message' => "The field '$field' is required."]);
                    exit;
                }
            }

            // Ensure "smoking_allowed" and "pets_allowed" are set to 0 if not checked
            $_POST['smoking_allowed'] = isset($_POST['smoking_allowed']) ? 1 : 0;
            $_POST['pets_allowed'] = isset($_POST['pets_allowed']) ? 1 : 0;

            $result = $controller->editRideOffer($rideId, $_POST);
            echo json_encode($result);
        } catch (Exception $e) {
            error_log('Error in editRideOffer: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'An internal error occurred. Please try again later.']);
        }
        exit;
    } elseif ($action === 'fetch') {
        try {
            $rideId = $_GET['ride_id'] ?? null;
            if (!$rideId) {
                echo json_encode(['success' => false, 'message' => 'Ride ID is required.']);
                exit;
            }

            $result = $controller->fetchRideOffer($rideId);
            echo json_encode($result);
        } catch (Exception $e) {
            error_log('Error in fetchRideOffer: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'An internal error occurred. Please try again later.']);
        }
        exit;
    } elseif ($action === 'reserve') {
        try {
            $rideId = $_POST['ride_id'] ?? null;
            $seatsBooked = $_POST['seats_booked'] ?? null;
            $pickupLocation = $_POST['pickup_location'] ?? null;
            $dropoffLocation = $_POST['dropoff_location'] ?? null;
            $specialRequests = $_POST['special_requests'] ?? null;

            // Validate required fields
            if (!$rideId || !$seatsBooked || !$pickupLocation || !$dropoffLocation) {
                echo json_encode(['success' => false, 'message' => 'All fields are required.']);
                exit;
            }

            // Log reservation data for debugging
            error_log('Reservation Data: ' . print_r($_POST, true));

            // Insert reservation into the database
            require_once '../../config/Database.php';
            $db = Database::getConnection();
            $stmt = $db->prepare("INSERT INTO reservations (ride_id, user_id, seats_booked, pickup_location, dropoff_location, special_requests) 
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $rideId,
                $_SESSION['user_id'],
                $seatsBooked,
                $pickupLocation,
                $dropoffLocation,
                $specialRequests
            ]);

            echo json_encode(['success' => true, 'message' => 'Reservation successful.']);
        } catch (Exception $e) {
            error_log('Error in reservation: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'An internal error occurred. Please try again later.']);
        }
        exit;
    }
}

// Redirect back to this page after login
if (isset($_SESSION['redirect_after_login']) && $_SESSION['redirect_after_login'] === $_SERVER['REQUEST_URI']) {
    unset($_SESSION['redirect_after_login']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clyptor - Covoiturage</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/hf.css">
    <link rel="stylesheet" href="css/animations.css">
    <link rel="stylesheet" href="https://unpkg.com/@splinetool/viewer@1.9.82/build/spline-viewer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .posts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .post {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .post:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .post h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #333;
        }

        .post p {
            margin: 5px 0;
            color: #555;
        }

        .post button {
            margin-top: 10px;
            padding: 8px 12px;
            font-size: 0.9rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .post .edit-btn {
            background-color: #007bff;
            color: #fff;
        }

        .post .edit-btn:hover {
            background-color: #0056b3;
        }

        .post .delete-btn {
            background-color: #dc3545;
            color: #fff;
            margin-left: 10px;
        }

        .post .delete-btn:hover {
            background-color: #a71d2a;
        }

        .post .reserve-btn {
            background-color: #28a745;
            color: #fff;
            margin-left: 10px;
        }

        .post .reserve-btn:hover {
            background-color: #218838;
        }

        .post .contact-btn {
            background-color: #ffc107;
            color: #fff;
            margin-left: 10px;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 4px;
            display: inline-block;
            text-align: center;
        }

        .post .contact-btn:hover {
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
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
<header class="header">
    <div class="logo-container">
        <a href="index.php" class="logo">
            <span class="logo-text">Clyptor</span>
        </a>
        <div class="logo-3d"></div>
    </div>
    
    <nav class="main-nav">
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="covoiturage.php">Carpooling</a></li>
            <li><a href="home-rent.php">Home Rent</a></li>
            <li><a href="car-rent.php">Car Rent</a></li>
            <li><a href="deliver-package.php">Deliver Package</a></li>
            <li><a href="contact.php">Contact</a></li>
        </ul>
    </nav>
    
    <div class="auth-buttons">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="../back/user/user-info.php" class="btn btn-primary">Dashboard</a>
            <a href="logout.php" class="btn btn-outline">Logout</a>
        <?php else: ?>
            <a href="login.php" class="btn btn-outline">Login</a>
            <a href="register.php" class="btn btn-primary">Register</a>
        <?php endif; ?>
    </div>
    
    <button class="mobile-menu-toggle">
        <i class="fas fa-bars"></i>
    </button>
</header>

<main class="service-main">
    <section class="service-hero">
        <div class="hero-content">
            <h1>Covoiturage</h1>
            <p>Share rides and save money on your daily commute or long trips.</p>
            <button id="create-post-btn" class="cta-button">Create Ride Offer</button>
        </div>
        <div class="hero-image">
            <div class="car-animation"></div>
        </div>
    </section>

    <section class="posts-section">
        <h2>Available Rides</h2>
        <div class="filter-controls">
            <select id="filter-category">
                <option value="all">All Categories</option>
                <option value="daily">Daily Commute</option>
                <option value="weekend">Weekend Trip</option>
                <option value="long-distance">Long Distance</option>
                <option value="airport">Airport Transfer</option>
            </select>
            <select id="filter-date">
                <option value="all">Any Date</option>
                <option value="today">Today</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
            </select>
        </div>
        <div id="posts-container" class="posts-container">
            <?php
            require_once '../../config/Database.php';
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT r.*, c.car_make, c.car_model, c.car_year, u.first_name, u.last_name 
                                  FROM rides r 
                                  JOIN cars c ON r.car_id = c.id 
                                  JOIN users u ON r.driver_id = u.id 
                                  WHERE r.status = 'active' 
                                  ORDER BY r.departure_datetime ASC");
            $stmt->execute();
            $rides = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rides as $ride) {
                echo "<div class='post'>
                        <h3>{$ride['departure_location']} to {$ride['destination_location']}</h3>
                        <p><strong>Driver:</strong> {$ride['first_name']} {$ride['last_name']}</p>
                        <p><strong>Departure:</strong> " . date('Y-m-d H:i', strtotime($ride['departure_datetime'])) . "</p>
                        <p><strong>Return:</strong> " . ($ride['return_datetime'] ? date('Y-m-d H:i', strtotime($ride['return_datetime'])) : 'No return time') . "</p>
                        <p><strong>Car:</strong> {$ride['car_make']} {$ride['car_model']} ({$ride['car_year']})</p>
                        <p><strong>Seats Available:</strong> {$ride['available_seats']}</p>
                        <p><strong>Price per Seat:</strong> \${$ride['price_per_seat']}</p>
                        <p><strong>Notes:</strong> " . ($ride['additional_notes'] ?: 'No additional notes') . "</p>
                        <button class='reserve-btn' data-id='{$ride['ride_id']}'>Reserve</button>
                        <a href='../../views/back/user/chat.php?ride_id={$ride['ride_id']}&recipient_id={$ride['driver_id']}' class='contact-btn'>Contact</a>
                      </div>";
            }
            ?>
        </div>
    </section>

    <section class="post-form-container" id="post-form-container" style="display:none;">
        <h2 id="form-title">Create a Ride Offer</h2>
        <form id="post-form" class="post-form" method="POST" action="?action=create" enctype="multipart/form-data">
            <div class="form-group">
                <label for="car-id">Select Car</label>
                <select id="car-id" name="car_id" required>
                    <option value="">-- Select a Car --</option>
                    <?php
                    // Fetch the user's cars from the database
                    require_once '../../config/Database.php';
                    $db = Database::getConnection();
                    $stmt = $db->prepare("SELECT id, car_make, car_model, car_year FROM cars WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($cars as $car) {
                        echo "<option value='{$car['id']}'>{$car['car_make']} {$car['car_model']} ({$car['car_year']})</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="departure-location">Departure Location</label>
                <input type="text" id="departure-location" name="departure_location" placeholder="Starting location" required>
            </div>
            <div class="form-group">
                <label for="destination-location">Destination Location</label>
                <input type="text" id="destination-location" name="destination_location" placeholder="Destination" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="departure-datetime">Departure Date & Time</label>
                    <input type="datetime-local" id="departure-datetime" name="departure_datetime" required>
                </div>
                <div class="form-group">
                    <label for="return-datetime">Return Date & Time</label>
                    <input type="datetime-local" id="return-datetime" name="return_datetime">
                </div>
            </div>
            <div class="form-group">
                <label for="available-seats">Available Seats</label>
                <input type="number" id="available-seats" name="available_seats" min="1" max="10" value="1" required>
            </div>
            <div class="form-group">
                <label for="price-per-seat">Price per Seat ($)</label>
                <input type="number" id="price-per-seat" name="price_per_seat" min="0" step="0.01" placeholder="0.00" required>
            </div>
            <div class="form-group">
                <label for="additional-notes">Additional Notes</label>
                <textarea id="additional-notes" name="additional_notes" placeholder="Any additional details..."></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="smoking-allowed">Smoking Allowed</label>
                    <input type="checkbox" id="smoking-allowed" name="smoking_allowed" value="1">
                </div>
                <div class="form-group">
                    <label for="pets-allowed">Pets Allowed</label>
                    <input type="checkbox" id="pets-allowed" name="pets_allowed" value="1">
                </div>
            </div>
            <div class="form-group">
                <label for="luggage-size">Luggage Size</label>
                <select id="luggage-size" name="luggage_size" required>
                    <option value="small">Small</option>
                    <option value="medium" selected>Medium</option>
                    <option value="large">Large</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" id="save-changes-btn" class="submit-btn">Save Changes</button>
                <button type="button" id="cancel-post" class="cancel-btn">Cancel</button>
            </div>
        </form>
    </section>

    <section class="edit-form-container" id="edit-form-container" style="display:none;">
        <h2>Edit Ride Offer</h2>
        <form id="edit-form" class="edit-form" method="POST" action="?action=edit" enctype="multipart/form-data">
            <input type="hidden" id="edit-ride-id" name="ride_id">
            <div class="form-group">
                <label for="edit-car-id">Select Car</label>
                <select id="edit-car-id" name="car_id" required>
                    <option value="">-- Select a Car --</option>
                    <?php
                    // Fetch the user's cars from the database
                    require_once '../../config/Database.php';
                    $db = Database::getConnection();
                    $stmt = $db->prepare("SELECT id, car_make, car_model, car_year FROM cars WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($cars as $car) {
                        echo "<option value='{$car['id']}'>{$car['car_make']} {$car['car_model']} ({$car['car_year']})</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="edit-departure-location">Departure Location</label>
                <input type="text" id="edit-departure-location" name="departure_location" placeholder="Starting location" required>
            </div>
            <div class="form-group">
                <label for="edit-destination-location">Destination Location</label>
                <input type="text" id="edit-destination-location" name="destination_location" placeholder="Destination" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="edit-departure-datetime">Departure Date & Time</label>
                    <input type="datetime-local" id="edit-departure-datetime" name="departure_datetime" required>
                </div>
                <div class="form-group">
                    <label for="edit-return-datetime">Return Date & Time</label>
                    <input type="datetime-local" id="edit-return-datetime" name="return_datetime">
                </div>
            </div>
            <div class="form-group">
                <label for="edit-available-seats">Available Seats</label>
                <input type="number" id="edit-available-seats" name="available_seats" min="1" max="10" value="1" required>
            </div>
            <div class="form-group">
                <label for="edit-price-per-seat">Price per Seat ($)</label>
                <input type="number" id="edit-price-per-seat" name="price_per_seat" min="0" step="0.01" placeholder="0.00" required>
            </div>
            <div class="form-group">
                <label for="edit-additional-notes">Additional Notes</label>
                <textarea id="edit-additional-notes" name="additional_notes" placeholder="Any additional details..."></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="edit-smoking-allowed">Smoking Allowed</label>
                    <input type="checkbox" id="edit-smoking-allowed" name="smoking_allowed" value="1">
                </div>
                <div class="form-group">
                    <label for="edit-pets-allowed">Pets Allowed</label>
                    <input type="checkbox" id="edit-pets-allowed" name="pets_allowed" value="1">
                </div>
            </div>
            <div class="form-group">
                <label for="edit-luggage-size">Luggage Size</label>
                <select id="edit-luggage-size" name="luggage_size" required>
                    <option value="small">Small</option>
                    <option value="medium" selected>Medium</option>
                    <option value="large">Large</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" id="save-edit-btn" class="submit-btn">Save Changes</button>
                <button type="button" id="cancel-edit" class="cancel-btn">Cancel</button>
            </div>
        </form>
    </section>

    <!-- Reservation Modal -->
    <div id="reservation-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <span id="close-reservation-modal" class="close">&times;</span>
            <h2>Reserve a Ride</h2>
            <form id="reservation-form">
                <input type="hidden" id="reservation-ride-id" name="ride_id">
                <div class="form-group">
                    <label for="seats-booked">Seats to Book</label>
                    <input type="number" id="seats-booked" name="seats_booked" min="1" max="10" value="1" required>
                </div>
                <div class="form-group">
                    <label for="pickup-location">Pickup Location</label>
                    <input type="text" id="pickup-location" name="pickup_location" placeholder="Enter pickup location" required>
                </div>
                <div class="form-group">
                    <label for="dropoff-location">Dropoff Location</label>
                    <input type="text" id="dropoff-location" name="dropoff_location" placeholder="Enter dropoff location" required>
                </div>
                <div class="form-group">
                    <label for="special-requests">Special Requests</label>
                    <textarea id="special-requests" name="special_requests" placeholder="Any special requests..."></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="submit-btn">Reserve</button>
                    <button type="button" id="cancel-reservation" class="cancel-btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</main>

<footer class="footer">
    <div class="footer-content">
        <div class="footer-section about">
            <div class="logo-container">
                <a href="index.php" class="logo">
                    <span class="logo-text">Clyptor</span>
                </a>
            </div>
            <p>Clyptor provides innovative solutions for carpooling, home rentals, and car rentals. Join our community today!</p>
            <div class="socials">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-linkedin"></i></a>
            </div>
        </div>
        
        <div class="footer-section links">
            <h3>Quick Links</h3>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="services/carpooling.php">Carpooling</a></li>
                <li><a href="services/home-rent.php">Home Rent</a></li>
                <li><a href="services/car-rent.php">Car Rent</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
        </div>
        
        <div class="footer-section contact">
            <h3>Contact Us</h3>
            <p><i class="fas fa-map-marker-alt"></i> TUNIS, BELVEDERE, BUREAU N°1</p>
            <p><i class="fas fa-phone"></i> +216 52 180 466</p>
            <p><i class="fas fa-envelope"></i> info@clyptor.tn</p>
        </div>
    </div>
    
    <div class="footer-bottom">
        <p>&copy; <script>document.write(new Date().getFullYear())</script> Clyptor. All rights reserved.</p>
    </div>
</footer>

<script src="js/main.js"></script>
<script src="js/auth.js"></script>
<script src="js/posts.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const postForm = document.getElementById('post-form');
        const postFormContainer = document.getElementById('post-form-container');
        const createPostBtn = document.getElementById('create-post-btn');
        const cancelPostBtn = document.getElementById('cancel-post');
        const formTitle = document.getElementById('form-title');
        const saveChangesBtn = document.getElementById('save-changes-btn');
        const postsContainer = document.getElementById('posts-container');

        // Show the popup when "Create Ride Offer" is clicked
        createPostBtn.addEventListener('click', function () {
            formTitle.textContent = 'Create a Ride Offer';
            postForm.action = '?action=create';
            saveChangesBtn.textContent = 'Post Offer';
            postForm.reset();
            postFormContainer.style.display = 'block';
            window.scrollTo({ top: postFormContainer.offsetTop, behavior: 'smooth' });
        });

        // Hide the popup when "Cancel" is clicked
        cancelPostBtn.addEventListener('click', function () {
            postFormContainer.style.display = 'none';
        });

        // Handle form submission via AJAX
        postForm.addEventListener('submit', function (e) {
            e.preventDefault(); // Prevent default form submission

            const formData = new FormData(postForm);

            fetch(postForm.action, {
                method: 'POST',
                body: formData,
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Ride offer created successfully!');
                        postFormContainer.style.display = 'none';

                        // Optionally, reload the posts section to reflect the new ride
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error creating ride offer:', error);
                    alert('An error occurred while creating the ride offer.');
                });
        });

        // Handle edit button click
        postsContainer.addEventListener('click', function (e) {
            if (e.target.classList.contains('edit-btn')) {
                const rideId = e.target.dataset.id;

                // Log the ride ID being fetched
                console.log(`Fetching ride with ID: ${rideId}`);

                fetch(`?action=fetch&ride_id=${rideId}`)
                    .then(response => {
                        console.log('Response status:', response.status);
                        return response.json();
                    })
                    .then(data => {
                        console.log('Fetched data:', data);
                        if (data.success) {
                            // Populate the form with the ride data
                            formTitle.textContent = 'Edit Ride Offer';
                            postForm.action = `?action=edit&ride_id=${rideId}`;
                            saveChangesBtn.textContent = 'Save Changes';

                            document.getElementById('departure-location').value = data.ride.departure_location;
                            document.getElementById('destination-location').value = data.ride.destination_location;
                            document.getElementById('departure-datetime').value = data.ride.departure_datetime.replace(' ', 'T');
                            document.getElementById('return-datetime').value = data.ride.return_datetime ? data.ride.return_datetime.replace(' ', 'T') : '';
                            document.getElementById('available-seats').value = data.ride.available_seats;
                            document.getElementById('price-per-seat').value = data.ride.price_per_seat;
                            document.getElementById('additional-notes').value = data.ride.additional_notes || '';
                            document.getElementById('smoking-allowed').checked = data.ride.smoking_allowed == 1;
                            document.getElementById('pets-allowed').checked = data.ride.pets_allowed == 1;
                            document.getElementById('car-id').value = data.ride.car_id;

                            postFormContainer.style.display = 'block';
                        } else {
                            console.error('Error fetching ride data:', data.message);
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching ride data:', error);
                        alert('An error occurred while fetching the ride data.');
                    });
            }
        });

        const editForm = document.getElementById('edit-form');
        const editFormContainer = document.getElementById('edit-form-container');
        const cancelEditBtn = document.getElementById('cancel-edit');

        // Handle edit button click
        postsContainer.addEventListener('click', function (e) {
            if (e.target.classList.contains('edit-btn')) {
                const rideId = e.target.dataset.id;

                // Log the ride ID being fetched
                console.log(`Fetching ride with ID: ${rideId}`);

                fetch(`?action=fetch&ride_id=${rideId}`)
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(data => {
                        console.log('Response data:', data);
                        if (data.success) {
                            // Populate the edit form with the ride data
                            const ride = data.ride;
                            document.getElementById('edit-ride-id').value = rideId;
                            document.getElementById('edit-departure-location').value = ride.departure_location;
                            document.getElementById('edit-destination-location').value = ride.destination_location;
                            document.getElementById('edit-departure-datetime').value = ride.departure_datetime.replace(' ', 'T');
                            document.getElementById('edit-return-datetime').value = ride.return_datetime ? ride.return_datetime.replace(' ', 'T') : '';
                            document.getElementById('edit-available-seats').value = ride.available_seats;
                            document.getElementById('edit-price-per-seat').value = ride.price_per_seat;
                            document.getElementById('edit-additional-notes').value = ride.additional_notes || '';
                            document.getElementById('edit-smoking-allowed').checked = ride.smoking_allowed == 1;
                            document.getElementById('edit-pets-allowed').checked = ride.pets_allowed == 1;
                            document.getElementById('edit-car-id').value = ride.car_id;
                            document.getElementById('edit-luggage-size').value = ride.luggage_size;

                            // Show the edit popup
                            editFormContainer.style.display = 'block';
                        } else {
                            console.error('Server error:', data.message);
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        alert('Failed to fetch ride data. Please check console for details.');
                    });
            }
        });

        // Hide the popup when "Cancel" is clicked
        cancelEditBtn.addEventListener('click', function () {
            editFormContainer.style.display = 'none';
        });

        // Handle delete button click
        postsContainer.addEventListener('click', function (e) {
            if (e.target.classList.contains('delete-btn')) {
                const rideId = e.target.dataset.id;

                // Confirm deletion
                if (confirm('Are you sure you want to delete this ride offer?')) {
                    // Log the ride ID being deleted
                    console.log(`Deleting ride with ID: ${rideId}`);

                    fetch(`?action=delete&ride_id=${rideId}`, {
                        method: 'POST',
                    })
                        .then(response => {
                            console.log('Response status:', response.status);
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            console.log('Delete response:', data);
                            if (data.success) {
                                alert('Ride offer deleted successfully.');
                                location.reload(); // Reload the page to reflect changes
                            } else {
                                console.error('Error deleting ride:', data.message);
                                alert('Error: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error deleting ride:', error);
                            alert('An error occurred while deleting the ride. Please check the console for details.');
                        });
                }
            }
        });

        const reservationModal = document.getElementById('reservation-modal');
        const closeReservationModal = document.getElementById('close-reservation-modal');
        const cancelReservation = document.getElementById('cancel-reservation');
        const reservationForm = document.getElementById('reservation-form');
        const reservationRideId = document.getElementById('reservation-ride-id');

        // Handle reserve button click
        postsContainer.addEventListener('click', function (e) {
            if (e.target.classList.contains('reserve-btn')) {
                const rideId = e.target.dataset.id;

                // Set the ride ID in the hidden input field
                reservationRideId.value = rideId;

                // Show the reservation modal
                reservationModal.style.display = 'block';
            }
        });

        // Close the reservation modal
        closeReservationModal.addEventListener('click', function () {
            reservationModal.style.display = 'none';
        });

        cancelReservation.addEventListener('click', function () {
            reservationModal.style.display = 'none';
        });

        // Handle reservation form submission
        reservationForm.addEventListener('submit', function (e) {
            e.preventDefault(); // Prevent default form submission

            const formData = new FormData(reservationForm);

            fetch('?action=reserve', {
                method: 'POST',
                body: formData,
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Reservation successful!');
                        reservationModal.style.display = 'none';
                        location.reload(); // Reload the page to reflect changes
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error making reservation:', error);
                    alert('An error occurred while making the reservation.');
                });
        });

        // Close the modal if the user clicks outside of it
        window.addEventListener('click', function (e) {
            if (e.target === reservationModal) {
                reservationModal.style.display = 'none';
            }
        });
    });
</script>
</body>
</html>