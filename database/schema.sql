-- BloodBridge Database Schema
-- Blood Donation & Emergency Assistance System
-- Created: May 2026

CREATE DATABASE IF NOT EXISTS bloodbridge;
USE bloodbridge;

-- ============================================
-- USERS TABLE (Core user data)
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('donor', 'patient', 'hospital', 'admin') NOT NULL,
    city VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_city (city)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DONORS TABLE (Donor-specific information)
-- ============================================
CREATE TABLE IF NOT EXISTS donors (
    donor_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    blood_group ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
    phone VARCHAR(20) NOT NULL,
    availability_status ENUM('Available', 'Unavailable') DEFAULT 'Available',
    last_donation_date DATE,
    total_donations INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_blood_group (blood_group),
    INDEX idx_availability (availability_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- HOSPITALS TABLE (Hospital-specific info)
-- ============================================
CREATE TABLE IF NOT EXISTS hospitals (
    hospital_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    hospital_name VARCHAR(150) NOT NULL,
    address TEXT NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    website VARCHAR(255),
    bed_capacity INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_hospital_name (hospital_name),
    INDEX idx_city (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- BLOOD_REQUESTS TABLE (Emergency requests)
-- ============================================
CREATE TABLE IF NOT EXISTS blood_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    requester_user_id INT NOT NULL,
    hospital_id INT,
    patient_name VARCHAR(100) NOT NULL,
    blood_group ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
    city VARCHAR(100) NOT NULL,
    urgency_level ENUM('Low','Medium','High','Critical') DEFAULT 'Medium',
    units_required INT DEFAULT 1,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Pending','Accepted','Completed','Cancelled') DEFAULT 'Pending',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (requester_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(hospital_id) ON DELETE SET NULL,
    INDEX idx_blood_group (blood_group),
    INDEX idx_status (status),
    INDEX idx_urgency (urgency_level),
    INDEX idx_request_date (request_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DONATIONS TABLE (Donation tracking)
-- ============================================
CREATE TABLE IF NOT EXISTS donations (
    donation_id INT AUTO_INCREMENT PRIMARY KEY,
    donor_id INT NOT NULL,
    request_id INT NOT NULL,
    donation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Scheduled','Completed','Cancelled') DEFAULT 'Scheduled',
    units_donated INT DEFAULT 1,
    location VARCHAR(255),
    FOREIGN KEY (donor_id) REFERENCES donors(donor_id) ON DELETE CASCADE,
    FOREIGN KEY (request_id) REFERENCES blood_requests(request_id) ON DELETE CASCADE,
    INDEX idx_donor_id (donor_id),
    INDEX idx_request_id (request_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ADMIN_LOGS TABLE (Activity tracking)
-- ============================================
CREATE TABLE IF NOT EXISTS admin_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action_performed TEXT NOT NULL,
    action_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    details JSON,
    FOREIGN KEY (admin_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_admin_id (admin_id),
    INDEX idx_action_time (action_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERT SAMPLE DATA
-- ============================================

-- Sample Users
INSERT INTO users (full_name, email, password, role, city) VALUES
('Admin User', 'admin@bloodbridge.com', '$2y$10$YourHashedPasswordHere', 'admin', 'New York'),
('John Donor', 'john.donor@example.com', '$2y$10$YourHashedPasswordHere', 'donor', 'New York'),
('Jane Patient', 'jane.patient@example.com', '$2y$10$YourHashedPasswordHere', 'patient', 'New York'),
('Sarah Donor', 'sarah.donor@example.com', '$2y$10$YourHashedPasswordHere', 'donor', 'Los Angeles'),
('City Hospital', 'info@cityhospital.com', '$2y$10$YourHashedPasswordHere', 'hospital', 'New York'),
('Medical Center', 'info@medicalcenter.com', '$2y$10$YourHashedPasswordHere', 'hospital', 'Los Angeles');

-- Sample Donors
INSERT INTO donors (user_id, blood_group, phone, availability_status, total_donations) VALUES
(2, 'O+', '5551234567', 'Available', 5),
(4, 'AB-', '5559876543', 'Unavailable', 3);

-- Sample Hospitals
INSERT INTO hospitals (user_id, hospital_name, address, contact_number, bed_capacity) VALUES
(5, 'City Hospital', '123 Main St, New York, NY', '5551000000', 300),
(6, 'Medical Center', '456 Oak Ave, Los Angeles, CA', '2135000000', 250);

-- Sample Blood Requests
INSERT INTO blood_requests (requester_user_id, hospital_id, patient_name, blood_group, city, urgency_level, units_required, status, description) VALUES
(5, 1, 'Robert Smith', 'O+', 'New York', 'Critical', 3, 'Pending', 'Emergency surgery required'),
(6, 2, 'Maria Garcia', 'A+', 'Los Angeles', 'High', 2, 'Pending', 'Accident victim');

-- ============================================
-- CREATE INDEXES FOR OPTIMIZATION
-- ============================================

CREATE INDEX idx_user_role ON users(role);
CREATE INDEX idx_donor_user ON donors(user_id);
CREATE INDEX idx_hospital_user ON hospitals(user_id);
CREATE INDEX idx_request_status ON blood_requests(status);
CREATE INDEX idx_donation_donor ON donations(donor_id);

-- ============================================
-- END OF SCHEMA
-- ============================================
