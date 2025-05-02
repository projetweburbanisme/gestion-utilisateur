<?php
require_once '../../config/Database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['action'] === 'reserve') {
    session_start();

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'You must be logged in to reserve a seat.']);
        exit;
    }

    $db = Database::getConnection();
    $rideId = $_POST['ride_id'];
    $passengerId = $_SESSION['user_id'];
    $seatsBooked = $_POST['seats_booked'];
    $pickupLocation = $_POST['pickup_location'];
    $dropoffLocation = $_POST['dropoff_location'];
    $specialRequests = $_POST['special_requests'] ?? null;

    try {
        // Check if the ride exists and has enough available seats
        $stmt = $db->prepare("SELECT available_seats FROM rides WHERE ride_id = ? AND status = 'active'");
        $stmt->execute([$rideId]);
        $ride = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ride) {
            error_log("Ride not found or inactive for ride_id: $rideId");
            echo json_encode(['success' => false, 'message' => 'Ride not found or is no longer active.']);
            exit;
        }

        if ($ride['available_seats'] < $seatsBooked) {
            error_log("Not enough seats for ride_id: $rideId. Requested: $seatsBooked, Available: {$ride['available_seats']}");
            echo json_encode(['success' => false, 'message' => 'Not enough available seats.']);
            exit;
        }

        // Reserve the seat
        $stmt = $db->prepare("INSERT INTO ride_bookings (ride_id, passenger_id, seats_booked, pickup_location, dropoff_location, special_requests) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$rideId, $passengerId, $seatsBooked, $pickupLocation, $dropoffLocation, $specialRequests]);

        // Get the last inserted booking ID
        $bookingId = $db->lastInsertId();

        // Update the available seats in the rides table
        $stmt = $db->prepare("UPDATE rides SET available_seats = available_seats - ? WHERE ride_id = ?");
        $stmt->execute([$seatsBooked, $rideId]);

        echo json_encode(['success' => true, 'message' => 'Seat reserved successfully.', 'booking_id' => $bookingId]);
    } catch (Exception $e) {
        error_log('Error reserving seat: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An internal error occurred. Please try again later.']);
    }
}
?>
