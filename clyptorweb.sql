-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : jeu. 01 mai 2025 à 22:57
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `clyptorweb`
--

-- --------------------------------------------------------

--
-- Structure de la table `cars`
--
CREATE TABLE 
`cars` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `car_make` varchar(50) NOT NULL,
  `car_model` varchar(50) NOT NULL,
  `car_year` int(11) NOT NULL,
  `car_registration_number` varchar(50) NOT NULL,
  `car_verification_status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `car_verification_token` varchar(255) DEFAULT NULL,
  `car_verification_token_expires` datetime DEFAULT NULL,
  `car_verified_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `cars`
--

INSERT INTO `cars` (`id`, `user_id`, `car_make`, `car_model`, `car_year`, `car_registration_number`, `car_verification_status`, `car_verification_token`, `car_verification_token_expires`, `car_verified_at`, `created_at`, `updated_at`) VALUES
(3, 1, 'BMW', 'BMW', 2020, '12424', 'Approved', NULL, NULL, '2025-05-01 21:24:05', '2025-05-01 21:22:02', '2025-05-01 21:24:05'),
(4, 1, 'toyota', 'toyota', 2021, '21343', 'Approved', NULL, NULL, '2025-05-01 21:24:36', '2025-05-01 21:24:29', '2025-05-01 21:24:36');

-- --------------------------------------------------------

--
-- Structure de la table `rides`
--

CREATE TABLE `rides` (
  `ride_id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `departure_location` varchar(100) NOT NULL,
  `departure_latitude` decimal(10,8) DEFAULT NULL,
  `departure_longitude` decimal(11,8) DEFAULT NULL,
  `destination_location` varchar(100) NOT NULL,
  `destination_latitude` decimal(10,8) DEFAULT NULL,
  `destination_longitude` decimal(11,8) DEFAULT NULL,
  `departure_datetime` datetime NOT NULL,
  `return_datetime` datetime DEFAULT NULL,
  `available_seats` int(11) NOT NULL,
  `price_per_seat` decimal(10,2) DEFAULT NULL,
  `additional_notes` text DEFAULT NULL,
  `smoking_allowed` tinyint(1) DEFAULT 0,
  `pets_allowed` tinyint(1) DEFAULT 0,
  `luggage_size` enum('small','medium','large') DEFAULT 'medium',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('active','completed','cancelled') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ride_bookings`
--

CREATE TABLE `ride_bookings` (
  `booking_id` int(11) NOT NULL AUTO_INCREMENT,
  `ride_id` int(11) NOT NULL,
  `passenger_id` int(11) NOT NULL,
  `seats_booked` int(11) NOT NULL DEFAULT 1,
  `pickup_location` varchar(100) DEFAULT NULL,
  `pickup_latitude` decimal(10,8) DEFAULT NULL,
  `pickup_longitude` decimal(11,8) DEFAULT NULL,
  `dropoff_location` varchar(100) DEFAULT NULL,
  `dropoff_latitude` decimal(10,8) DEFAULT NULL,
  `dropoff_longitude` decimal(11,8) DEFAULT NULL,
  `booking_status` enum('pending','confirmed','rejected','cancelled','completed') DEFAULT 'pending',
  `special_requests` text DEFAULT NULL,
  `booked_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ride_messages`
--

CREATE TABLE `ride_messages` (
  `message_id` int(11) NOT NULL,
  `ride_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `message_text` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `sent_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ride_reviews`
--

CREATE TABLE `ride_reviews` (
  `review_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `reviewed_user_id` int(11) NOT NULL,
  `ride_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL CHECK (`rating` between 1 and 5),
  `review_text` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `address_line1` varchar(100) DEFAULT NULL,
  `address_line2` varchar(100) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state_province` varchar(50) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `verification_token_expires` datetime DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `car_verification_status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `car_verification_token` varchar(255) DEFAULT NULL,
  `car_verification_token_expires` datetime DEFAULT NULL,
  `car_verified_at` datetime DEFAULT NULL,
  `id_card_or_passport` varchar(255) DEFAULT NULL,
  `driver_license_front` varchar(255) DEFAULT NULL,
  `driver_license_back` varchar(255) DEFAULT NULL,
  `verification_status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `verification_submitted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `first_name`, `last_name`, `address_line1`, `address_line2`, `city`, `state_province`, `postal_code`, `country`, `phone_number`, `is_verified`, `verification_token`, `verification_token_expires`, `verified_at`, `created_at`, `updated_at`, `car_verification_status`, `car_verification_token`, `car_verification_token_expires`, `car_verified_at`, `id_card_or_passport`, `driver_license_front`, `driver_license_back`, `verification_status`, `verification_submitted_at`) VALUES
(1, 'taki', 'taki.mejri001@gmail.com', '$2y$10$qEqS20U9cMtQpKDKJQ8wCejyfJmt65lJLvf1MH/asfy5DfA1G7iUa', 'taki', 'mejri', '58889766 /// 34 RUE DE VERTVEEN', '34 RUE DE VERTVEEN CITE ZOUHOUR 2', 'Tunis', 'TP', '2052', 'Tunisiee', '58889766', 0, NULL, NULL, '2025-05-01 21:38:07', '2025-05-01 20:00:12', '2025-05-01 21:38:07', 'Pending', NULL, NULL, NULL, 'uploads/verification/494329526_1473167303664169_2052878546577377543_n.png', 'uploads/verification/494326119_629858960031611_4581981014149457222_n.png', 'uploads/verification/494330688_1011480307744373_5953346710744009129_n.png', 'Approved', '2025-05-01 21:34:02'),
(3, 'BRAG', 'ttatt@gmail.com', '$2y$10$GnE9L6ajlebk3qzzp7oI9uE7Q6Tg05u/Q9OyvbGxhewKomegnfMVq', 'abderrahmen', 'boussida', '58889766 /// 34 RUE DE VERTVEEN', '34 RUE DE VERTVEEN CITE ZOUHOUR 2', 'Tunis', 'TP', '2052', 'Tunisie', '58889766', 0, NULL, NULL, NULL, '2025-05-01 21:41:50', '2025-05-01 21:41:50', 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, 'Pending', NULL);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `car_registration_number` (`car_registration_number`),
  ADD KEY `idx_car_user` (`user_id`);

--
-- Index pour la table `rides`
--
ALTER TABLE `rides`
  ADD PRIMARY KEY (`ride_id`),
  ADD KEY `idx_rides_driver` (`driver_id`),
  ADD KEY `idx_rides_car` (`car_id`),
  ADD KEY `idx_rides_departure` (`departure_location`),
  ADD KEY `idx_rides_destination` (`destination_location`),
  ADD KEY `idx_rides_datetime` (`departure_datetime`);

--
-- Index pour la table `ride_bookings`
--
ALTER TABLE `ride_bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `idx_bookings_ride` (`ride_id`),
  ADD KEY `idx_bookings_passenger` (`passenger_id`);

--
-- Index pour la table `ride_messages`
--
ALTER TABLE `ride_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `idx_messages_ride` (`ride_id`),
  ADD KEY `idx_messages_sender` (`sender_id`),
  ADD KEY `idx_messages_recipient` (`recipient_id`);

--
-- Index pour la table `ride_reviews`
--
ALTER TABLE `ride_reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `reviewer_id` (`reviewer_id`),
  ADD KEY `reviewed_user_id` (`reviewed_user_id`),
  ADD KEY `idx_reviews_booking` (`booking_id`),
  ADD KEY `idx_reviews_ride` (`ride_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_verification_token` (`verification_token`),
  ADD KEY `idx_email_verification` (`email`,`is_verified`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `cars`
--
ALTER TABLE `cars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `rides`
--
ALTER TABLE `rides`
  MODIFY `ride_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ride_bookings`
--
ALTER TABLE `ride_bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ride_messages`
--
ALTER TABLE `ride_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ride_reviews`
--
ALTER TABLE `ride_reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `cars`
--
ALTER TABLE `cars`
  ADD CONSTRAINT `cars_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `rides`
--
ALTER TABLE `rides`
  ADD CONSTRAINT `rides_ibfk_1` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rides_ibfk_2` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`);

--
-- Contraintes pour la table `ride_bookings`
--
ALTER TABLE `ride_bookings`
  ADD CONSTRAINT `ride_bookings_ibfk_1` FOREIGN KEY (`ride_id`) REFERENCES `rides` (`ride_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ride_bookings_ibfk_2` FOREIGN KEY (`passenger_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `ride_messages`
--
ALTER TABLE `ride_messages`
  ADD CONSTRAINT `ride_messages_ibfk_1` FOREIGN KEY (`ride_id`) REFERENCES `rides` (`ride_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ride_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ride_messages_ibfk_3` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `ride_reviews`
--
ALTER TABLE `ride_reviews`
  ADD CONSTRAINT `ride_reviews_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `ride_bookings` (`booking_id`),
  ADD CONSTRAINT `ride_reviews_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ride_reviews_ibfk_3` FOREIGN KEY (`reviewed_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ride_reviews_ibfk_4` FOREIGN KEY (`ride_id`) REFERENCES `rides` (`ride_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
