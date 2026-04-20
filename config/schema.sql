-- Transport System Database Schema
-- Run this file in your MySQL/phpMyAdmin to set up the database

CREATE DATABASE IF NOT EXISTS transport_system CHARACTER SET utf8 COLLATE utf8_general_ci;
USE transport_system;

-- 1. Admin Table
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Locations Table
CREATE TABLE IF NOT EXISTS locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location_name VARCHAR(100) NOT NULL
);

-- 4. Pricing Table
CREATE TABLE IF NOT EXISTS pricing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pickup_location_id INT NOT NULL,
    dropoff_location_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (pickup_location_id) REFERENCES locations(id) ON DELETE CASCADE,
    FOREIGN KEY (dropoff_location_id) REFERENCES locations(id) ON DELETE CASCADE
);

-- 5. Bookings Table
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_name VARCHAR(150) NOT NULL,
    quantity_weight VARCHAR(100) NOT NULL,
    pickup_location_id INT NOT NULL,
    dropoff_location_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    status ENUM('pending','confirmed','in-transit','delivered','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (pickup_location_id) REFERENCES locations(id),
    FOREIGN KEY (dropoff_location_id) REFERENCES locations(id)
);

-- 6. Feedback Table
CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    booking_id INT NOT NULL,
    message TEXT NOT NULL,
    rating TINYINT(1) NOT NULL CHECK (rating BETWEEN 1 AND 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

-- Seed Locations
INSERT INTO locations (location_name) VALUES
('Nyabugogo'),
('Kabuga'),
('Kimironko'),
('Downtown'),
('Gisozi');

-- Seed Pricing (both directions)
INSERT INTO pricing (pickup_location_id, dropoff_location_id, price) VALUES
(1, 2, 15000), (2, 1, 15000),  -- Nyabugogo <-> Kabuga
(3, 2, 10000), (2, 3, 10000),  -- Kimironko <-> Kabuga
(1, 3, 5000),  (3, 1, 5000),   -- Nyabugogo <-> Kimironko
(1, 4, 500),   (4, 1, 500),    -- Nyabugogo <-> Downtown
(1, 5, 7000),  (5, 1, 7000),   -- Nyabugogo <-> Gisozi
(3, 5, 7000),  (5, 3, 7000);   -- Kimironko <-> Gisozi
