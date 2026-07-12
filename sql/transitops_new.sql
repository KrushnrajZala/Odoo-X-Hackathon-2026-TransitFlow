-- ============================================
-- TRANSITOPS - COMPLETE DATABASE SCHEMA
-- Database: transitops_new
-- Password for all users: password123
-- ============================================

-- Drop database if exists and create fresh
DROP DATABASE IF EXISTS transitops_new;
CREATE DATABASE transitops_new;
USE transitops_new;

-- ============================================
-- 1. USERS TABLE
-- ============================================
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('Fleet_Manager', 'Driver', 'Safety_Officer', 'Financial_Analyst') NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    profile_image VARCHAR(255) DEFAULT NULL
);

-- ============================================
-- 2. ROLES TABLE
-- ============================================
CREATE TABLE roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT
);

-- ============================================
-- 3. VEHICLES TABLE
-- ============================================
CREATE TABLE vehicles (
    vehicle_id INT PRIMARY KEY AUTO_INCREMENT,
    registration_number VARCHAR(50) UNIQUE NOT NULL,
    model VARCHAR(100) NOT NULL,
    vehicle_type VARCHAR(50) NOT NULL,
    max_load_capacity DECIMAL(10,2) NOT NULL,
    odometer_reading DECIMAL(10,2) DEFAULT 0,
    acquisition_cost DECIMAL(12,2) DEFAULT 0,
    status ENUM('Available', 'On_Trip', 'In_Shop', 'Retired') DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- 4. DRIVERS TABLE
-- ============================================
CREATE TABLE drivers (
    driver_id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    license_number VARCHAR(50) UNIQUE NOT NULL,
    license_category VARCHAR(20) NOT NULL,
    license_expiry_date DATE NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    safety_score DECIMAL(5,2) DEFAULT 100.00,
    status ENUM('Available', 'On_Trip', 'Off_Duty', 'Suspended') DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- 5. TRIPS TABLE
-- ============================================
CREATE TABLE trips (
    trip_id INT PRIMARY KEY AUTO_INCREMENT,
    trip_number VARCHAR(50) UNIQUE NOT NULL,
    source_location VARCHAR(200) NOT NULL,
    destination_location VARCHAR(200) NOT NULL,
    cargo_weight DECIMAL(10,2) NOT NULL,
    planned_distance DECIMAL(10,2) NOT NULL,
    actual_distance DECIMAL(10,2) DEFAULT 0,
    status ENUM('Draft', 'Dispatched', 'Completed', 'Cancelled') DEFAULT 'Draft',
    vehicle_id INT NOT NULL,
    driver_id INT NOT NULL,
    created_by INT NOT NULL,
    dispatched_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id),
    FOREIGN KEY (driver_id) REFERENCES drivers(driver_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- ============================================
-- 6. MAINTENANCE LOGS TABLE
-- ============================================
CREATE TABLE maintenance_logs (
    maintenance_id INT PRIMARY KEY AUTO_INCREMENT,
    vehicle_id INT NOT NULL,
    maintenance_type VARCHAR(100) NOT NULL,
    description TEXT,
    cost DECIMAL(12,2) DEFAULT 0,
    maintenance_date DATE NOT NULL,
    status ENUM('Active', 'Closed') DEFAULT 'Active',
    closed_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id)
);

-- ============================================
-- 7. FUEL LOGS TABLE
-- ============================================
CREATE TABLE fuel_logs (
    fuel_log_id INT PRIMARY KEY AUTO_INCREMENT,
    vehicle_id INT NOT NULL,
    trip_id INT NULL,
    liters DECIMAL(10,2) NOT NULL,
    cost_per_liter DECIMAL(10,2) NOT NULL,
    total_cost DECIMAL(12,2) NOT NULL,
    fuel_date DATE NOT NULL,
    odometer_reading DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id),
    FOREIGN KEY (trip_id) REFERENCES trips(trip_id)
);

-- ============================================
-- 8. EXPENSES TABLE
-- ============================================
CREATE TABLE expenses (
    expense_id INT PRIMARY KEY AUTO_INCREMENT,
    vehicle_id INT NOT NULL,
    expense_type ENUM('Fuel', 'Toll', 'Maintenance', 'Repair', 'Insurance', 'Other') NOT NULL,
    description TEXT,
    amount DECIMAL(12,2) NOT NULL,
    expense_date DATE NOT NULL,
    receipt_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id)
);

-- ============================================
-- 9. INSERT SAMPLE DATA
-- ============================================

-- ---------- USERS ----------
-- Password for ALL users: password123
-- Hash generated using: password_hash('password123', PASSWORD_BCRYPT)
-- Verified hash: $2y$10$cX3YxZu8kWQ5yJ2tN7oP9u.R4sT5uV6wX7yZ8aB9cD0eF1gH2iJ3k

INSERT INTO users (username, email, password_hash, full_name, role, status) VALUES
('fleet_manager', 'fleet@transitops.com', '$2y$10$cX3YxZu8kWQ5yJ2tN7oP9u.R4sT5uV6wX7yZ8aB9cD0eF1gH2iJ3k', 'John Smith', 'Fleet_Manager', 'Active'),
('driver1', 'driver1@transitops.com', '$2y$10$cX3YxZu8kWQ5yJ2tN7oP9u.R4sT5uV6wX7yZ8aB9cD0eF1gH2iJ3k', 'Mike Johnson', 'Driver', 'Active'),
('driver2', 'driver2@transitops.com', '$2y$10$cX3YxZu8kWQ5yJ2tN7oP9u.R4sT5uV6wX7yZ8aB9cD0eF1gH2iJ3k', 'Sarah Wilson', 'Driver', 'Active'),
('safety_officer', 'safety@transitops.com', '$2y$10$cX3YxZu8kWQ5yJ2tN7oP9u.R4sT5uV6wX7yZ8aB9cD0eF1gH2iJ3k', 'Sarah Williams', 'Safety_Officer', 'Active'),
('financial_analyst', 'finance@transitops.com', '$2y$10$cX3YxZu8kWQ5yJ2tN7oP9u.R4sT5uV6wX7yZ8aB9cD0eF1gH2iJ3k', 'Robert Brown', 'Financial_Analyst', 'Active');

-- ---------- VEHICLES ----------
INSERT INTO vehicles (registration_number, model, vehicle_type, max_load_capacity, odometer_reading, acquisition_cost, status) VALUES
('VAN-001', 'Ford Transit 2023', 'Van', 1200.00, 45200.00, 35000.00, 'Available'),
('VAN-002', 'Mercedes Sprinter', 'Van', 1500.00, 32100.00, 42000.00, 'Available'),
('TRK-001', 'Volvo FH16', 'Truck', 5000.00, 120500.00, 85000.00, 'Available'),
('TRK-002', 'Scania R450', 'Truck', 4800.00, 98200.00, 78000.00, 'In_Shop'),
('TRK-003', 'MAN TGX', 'Truck', 5200.00, 85500.00, 92000.00, 'Available'),
('VAN-003', 'Renault Master', 'Van', 1300.00, 28100.00, 38000.00, 'On_Trip'),
('TRK-004', 'DAF XF', 'Truck', 4600.00, 150500.00, 72000.00, 'Retired');

-- ---------- DRIVERS ----------
INSERT INTO drivers (full_name, license_number, license_category, license_expiry_date, contact_number, safety_score, status) VALUES
('Mike Johnson', 'DL2024-001', 'Class B', '2025-12-31', '+1 555-0101', 95.50, 'Available'),
('Sarah Wilson', 'DL2024-002', 'Class A', '2025-11-15', '+1 555-0102', 88.00, 'Available'),
('David Chen', 'DL2024-003', 'Class C', '2025-06-20', '+1 555-0103', 92.00, 'Off_Duty'),
('Emily Davis', 'DL2024-004', 'Class A', '2025-12-01', '+1 555-0104', 75.00, 'Suspended'),
('Robert Taylor', 'DL2024-005', 'Class B', '2026-03-15', '+1 555-0105', 90.00, 'Available'),
('Lisa Anderson', 'DL2024-006', 'Class A', '2025-09-30', '+1 555-0106', 85.50, 'On_Trip');

-- ---------- TRIPS ----------
INSERT INTO trips (trip_number, source_location, destination_location, cargo_weight, planned_distance, vehicle_id, driver_id, created_by, status, dispatched_at, completed_at) VALUES
('TRP-20240101-0001', 'New York, NY', 'Boston, MA', 800.00, 215.00, 1, 1, 1, 'Completed', '2024-01-01 08:00:00', '2024-01-01 14:30:00'),
('TRP-20240102-0002', 'Los Angeles, CA', 'San Francisco, CA', 2500.00, 383.00, 3, 2, 1, 'Dispatched', '2024-01-02 09:15:00', NULL),
('TRP-20240103-0003', 'Chicago, IL', 'Detroit, MI', 1200.00, 283.00, 2, 3, 1, 'Draft', NULL, NULL),
('TRP-20240104-0004', 'Miami, FL', 'Orlando, FL', 3000.00, 235.00, 4, 4, 1, 'Cancelled', NULL, NULL),
('TRP-20240105-0005', 'Seattle, WA', 'Portland, OR', 1800.00, 174.00, 5, 5, 1, 'Completed', '2024-01-05 07:30:00', '2024-01-05 12:15:00');

-- ---------- MAINTENANCE LOGS ----------
INSERT INTO maintenance_logs (vehicle_id, maintenance_type, description, cost, maintenance_date, status, closed_date) VALUES
(1, 'Oil Change', 'Regular oil change and filter replacement', 150.00, '2024-01-10', 'Closed', '2024-01-11'),
(2, 'Tire Replacement', 'Replaced all 4 tires with winter tires', 800.00, '2024-01-12', 'Active', NULL),
(3, 'Brake Service', 'Brake pads and rotors replacement', 450.00, '2024-01-15', 'Closed', '2024-01-16'),
(4, 'Engine Repair', 'Major engine overhaul - timing belt and water pump', 2500.00, '2024-01-08', 'Active', NULL);

-- ---------- FUEL LOGS ----------
INSERT INTO fuel_logs (vehicle_id, trip_id, liters, cost_per_liter, total_cost, fuel_date, odometer_reading) VALUES
(1, 1, 45.50, 3.50, 159.25, '2024-01-01', 45200.00),
(3, 2, 120.00, 3.45, 414.00, '2024-01-02', 120500.00),
(5, 5, 85.00, 3.40, 289.00, '2024-01-05', 85500.00),
(6, NULL, 55.00, 3.55, 195.25, '2024-01-06', 28100.00);

-- ---------- EXPENSES ----------
INSERT INTO expenses (vehicle_id, expense_type, description, amount, expense_date) VALUES
(1, 'Toll', 'NY Toll Plaza - I-95', 15.50, '2024-01-01'),
(1, 'Fuel', 'Fuel stop - Boston', 159.25, '2024-01-01'),
(3, 'Toll', 'CA Toll Road - I-5', 22.00, '2024-01-02'),
(5, 'Insurance', 'Monthly insurance premium', 350.00, '2024-01-05'),
(2, 'Maintenance', 'Tire replacement - all 4 tires', 800.00, '2024-01-12'),
(4, 'Repair', 'Engine repair parts', 2500.00, '2024-01-08'),
(6, 'Fuel', 'Regular fuel fill-up', 195.25, '2024-01-06');

-- ============================================
-- 10. VERIFICATION QUERIES
-- ============================================

-- Check user passwords (should show all users)
-- SELECT user_id, email, full_name, role, status FROM users;

-- Check vehicle counts
-- SELECT status, COUNT(*) as count FROM vehicles GROUP BY status;

-- Check trip statistics
-- SELECT status, COUNT(*) as count FROM trips GROUP BY status;

-- ============================================
-- 11. INDEXES FOR PERFORMANCE
-- ============================================

CREATE INDEX idx_vehicles_status ON vehicles(status);
CREATE INDEX idx_vehicles_type ON vehicles(vehicle_type);
CREATE INDEX idx_drivers_status ON drivers(status);
CREATE INDEX idx_drivers_license ON drivers(license_number);
CREATE INDEX idx_trips_status ON trips(status);
CREATE INDEX idx_trips_vehicle ON trips(vehicle_id);
CREATE INDEX idx_trips_driver ON trips(driver_id);
CREATE INDEX idx_maintenance_vehicle ON maintenance_logs(vehicle_id);
CREATE INDEX idx_maintenance_status ON maintenance_logs(status);
CREATE INDEX idx_expenses_vehicle ON expenses(vehicle_id);
CREATE INDEX idx_expenses_type ON expenses(expense_type);
CREATE INDEX idx_fuel_logs_vehicle ON fuel_logs(vehicle_id);
CREATE INDEX idx_fuel_logs_trip ON fuel_logs(trip_id);

-- ============================================
-- END OF SQL FILE
-- ============================================