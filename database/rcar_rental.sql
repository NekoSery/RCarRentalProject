-- RCar Rental Database Schema
-- For Laragon/MySQL

CREATE DATABASE IF NOT EXISTS rcar_rental CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rcar_rental;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    license_number VARCHAR(50) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    role ENUM('customer', 'admin') DEFAULT 'customer',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Cars/Vehicles table
CREATE TABLE IF NOT EXISTS cars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    brand VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    type ENUM('sedan', 'suv', 'luxury', 'electric', 'hatchback', 'mpv') NOT NULL,
    price_per_day DECIMAL(10,2) NOT NULL,
    price_per_hour DECIMAL(10,2) DEFAULT NULL,
    year INT NOT NULL,
    seats INT NOT NULL DEFAULT 5,
    features TEXT,
    image VARCHAR(50) DEFAULT 'fa-car',
    image_exterior VARCHAR(255) DEFAULT NULL,
    image_interior VARCHAR(255) DEFAULT NULL,
    available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bookings table
CREATE TABLE IF NOT EXISTS bookings (
    id VARCHAR(20) PRIMARY KEY,
    user_id INT NOT NULL,
    car_id INT NOT NULL,
    rental_type ENUM('hourly', 'daily') DEFAULT 'daily',
    hours INT DEFAULT NULL,
    pickup_date DATE NOT NULL,
    return_date DATE NOT NULL,
    pickup_time TIME DEFAULT NULL,
    return_time TIME DEFAULT NULL,
    location VARCHAR(100) NOT NULL,
    status ENUM('pending', 'active', 'completed', 'cancelled') DEFAULT 'pending',
    total_amount DECIMAL(10,2) NOT NULL,
    insurance BOOLEAN DEFAULT FALSE,
    gps BOOLEAN DEFAULT FALSE,
    child_seat BOOLEAN DEFAULT FALSE,
    touch_n_go BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE
);

-- Insert default admin user (password: admin)
INSERT INTO users (name, email, password, license_number, phone, role) VALUES
('Admin User', 'admin@rcar.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ADM001', '+60 12-345 6789', 'admin');

-- Insert default customer user (password: password)
INSERT INTO users (name, email, password, license_number, phone, role) VALUES
('John Doe', 'user@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DL123456', '+60 11-234 5678', 'customer');

-- Insert sample cars
INSERT INTO cars (brand, model, type, price_per_day, price_per_hour, year, seats, features, image, available) VALUES
('Perodua', 'Bezza', 'sedan', 80.00, NULL, 2024, 5, 'Bluetooth,Reverse Camera,Fuel Efficient,Touchscreen', 'fa-car', TRUE),
('Proton', 'X50', 'suv', 150.00, NULL, 2024, 5, 'ADAS,Sunroof,Apple CarPlay,Android Auto,Leather Seats', 'fa-truck', TRUE),
('Toyota', 'Vellfire', 'mpv', 450.00, NULL, 2023, 7, 'Power Doors,Premium Leather,Rear Entertainment,Climate Control', 'fa-shuttle-van', TRUE),
('Tesla', 'Model 3', 'electric', 350.00, 45.00, 2024, 5, 'Autopilot,Full Self-Driving,Glass Roof,Premium Interior', 'fa-bolt', TRUE),
('Honda', 'City', 'sedan', 120.00, NULL, 2024, 5, 'Honda Sensing,Lane Watch,Eco Mode,Spacious Boot', 'fa-car', TRUE),
('Perodua', 'Alza', 'mpv', 130.00, NULL, 2024, 7, 'Foldable Seats,USB Ports,Dual Airbags,ABS', 'fa-shuttle-van', TRUE),
('BMW', 'X5', 'luxury', 550.00, NULL, 2024, 5, 'Panoramic Roof,Harmon Kardon Sound,Massage Seats,Heads Up Display', 'fa-car-side', TRUE),
('Mercedes', 'C-Class', 'luxury', 480.00, NULL, 2024, 5, 'Ambient Lighting,Burmester Sound,Air Suspension,Keyless Entry', 'fa-car-side', TRUE);

-- Insert sample bookings
INSERT INTO bookings (id, user_id, car_id, rental_type, hours, pickup_date, return_date, pickup_time, return_time, location, status, total_amount, insurance, gps, child_seat) VALUES
('BK001', 2, 1, 'daily', NULL, '2026-04-15', '2026-04-18', NULL, NULL, 'kuala-lumpur', 'active', 264.00, TRUE, FALSE, FALSE),
('BK002', 2, 4, 'daily', NULL, '2026-03-15', '2026-03-18', NULL, NULL, 'klia', 'completed', 1155.00, TRUE, TRUE, FALSE),
('BK003', 2, 2, 'daily', NULL, '2026-05-01', '2026-05-05', NULL, NULL, 'petaling-jaya', 'pending', 660.00, TRUE, TRUE, TRUE);
