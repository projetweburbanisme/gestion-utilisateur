<?php
require_once '../../config/Database.php';

class RideModel {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function createRide($data) {
        try {
            $query = "INSERT INTO rides (driver_id, car_id, departure_location, destination_location, departure_datetime, return_datetime, available_seats, price_per_seat, additional_notes, smoking_allowed, pets_allowed, luggage_size, status) 
                      VALUES (:driver_id, :car_id, :departure_location, :destination_location, :departure_datetime, :return_datetime, :available_seats, :price_per_seat, :additional_notes, :smoking_allowed, :pets_allowed, :luggage_size, 'active')";
            $stmt = $this->db->prepare($query);

            $result = $stmt->execute([
                ':driver_id' => $_SESSION['user_id'],
                ':car_id' => $data['car_id'],
                ':departure_location' => $data['departure_location'],
                ':destination_location' => $data['destination_location'],
                ':departure_datetime' => $data['departure_datetime'],
                ':return_datetime' => $data['return_datetime'] ?? null,
                ':available_seats' => $data['available_seats'],
                ':price_per_seat' => $data['price_per_seat'],
                ':additional_notes' => $data['additional_notes'] ?? null,
                ':smoking_allowed' => $data['smoking_allowed'],
                ':pets_allowed' => $data['pets_allowed'],
                ':luggage_size' => $data['luggage_size']
            ]);

            if (!$result) {
                error_log('Database error: ' . print_r($stmt->errorInfo(), true));
            }

            return $result;
        } catch (Exception $e) {
            error_log('Error in createRide: ' . $e->getMessage());
            return false;
        }
    }

    public function getRideById($rideId) {
        try {
            // Log the ride ID being fetched
            error_log("Fetching ride from database with ID: $rideId");

            $stmt = $this->db->prepare("SELECT * FROM rides WHERE ride_id = :ride_id");
            $stmt->execute([':ride_id' => $rideId]);
            $ride = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ride) {
                error_log("No ride found with ID: $rideId");
            } else {
                error_log("Ride fetched successfully: " . print_r($ride, true));
            }

            return $ride;
        } catch (Exception $e) {
            error_log('Error in getRideById: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteRide($rideId) {
        try {
            // Log the ride ID being deleted
            error_log("Deleting ride from database with ID: $rideId");

            $stmt = $this->db->prepare("DELETE FROM rides WHERE ride_id = :ride_id");
            $result = $stmt->execute([':ride_id' => $rideId]);

            if ($result) {
                error_log("Ride with ID $rideId deleted successfully from the database.");
            } else {
                error_log("Failed to delete ride with ID $rideId from the database.");
            }

            return $result;
        } catch (Exception $e) {
            error_log('Error in deleteRide: ' . $e->getMessage());
            return false;
        }
    }

    public function updateRide($rideId, $data) {
        try {
            $query = "UPDATE rides SET 
                        departure_location = :departure_location,
                        departure_latitude = :departure_latitude,
                        departure_longitude = :departure_longitude,
                        destination_location = :destination_location,
                        destination_latitude = :destination_latitude,
                        destination_longitude = :destination_longitude,
                        departure_datetime = :departure_datetime,
                        return_datetime = :return_datetime,
                        available_seats = :available_seats,
                        price_per_seat = :price_per_seat,
                        additional_notes = :additional_notes,
                        smoking_allowed = :smoking_allowed,
                        pets_allowed = :pets_allowed,
                        luggage_size = :luggage_size,
                        car_id = :car_id
                      WHERE ride_id = :ride_id";
            $stmt = $this->db->prepare($query);
            $data['ride_id'] = $rideId;
            return $stmt->execute($data);
        } catch (Exception $e) {
            error_log('Error in updateRide: ' . $e->getMessage());
            return false;
        }
    }
}
?>
