<?php
session_start(); // Ensure this is at the very top of the file
require_once '../../models/PackageDelivery.php';
require_once '../../controllers/PackageDeliveryController.php';

use Models\PackageDelivery;
use Controllers\PackageDeliveryController;


// Database connection
try {
    $db = new PDO('mysql:host=127.0.0.1;dbname=clyptor', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Fetch the user's default address if not already set in the session
if (isset($_SESSION['user_id']) && !isset($_SESSION['default_address_id'])) {
    try {
        $stmt = $db->prepare("SELECT address_id FROM user_addresses WHERE user_id = :user_id AND is_default = 1 LIMIT 1");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $defaultAddress = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($defaultAddress) {
            $_SESSION['default_address_id'] = $defaultAddress['address_id'];
        }
    } catch (PDOException $e) {
        error_log("Error fetching default address: " . $e->getMessage());
    }
}

// Fetch the user's addresses for the pickup address dropdown
$userAddresses = [];
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $db->prepare("SELECT address_id, CONCAT(address_line1, ', ', city, ', ', state, ', ', country) AS full_address FROM user_addresses WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $userAddresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching user addresses: " . $e->getMessage());
    }
}

// Initialize model and controller
$model = new PackageDelivery($db);
$controller = new PackageDeliveryController($model); // Pass the model to the controller

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_post'])) {
    try {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'You must be logged in to create a post.']);
            exit;
        }

        // Automatically set sender_id and pickup_address_id
        $data = [
            'sender_id' => $_SESSION['user_id'], // Get user ID from session
            'pickup_address_id' => $_POST['pickup_address_id'] ?? $_SESSION['default_address_id'] ?? null, // Get pickup address ID from form or session
            'delivery_address_id' => $_POST['delivery_address_id'] ?? null,
            'package_description' => $_POST['package_description'] ?? null,
            'package_weight' => $_POST['package_weight'] ?? null,
            'package_dimensions' => $_POST['package_dimensions'] ?? null,
            'estimated_value' => $_POST['estimated_value'] ?? null,
            'delivery_deadline' => $_POST['delivery_deadline'] ?? null,
            'proposed_price' => $_POST['proposed_price'] ?? null,
        ];

        if (empty($data['pickup_address_id'])) {
            echo json_encode(['success' => false, 'message' => 'You must set a default pickup address in your profile.']);
            exit;
        }

        // Call the controller to create the post
        $response = $controller->createPost($data);

        // Return the response as JSON
        echo json_encode($response);
        exit;
    } catch (Exception $e) {
        // Log the error and return a generic error message
        error_log("Error creating package delivery: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An unexpected error occurred. Please try again later.']);
        exit;
    }
}

// Handle form submission for editing and deleting posts
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if (!isset($_SESSION['user_id'])) {
            $errorMessage = 'You must be logged in to perform this action.';
        } else {
            $action = $_POST['action'];
            $deliveryId = $_POST['delivery_id'] ?? null;

            if ($action === 'edit' && $deliveryId) {
                $data = [
                    'delivery_id' => $deliveryId,
                    'package_description' => $_POST['package_description'] ?? null,
                    'package_weight' => $_POST['package_weight'] ?? null,
                    'package_dimensions' => $_POST['package_dimensions'] ?? null,
                    'estimated_value' => $_POST['estimated_value'] ?? null,
                    'delivery_deadline' => $_POST['delivery_deadline'] ?? null,
                    'proposed_price' => $_POST['proposed_price'] ?? null,
                ];
                $response = $controller->editPost($data);
                if ($response['success']) {
                    $successMessage = $response['message'];
                } else {
                    $errorMessage = $response['message'];
                }
            } elseif ($action === 'delete' && $deliveryId) {
                $response = $controller->deletePost($deliveryId);
                if ($response['success']) {
                    $successMessage = $response['message'];
                } else {
                    $errorMessage = $response['message'];
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error handling post action: " . $e->getMessage());
        $errorMessage = 'An unexpected error occurred. Please try again later.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deliver Package</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/hf.css">
    <style>
        

/* Add these styles to your existing CSS */
.tracking-section {
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin: 20px 0;
}

.tracking-form {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.tracking-form input {
    flex: 1;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.tracking-form button {
    padding: 10px 20px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.tracking-form button:hover {
    background-color: #0056b3;
}

.tracking-results {
    background-color:rgb(7, 7, 7);
    padding: 15px;
    border-radius: 8px;
}

.tracking-history ul {
    list-style-type: none;
    padding: 0;
}

.tracking-history li {
    background-color: #fff;
    padding: 10px;
    margin-bottom: 10px;
    border-radius: 4px;
    border-left: 4px solid #007bff;
}

.track-package-link {
    display: inline-block;
    margin-top: 15px;
    color: #007bff;
    text-decoration: none;
    font-weight: bold;
}

.track-package-link:hover {
    text-decoration: underline;
}

.status {
    font-weight: bold;
    color: #28a745;
}

.error {
    color: #dc3545;
}


        /* Styles for the posts */
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .post:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .post h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #333;
        }

        .post p {
            margin: 5px 0;
            color: #555;
            font-size: 0.9rem;
        }

        .post .status {
            font-weight: bold;
            color: #007bff;
        }

        .post .price {
            font-weight: bold;
            color: #28a745;
        }

        .post .actions {
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
        }

        .post .actions button {
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 12px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .post .actions button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
<header class="header">
            <div class="logo-container">
                <a href="index.php" class="logo">
                    <!-- <img src="assets/images/logo.png" alt="Clyptor Logo"> -->
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
                <a href="login.php" class="btn btn-outline">Login</a>
                <a href="register.php" class="btn btn-primary">Register</a>
            </div>
            
            <button class="mobile-menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
        </header>


    <!-- Hero Section -->
    <main class="service-main">
        <section class="service-hero">
            <div class="hero-content">
                <h1>Deliver Packages with Clyptor</h1>
                <p>Fast, secure, and reliable package delivery services at your fingertips.</p>
                <button id="create-post-btn" class="cta-button">Deliver your package</button>
            </div>
            <div class="hero-image">
                <div class="car-animation">
                    
                </div>
            </div>
        </section>
        
    
    <section class="post-form-container" id="post-form-container" style="display:none;">
        <h2>Create a Package Delivery</h2>
        <?php if (isset($successMessage)): ?>
            <p class="success-message"><?= htmlspecialchars($successMessage) ?></p>
        <?php elseif (isset($errorMessage)): ?>
            <p class="error-message"><?= htmlspecialchars($errorMessage) ?></p>
        <?php endif; ?>
        <form id="post-form" method="POST">
            <input type="hidden" name="create_post" value="1">
            <div class="form-group">
                <label for="pickup_address_id">Pickup Address</label>
                <select id="pickup_address_id" name="pickup_address_id" required>
                    <option value="">Select a pickup address</option>
                    <?php foreach ($userAddresses as $address): ?>
                        <option value="<?= $address['address_id'] ?>" <?= (isset($_SESSION['default_address_id']) && $_SESSION['default_address_id'] == $address['address_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($address['full_address']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="delivery_address_id">Delivery Address ID</label>
                <input type="number" id="delivery_address_id" name="delivery_address_id" required>
            </div>
            <div class="form-group">
                <label for="package_description">Package Description</label>
                <textarea id="package_description" name="package_description" required></textarea>
            </div>
            <div class="form-group">
                <label for="package_weight">Package Weight (kg)</label>
                <input type="number" id="package_weight" name="package_weight" step="0.01" required>
            </div>
            <div class="form-group">
                <label for="package_dimensions">Package Dimensions</label>
                <input type="text" id="package_dimensions" name="package_dimensions">
            </div>
            <div class="form-group">
                <label for="estimated_value">Estimated Value ($)</label>
                <input type="number" id="estimated_value" name="estimated_value" step="0.01">
            </div>
            <div class="form-group">
                <label for="delivery_deadline">Delivery Deadline</label>
                <input type="datetime-local" id="delivery_deadline" name="delivery_deadline">
            </div>
            <div class="form-group">
                <label for="proposed_price">Proposed Price ($)</label>
                <input type="number" id="proposed_price" name="proposed_price" step="0.01">
            </div>
            <div class="form-actions">
                <button type="submit" class="submit-btn">Submit</button>
                <button type="button" id="cancel-post" class="cancel-btn">Cancel</button>
            </div>
        </form>
    </section>
    <section class="posts-section">
        <h2>Available Packages</h2>
        <div id="posts-container" class="posts-container">
            <?php
            $packages = $controller->getAvailablePackages();

            if (!empty($packages)) {
                foreach ($packages as $package) {
                    $isOwner = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $package['sender_id'];

                    // Debugging output
                    error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'not set'));
                    error_log("Package sender_id: " . $package['sender_id']);
                    error_log("Is owner: " . ($isOwner ? 'true' : 'false'));

                    echo "<div class='post' data-id='{$package['delivery_id']}'>
                            <h3>Package ID: {$package['delivery_id']}</h3>
                            <p><strong>Description:</strong> <span class='description'>{$package['package_description']}</span></p>
                            <p><strong>Weight:</strong> <span class='weight'>{$package['package_weight']}</span> kg</p>
                            <p class='price'><strong>Proposed Price:</strong> $<span class='price-value'>{$package['proposed_price']}</span></p>
                            <p class='status'><strong>Status:</strong> {$package['status']}</p>
                            <div class='actions'>";
                    if ($isOwner) {
                        echo "<button class='edit-btn' data-id='{$package['delivery_id']}'>Edit</button>
                              <form method='POST' style='display:inline-block;'>
                                    <input type='hidden' name='action' value='delete'>
                                    <input type='hidden' name='delivery_id' value='{$package['delivery_id']}'>
                                    <button type='submit' class='delete-btn'>Delete</button>
                              </form>";
                    }
                    echo "      </div>
                          </div>";
                }
            } else {
                echo "<p>No packages available at the moment.</p>";
            }
            ?>
        </div>
    </section>
    <section class="tracking-section" id="tracking-section" style="display:none;">
    <h2>Track Your Package</h2>
    <div class="tracking-form">
        <input type="text" id="tracking-number" placeholder="Enter your delivery ID">
        <button id="track-package-btn">Track Package</button>
    </div>
    
    <div id="tracking-results" class="tracking-results">
        <div class="tracking-header">
            <div class="status-progress" id="status-progress"></div>
        </div>
        <div class="tracking-details">
            <div class="map-container" id="map-container">
                <div id="map" style="height: 300px;"></div>
            </div>
            <div class="tracking-history-container">
                <h4>Tracking History</h4>
                <div id="tracking-history"></div>
            </div>
        </div>
    </div>
</section>
<!-- Add Leaflet CSS and JS for maps -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

<style>
/* Add these styles */
.status-progress {
    display: flex;
    justify-content: space-between;
    position: relative;
    margin: 30px 0;
}

.status-step {
    text-align: center;
    position: relative;
    z-index: 2;
    flex: 1;
}

.status-step .step-icon {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #ddd;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 5px;
}

.status-step.active .step-icon {
    background: #007bff;
    color: white;
}

.status-step.completed .step-icon {
    background: #28a745;
    color: white;
}

.status-progress::before {
    content: '';
    position: absolute;
    top: 15px;
    left: 0;
    right: 0;
    height: 2px;
    background: #ddd;
    z-index: 1;
}

.progress-bar {
    position: absolute;
    top: 15px;
    left: 0;
    height: 2px;
    background: #28a745;
    z-index: 1;
    transition: width 0.3s ease;
}

.map-container {
    margin-bottom: 20px;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.tracking-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

@media (max-width: 768px) {
    .tracking-details {
        grid-template-columns: 1fr;
    }
}

.tracking-history-container {
    max-height: 300px;
    overflow-y: auto;
    padding-right: 10px;
}

.tracking-event {
    padding: 10px;
    margin-bottom: 10px;
    background: #f8f9fa;
    border-radius: 4px;
    border-left: 3px solid #007bff;
}

.tracking-event .event-time {
    font-size: 0.8em;
    color: #6c757d;
}

.tracking-event .event-status {
    font-weight: bold;
    margin: 5px 0;
}

.tracking-event .event-location {
    font-style: italic;
}
</style>

<script>
// Add this to your JavaScript
let map;
let routeLayer;

function initMap(pickupCoords, deliveryCoords, currentCoords) {
    if (!map) {
        map = L.map('map').setView([pickupCoords.lat, pickupCoords.lng], 6);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
    } else {
        map.setView([currentCoords.lat, currentCoords.lng], 12);
    }

    // Clear previous markers and route
    if (routeLayer) {
        map.removeLayer(routeLayer);
    }

    // Add markers
    const pickupMarker = L.marker([pickupCoords.lat, pickupCoords.lng])
        .addTo(map)
        .bindPopup("Pickup Location");
    
    const deliveryMarker = L.marker([deliveryCoords.lat, deliveryCoords.lng])
        .addTo(map)
        .bindPopup("Delivery Location");
    
    const currentMarker = L.marker([currentCoords.lat, currentCoords.lng], {
        icon: L.divIcon({
            className: 'current-location-marker',
            html: '<div class="pulse-marker"></div>',
            iconSize: [20, 20]
        })
    }).addTo(map).bindPopup("Current Location");

    // Add route (simplified - in production you'd use a routing service)
    routeLayer = L.polyline([
        [pickupCoords.lat, pickupCoords.lng],
        [currentCoords.lat, currentCoords.lng],
        [deliveryCoords.lat, deliveryCoords.lng]
    ], {color: 'blue'}).addTo(map);
}

function displayTrackingResults(data) {
    const package = data.package;
    const tracking = data.tracking;
    
    // Update status progress
    const statusFlow = [
        'awaiting_pickup', 
        'in_transit', 
        'out_for_delivery', 
        'delivered'
    ];
    const currentStatusIndex = statusFlow.indexOf(package.status);
    
    let progressHtml = '<div class="progress-bar" style="width:' + 
        (currentStatusIndex >= 0 ? (currentStatusIndex * 33.33) + '%' : '0%') + '"></div>';
    
    statusFlow.forEach((status, index) => {
        const statusText = status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        const isCompleted = currentStatusIndex > index;
        const isActive = currentStatusIndex === index;
        
        progressHtml += `
            <div class="status-step ${isCompleted ? 'completed' : ''} ${isActive ? 'active' : ''}">
                <div class="step-icon">${index + 1}</div>
                <div class="step-text">${statusText}</div>
            </div>
        `;
    });
    
    document.getElementById('status-progress').innerHTML = progressHtml;
    
    // Get coordinates for map
    fetch(`get-coordinates.php?pickup_id=${package.pickup_address_id}&delivery_id=${package.delivery_address_id}`)
        .then(response => response.json())
        .then(coords => {
            const currentCoords = package.current_latitude && package.current_longitude ? 
                { lat: package.current_latitude, lng: package.current_longitude } :
                coords.pickup;
            
            initMap(coords.pickup, coords.delivery, currentCoords);
        });
    
    // Update tracking history
    let historyHtml = '';
    if (tracking.length > 0) {
        tracking.forEach(event => {
            const eventTime = new Date(event.timestamp).toLocaleString();
            historyHtml += `
                <div class="tracking-event">
                    <div class="event-time">${eventTime}</div>
                    <div class="event-status">${formatStatus(event.status)}</div>
                    <div class="event-location">Location: ${event.location_name || 'N/A'}</div>
                    ${event.notes ? `<div class="event-notes">Notes: ${event.notes}</div>` : ''}
                </div>
            `;
        });
    } else {
        historyHtml = '<p>No tracking history available</p>';
    }
    
    document.getElementById('tracking-history').innerHTML = historyHtml;
}

function formatStatus(status) {
    return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

// Add marker styling
document.head.insertAdjacentHTML('beforeend', `
    <style>
        .current-location-marker {
            position: relative;
        }
        .pulse-marker {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #ff0000;
            position: relative;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(0.8); opacity: 1; }
            70% { transform: scale(1.3); opacity: 0.7; }
            100% { transform: scale(0.8); opacity: 1; }
        }
    </style>
`);
</script>
    <script>
        // Add this to your existing JavaScript
document.addEventListener("DOMContentLoaded", () => {
    const trackingSection = document.getElementById("tracking-section");
    const trackPackageBtn = document.getElementById("track-package-btn");
    const trackingResults = document.getElementById("tracking-results");

    // Show tracking section
    const trackPackageLink = document.createElement("a");
    trackPackageLink.href = "#";
    trackPackageLink.textContent = "Track your package";
    trackPackageLink.className = "track-package-link";
    trackPackageLink.addEventListener("click", (e) => {
        e.preventDefault();
        trackingSection.style.display = "block";
        window.scrollTo({ top: trackingSection.offsetTop, behavior: "smooth" });
    });
    
    // Add the link to the hero section
    const heroContent = document.querySelector(".hero-content");
    heroContent.appendChild(trackPackageLink);

    // Handle package tracking
    trackPackageBtn.addEventListener("click", () => {
        const trackingNumber = document.getElementById("tracking-number").value.trim();
        
        if (!trackingNumber) {
            alert("Please enter a delivery ID");
            return;
        }

        fetch(`track-package.php?delivery_id=${trackingNumber}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayTrackingResults(data);
                } else {
                    trackingResults.innerHTML = `<p class="error">${data.message}</p>`;
                }
            })
            .catch(error => {
                console.error("Error tracking package:", error);
                trackingResults.innerHTML = `<p class="error">An error occurred while tracking your package.</p>`;
            });
    });

    function displayTrackingResults(data) {
        const package = data.package;
        const tracking = data.tracking;
        
        let html = `
            <div class="package-info">
                <h3>Package #${package.delivery_id}</h3>
                <p><strong>Status:</strong> <span class="status">${package.status}</span></p>
                <p><strong>Description:</strong> ${package.package_description}</p>
                <p><strong>Weight:</strong> ${package.package_weight} kg</p>
                <p><strong>Price:</strong> $${package.proposed_price}</p>
            </div>
            <div class="tracking-history">
                <h4>Tracking History</h4>
                <ul>`;
        
        if (tracking.length > 0) {
            tracking.forEach(event => {
                html += `
                    <li>
                        <strong>${new Date(event.timestamp).toLocaleString()}</strong>
                        <p>Status: ${event.status}</p>
                        <p>Location: ${event.location}</p>
                        ${event.notes ? `<p>Notes: ${event.notes}</p>` : ''}
                    </li>`;
            });
        } else {
            html += `<li>No tracking history available</li>`;
        }
        
        html += `</ul></div>`;
        trackingResults.innerHTML = html;
    }
});
    document.addEventListener("DOMContentLoaded", () => {
        const postsContainer = document.getElementById("posts-container");
        const postFormContainer = document.getElementById("post-form-container");
        const postForm = document.getElementById("post-form");
        const createPostBtn = document.getElementById("create-post-btn");
        const cancelPostBtn = document.getElementById("cancel-post");

        // Show the popup when "Deliver your package" is clicked
        createPostBtn.addEventListener("click", () => {
            const isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
            if (!isLoggedIn) {
                alert("You must be logged in to create a package delivery.");
                return;
            }
            postFormContainer.style.display = "block";
            postForm.reset(); // Reset the form fields
            postForm.dataset.editing = ""; // Clear editing state
        });

        // Hide the popup when "Cancel" is clicked
        cancelPostBtn.addEventListener("click", () => {
            postFormContainer.style.display = "none";
            postForm.reset(); // Reset the form fields
        });

        // Handle Edit button click
        postsContainer.addEventListener("click", (e) => {
            if (e.target.classList.contains("edit-btn")) {
                const postElement = e.target.closest(".post");
                const deliveryId = postElement.dataset.id;
                const description = postElement.querySelector(".description").textContent;
                const weight = postElement.querySelector(".weight").textContent;
                const price = postElement.querySelector(".price-value").textContent;

                // Populate the form with post details for editing
                document.getElementById("package_description").value = description;
                document.getElementById("package_weight").value = weight;
                document.getElementById("proposed_price").value = price;

                // Set the editing state
                postForm.dataset.editing = deliveryId;

                // Show the form for editing
                postFormContainer.style.display = "block";
            }
        });

        // Handle form submission using AJAX
        postForm.addEventListener("submit", (e) => {
            e.preventDefault(); // Prevent the default form submission

            const formData = new FormData(postForm);
            const editingId = postForm.dataset.editing;

            if (editingId) {
                // Editing an existing post
                formData.append("action", "edit");
                formData.append("delivery_id", editingId);
            } else {
                // Creating a new post
                formData.append("create_post", "1");
            }

            fetch("", {
                method: "POST",
                body: formData,
            })
                .then((response) => response.json())
                .then((result) => {
                    if (result.success) {
                        alert(result.message);
                        // Close the form popup
                        postFormContainer.style.display = "none";

                        // Reset the form fields
                        postForm.reset();

                        // Update the post in the DOM if editing
                        if (editingId) {
                            const postElement = postsContainer.querySelector(`.post[data-id='${editingId}']`);
                            postElement.querySelector(".description").textContent = formData.get("package_description");
                            postElement.querySelector(".weight").textContent = formData.get("package_weight");
                            postElement.querySelector(".price-value").textContent = formData.get("proposed_price");
                        } else {
                            // Optionally, reload the posts to reflect the new post
                            fetchPosts();
                        }
                    } else {
                        alert(result.message);
                    }
                })
                .catch((error) => {
                    console.error("Error creating/editing post:", error);
                    alert("An error occurred while processing the post. Please try again.");
                });
        });

        // Function to fetch and display posts
        function fetchPosts() {
            fetch("")
                .then((response) => response.text())
                .then((html) => {
                    // Replace the posts container with the updated content
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, "text/html");
                    const updatedPostsContainer = doc.getElementById("posts-container");
                    postsContainer.innerHTML = updatedPostsContainer.innerHTML;
                })
                .catch((error) => {
                    console.error("Error fetching posts:", error);
                });
        }
    });
    </script>
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
    <script>
    document.addEventListener("DOMContentLoaded", () => {
        const authButtons = document.querySelector(".auth-buttons");
        const isLoggedIn = localStorage.getItem("isLoggedIn") === "true";

        if (isLoggedIn) {
            authButtons.innerHTML = `
                <a href="../admin/user/user-dashboard.php" class="btn btn-primary">Dashboard</a>
                <a href="logout.php" class="btn btn-outline">Logout</a>
            `;
        }

        // Add functionality to the "Deliver your package" button
        const createPostBtn = document.getElementById("create-post-btn");
        const postFormContainer = document.getElementById("post-form-container");
        const cancelPostBtn = document.getElementById("cancel-post");

        // Show the popup when "Deliver your package" is clicked
        createPostBtn.addEventListener("click", () => {
            const isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
            if (!isLoggedIn) {
                alert("You must be logged in to create a package delivery.");
                return;
            }
            postFormContainer.style.display = "block";
            window.scrollTo({ top: postFormContainer.offsetTop, behavior: "smooth" });
        });

        // Hide the popup when "Cancel" is clicked
        cancelPostBtn.addEventListener("click", () => {
            postFormContainer.style.display = "none";
        });
    });
    // Input validation functions
function validatePickupAddress() {
    const pickupAddress = document.getElementById('pickup_address_id');
    if (!pickupAddress.value) {
        alert('Please select a pickup address');
        return false;
    }
    return true;
}

function validateDeliveryAddress() {
    const deliveryAddress = document.getElementById('delivery_address_id');
    if (!deliveryAddress.value || isNaN(deliveryAddress.value)) {
        alert('Please enter a valid delivery address ID');
        return false;
    }
    return true;
}

function validatePackageDescription() {
    const description = document.getElementById('package_description').value.trim();
    if (description.length < 10) {
        alert('Package description must be at least 10 characters long');
        return false;
    }
    return true;
}

function validatePackageWeight() {
    const weight = parseFloat(document.getElementById('package_weight').value);
    if (isNaN(weight)) {
        alert('Please enter a valid weight');
        return false;
    }
    if (weight <= 0) {
        alert('Weight must be greater than 0');
        return false;
    }
    if (weight > 1000) {
        alert('Weight cannot exceed 1000 kg');
        return false;
    }
    return true;
}

function validatePackageDimensions() {
    const dimensions = document.getElementById('package_dimensions').value.trim();
    if (dimensions && !/^\d+x\d+x\d+$/.test(dimensions)) {
        alert('Dimensions should be in format LxWxH (e.g., 20x15x10)');
        return false;
    }
    return true;
}

function validateEstimatedValue() {
    const value = parseFloat(document.getElementById('estimated_value').value);
    if (!isNaN(value) ){
        if (value < 0) {
            alert('Estimated value cannot be negative');
            return false;
        }
        if (value > 1000000) {
            alert('Estimated value cannot exceed $1,000,000');
            return false;
        }
    }
    return true;
}

function validateDeliveryDeadline() {
    const deadline = document.getElementById('delivery_deadline').value;
    if (deadline) {
        const deadlineDate = new Date(deadline);
        const now = new Date();
        if (deadlineDate <= now) {
            alert('Delivery deadline must be in the future');
            return false;
        }
    }
    return true;
}

function validateProposedPrice() {
    const price = parseFloat(document.getElementById('proposed_price').value);
    if (isNaN(price)) {
        alert('Please enter a valid price');
        return false;
    }
    if (price <= 0) {
        alert('Price must be greater than 0');
        return false;
    }
    if (price > 10000) {
        alert('Price cannot exceed $10,000');
        return false;
    }
    return true;
}

// Real-time validation event listeners
document.getElementById('package_weight').addEventListener('blur', validatePackageWeight);
document.getElementById('package_dimensions').addEventListener('blur', validatePackageDimensions);
document.getElementById('estimated_value').addEventListener('blur', validateEstimatedValue);
document.getElementById('delivery_deadline').addEventListener('change', validateDeliveryDeadline);
document.getElementById('proposed_price').addEventListener('blur', validateProposedPrice);

// Form submission validation
postForm.addEventListener('submit', function(e) {
    // Validate all fields before submission
    const isValid = validatePickupAddress() &&
                   validateDeliveryAddress() &&
                   validatePackageDescription() &&
                   validatePackageWeight() &&
                   validatePackageDimensions() &&
                   validateEstimatedValue() &&
                   validateDeliveryDeadline() &&
                   validateProposedPrice();

    if (!isValid) {
        e.preventDefault(); // Prevent form submission if validation fails
        return false;
    }

    // Rest of your existing submission code...
});
    </script>
</body>
</html>