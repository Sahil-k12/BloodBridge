<?php
/**
 * Helper Functions
 * BloodBridge - Blood Donation & Emergency Assistance System
 * 
 * Contains reusable functions for common operations
 */

// ============================================
// SECURITY FUNCTIONS
// ============================================

/**
 * Sanitize user input
 * @param string $input User input
 * @return string Sanitized string
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Hash password
 * @param string $password Plain password
 * @return string Hashed password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
}

/**
 * Verify password
 * @param string $password Plain password
 * @param string $hash Hashed password
 * @return bool Password match status
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Validate email
 * @param string $email Email address
 * @return bool Valid email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate phone number
 * @param string $phone Phone number
 * @return bool Valid phone
 */
function validatePhone($phone) {
    return preg_match('/^[0-9\-\+\(\) ]{7,20}$/', $phone);
}

// ============================================
// USER FUNCTIONS
// ============================================

/**
 * Get user by ID
 * @param int $user_id User ID
 * @return array User data
 */
function getUserById($user_id) {
    global $conn;
    $stmt = $conn->prepare('SELECT * FROM users WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Get user by email
 * @param string $email Email address
 * @return array User data
 */
function getUserByEmail($email) {
    global $conn;
    $stmt = $conn->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Create new user
 * @param array $data User data
 * @return int New user ID
 */
function createUser($data) {
    global $conn;
    $stmt = $conn->prepare(
        'INSERT INTO users (full_name, email, password, role, city) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('sssss', $data['full_name'], $data['email'], $data['password'], $data['role'], $data['city']);
    
    if ($stmt->execute()) {
        return $conn->insert_id;
    }
    return false;
}

// ============================================
// DONOR FUNCTIONS
// ============================================

/**
 * Get donor by user ID
 * @param int $user_id User ID
 * @return array Donor data
 */
function getDonorByUserId($user_id) {
    global $conn;
    $stmt = $conn->prepare('SELECT * FROM donors WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Get all available donors
 * @return array Donors list
 */
function getAvailableDonors() {
    global $conn;
    $stmt = $conn->prepare(
        'SELECT d.*, u.full_name, u.city, u.email FROM donors d 
         JOIN users u ON d.user_id = u.user_id 
         WHERE d.availability_status = "Available"'
    );
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Search donors by blood group and city
 * @param string $blood_group Blood group
 * @param string $city City name
 * @return array Matching donors
 */
function searchDonors($blood_group, $city = null) {
    global $conn;
    
    if ($city) {
        $stmt = $conn->prepare(
            'SELECT d.*, u.full_name, u.city, u.email FROM donors d 
             JOIN users u ON d.user_id = u.user_id 
             WHERE d.blood_group = ? AND u.city LIKE ? AND d.availability_status = "Available"'
        );
        $city_search = '%' . $city . '%';
        $stmt->bind_param('ss', $blood_group, $city_search);
    } else {
        $stmt = $conn->prepare(
            'SELECT d.*, u.full_name, u.city, u.email FROM donors d 
             JOIN users u ON d.user_id = u.user_id 
             WHERE d.blood_group = ? AND d.availability_status = "Available"'
        );
        $stmt->bind_param('s', $blood_group);
    }
    
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Update donor availability
 * @param int $donor_id Donor ID
 * @param string $status Availability status
 * @return bool Success status
 */
function updateDonorAvailability($donor_id, $status) {
    global $conn;
    $stmt = $conn->prepare('UPDATE donors SET availability_status = ? WHERE donor_id = ?');
    $stmt->bind_param('si', $status, $donor_id);
    return $stmt->execute();
}

// ============================================
// BLOOD REQUEST FUNCTIONS
// ============================================

/**
 * Create blood request
 * @param array $data Request data
 * @return int New request ID
 */
function createBloodRequest($data) {
    global $conn;
    $stmt = $conn->prepare(
        'INSERT INTO blood_requests (requester_user_id, hospital_id, patient_name, blood_group, city, urgency_level, units_required, description) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param(
        'iissssis',
        $data['requester_user_id'],
        $data['hospital_id'],
        $data['patient_name'],
        $data['blood_group'],
        $data['city'],
        $data['urgency_level'],
        $data['units_required'],
        $data['description']
    );
    
    if ($stmt->execute()) {
        return $conn->insert_id;
    }
    return false;
}

/**
 * Get blood request by ID
 * @param int $request_id Request ID
 * @return array Request data
 */
function getBloodRequest($request_id) {
    global $conn;
    $stmt = $conn->prepare('SELECT * FROM blood_requests WHERE request_id = ?');
    $stmt->bind_param('i', $request_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Get pending blood requests
 * @return array Pending requests
 */
function getPendingRequests() {
    global $conn;
    $stmt = $conn->prepare(
        'SELECT br.*, u.full_name, u.city FROM blood_requests br 
         JOIN users u ON br.requester_user_id = u.user_id 
         WHERE br.status = "Pending" ORDER BY br.urgency_level DESC, br.request_date ASC'
    );
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Update request status
 * @param int $request_id Request ID
 * @param string $status New status
 * @return bool Success status
 */
function updateRequestStatus($request_id, $status) {
    global $conn;
    $stmt = $conn->prepare('UPDATE blood_requests SET status = ? WHERE request_id = ?');
    $stmt->bind_param('si', $status, $request_id);
    return $stmt->execute();
}

// ============================================
// STATISTICS FUNCTIONS
// ============================================

/**
 * Get system statistics
 * @return array Statistics data
 */
function getSystemStats() {
    global $conn;
    
    $stats = [];
    
    // Total users
    $result = $conn->query('SELECT COUNT(*) as count FROM users');
    $stats['total_users'] = $result->fetch_assoc()['count'];
    
    // Total donors
    $result = $conn->query('SELECT COUNT(*) as count FROM donors');
    $stats['total_donors'] = $result->fetch_assoc()['count'];
    
    // Available donors
    $result = $conn->query('SELECT COUNT(*) as count FROM donors WHERE availability_status = "Available"');
    $stats['available_donors'] = $result->fetch_assoc()['count'];
    
    // Total requests
    $result = $conn->query('SELECT COUNT(*) as count FROM blood_requests');
    $stats['total_requests'] = $result->fetch_assoc()['count'];
    
    // Pending requests
    $result = $conn->query('SELECT COUNT(*) as count FROM blood_requests WHERE status = "Pending"');
    $stats['pending_requests'] = $result->fetch_assoc()['count'];
    
    // Completed donations
    $result = $conn->query('SELECT COUNT(*) as count FROM donations WHERE status = "Completed"');
    $stats['completed_donations'] = $result->fetch_assoc()['count'];
    
    return $stats;
}

// ============================================
// LOGGING FUNCTIONS
// ============================================

/**
 * Log admin action
 * @param int $admin_id Admin ID
 * @param string $action Action description
 * @param array $details Additional details
 * @return bool Success status
 */
function logAdminAction($admin_id, $action, $details = null) {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $details_json = json_encode($details);
    
    $stmt = $conn->prepare(
        'INSERT INTO admin_logs (admin_id, action_performed, ip_address, details) VALUES (?, ?, ?, ?)'
    );
    $stmt->bind_param('isss', $admin_id, $action, $ip, $details_json);
    return $stmt->execute();
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

/**
 * Format date for display
 * @param string $date Date string
 * @return string Formatted date
 */
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

/**
 * Get urgency badge color
 * @param string $urgency Urgency level
 * @return string Badge color class
 */
function getUrgencyColor($urgency) {
    $colors = [
        'Low' => 'success',
        'Medium' => 'warning',
        'High' => 'danger',
        'Critical' => 'dark'
    ];
    return $colors[$urgency] ?? 'secondary';
}

/**
 * Get status badge color
 * @param string $status Status
 * @return string Badge color class
 */
function getStatusColor($status) {
    $colors = [
        'Pending' => 'warning',
        'Accepted' => 'info',
        'Completed' => 'success',
        'Cancelled' => 'danger',
        'Scheduled' => 'primary',
        'Available' => 'success',
        'Unavailable' => 'danger'
    ];
    return $colors[$status] ?? 'secondary';
}

?>
