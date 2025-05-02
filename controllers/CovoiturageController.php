<?php
require_once '../../models/RideModel.php';

class CovoiturageController {
    private $rideModel;

    public function __construct() {
        // Check if a session is already active before starting a new one
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->rideModel = new RideModel();
    }

    public function createRideOffer($postData, $files) {
        try {
            // Validate required fields
            $requiredFields = ['departure_location', 'destination_location', 'departure_datetime', 'available_seats', 'price_per_seat', 'car_id'];
            foreach ($requiredFields as $field) {
                if (empty($postData[$field])) {
                    error_log("Validation failed: The field '$field' is required.");
                    return ['success' => false, 'message' => "The field '$field' is required."];
                }
            }

            $data = [
                'driver_id' => $_SESSION['user_id'],
                'car_id' => $postData['car_id'], // Ensure car_id is passed in the form
                'departure_location' => $postData['departure_location'],
                'departure_latitude' => $postData['departure_latitude'] ?? null,
                'departure_longitude' => $postData['departure_longitude'] ?? null,
                'destination_location' => $postData['destination_location'],
                'destination_latitude' => $postData['destination_latitude'] ?? null,
                'destination_longitude' => $postData['destination_longitude'] ?? null,
                'departure_datetime' => $postData['departure_datetime'],
                'return_datetime' => $postData['return_datetime'] ?? null,
                'available_seats' => $postData['available_seats'],
                'price_per_seat' => $postData['price_per_seat'],
                'additional_notes' => $postData['additional_notes'] ?? null,
                'smoking_allowed' => isset($postData['smoking_allowed']) ? 1 : 0,
                'pets_allowed' => isset($postData['pets_allowed']) ? 1 : 0,
                'luggage_size' => $postData['luggage_size']
            ];

            // Log the data being passed to the model
            error_log('Data passed to createRide: ' . print_r($data, true));

            if ($this->rideModel->createRide($data)) {
                return ['success' => true, 'message' => 'Ride offer created successfully.'];
            } else {
                error_log('Failed to create ride offer.');
                return ['success' => false, 'message' => 'Failed to create ride offer.'];
            }
        } catch (Exception $e) {
            error_log('Error in createRideOffer: ' . $e->getMessage());
            return ['success' => false, 'message' => 'An internal error occurred.'];
        }
    }

    public function deleteRideOffer($rideId) {
        try {
            // Log the ride ID being deleted
            error_log("Attempting to delete ride with ID: $rideId");

            // Ensure the ride belongs to the logged-in user
            $ride = $this->rideModel->getRideById($rideId);
            if (!$ride) {
                error_log("Ride with ID $rideId not found.");
                return ['success' => false, 'message' => 'Ride not found.'];
            }

            if ($ride['driver_id'] != $_SESSION['user_id']) {
                error_log("Unauthorized deletion attempt by user ID {$_SESSION['user_id']} for ride ID $rideId.");
                return ['success' => false, 'message' => 'You are not authorized to delete this ride.'];
            }

            if ($this->rideModel->deleteRide($rideId)) {
                error_log("Ride with ID $rideId deleted successfully.");
                return ['success' => true, 'message' => 'Ride offer deleted successfully.'];
            } else {
                error_log("Failed to delete ride with ID: $rideId.");
                return ['success' => false, 'message' => 'Failed to delete ride offer.'];
            }
        } catch (Exception $e) {
            error_log('Error in deleteRideOffer: ' . $e->getMessage());
            return ['success' => false, 'message' => 'An internal error occurred.'];
        }
    }

    public function editRideOffer($rideId, $postData) {
        try {
            // Ensure the ride belongs to the logged-in user
            $ride = $this->rideModel->getRideById($rideId);
            if (!$ride || $ride['driver_id'] != $_SESSION['user_id']) {
                return ['success' => false, 'message' => 'You are not authorized to edit this ride.'];
            }

            // Validate required fields
            $requiredFields = ['departure_location', 'destination_location', 'departure_datetime', 'available_seats', 'price_per_seat', 'car_id'];
            foreach ($requiredFields as $field) {
                if (empty($postData[$field])) {
                    return ['success' => false, 'message' => "The field '$field' is required."];
                }
            }

            $data = [
                'departure_location' => $postData['departure_location'],
                'departure_latitude' => $postData['departure_latitude'] ?? null,
                'departure_longitude' => $postData['departure_longitude'] ?? null,
                'destination_location' => $postData['destination_location'],
                'destination_latitude' => $postData['destination_latitude'] ?? null,
                'destination_longitude' => $postData['destination_longitude'] ?? null,
                'departure_datetime' => $postData['departure_datetime'],
                'return_datetime' => $postData['return_datetime'] ?? null,
                'available_seats' => $postData['available_seats'],
                'price_per_seat' => $postData['price_per_seat'],
                'additional_notes' => $postData['additional_notes'] ?? null,
                'smoking_allowed' => isset($postData['smoking_allowed']) ? 1 : 0,
                'pets_allowed' => isset($postData['pets_allowed']) ? 1 : 0,
                'luggage_size' => $postData['luggage_size'],
                'car_id' => $postData['car_id']
            ];

            if ($this->rideModel->updateRide($rideId, $data)) {
                return ['success' => true, 'message' => 'Ride offer updated successfully.'];
            } else {
                return ['success' => false, 'message' => 'Failed to update ride offer.'];
            }
        } catch (Exception $e) {
            error_log('Error in editRideOffer: ' . $e->getMessage());
            return ['success' => false, 'message' => 'An internal error occurred.'];
        }
    }

    public function fetchRideOffer($rideId, $checkOwner = false) {
        try {
            error_log("Fetching ride with ID: $rideId");
    
            if (!is_numeric($rideId) || $rideId <= 0) {
                return ['success' => false, 'message' => 'Invalid ride ID.'];
            }
    
            $ride = $this->rideModel->getRideById($rideId);
            if (!$ride) {
                return ['success' => false, 'message' => 'Ride not found.'];
            }
    
            // Only check owner if requested
            if ($checkOwner && $ride['driver_id'] != $_SESSION['user_id']) {
                return ['success' => false, 'message' => 'You are not authorized to view this ride.'];
            }
    
            return ['success' => true, 'ride' => $ride];
        } catch (Exception $e) {
            error_log('Error in fetchRideOffer: ' . $e->getMessage());
            return ['success' => false, 'message' => 'An internal error occurred.'];
        }
    }
}
?>