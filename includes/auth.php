<?php
/**
 * Authentication Functions
 * BloodBridge - Blood Donation & Emergency Assistance System
 * 
 * Handles user registration, login, and session management
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// AUTHENTICATION CORE FUNCTIONS
// ============================================

/**
 * Register new user
 * @param array $data Registration data
 * @return array Response with status and message
 */
function registerUser($data) {
    global $conn;
    
    // Validate input
    if (empty($data['full_name']) || empty($data['email']) || empty($data['password']) || 
        empty($data['role']) || empty($data['city'])) {
        return ['success' => false, 'message' => 'All fields are required'];
    }
    
    // Sanitize input
    $full_name = sanitize($data['full_name']);
    $email = sanitize($data['email']);
    $password = $data['password'];
    $role = sanitize($data['role']);
    $city = sanitize($data['city']);
    
    // Validate email
    if (!validateEmail($email)) {
        return ['success' => false, 'message' => 'Invalid email address'];
    }
    
    // Validate password strength (minimum 6 characters)
    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters'];
    }
    
    // Validate role
    $allowed_roles = ['donor', 'patient', 'hospital', 'admin'];
    if (!in_array($role, $allowed_roles)) {
        return ['success' => false, 'message' => 'Invalid role'];
    }
    
    // Check if email already exists
    $stmt = $conn->prepare('SELECT user_id FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => 'Email already registered'];
    }
    
    // Hash password
    $hashed_password = hashPassword($password);
    
    // Create user
    $stmt = $conn->prepare(
        'INSERT INTO users (full_name, email, password, role, city) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('sssss', $full_name, $email, $hashed_password, $role, $city);
    
    if (!$stmt->execute()) {
        return ['success' => false, 'message' => 'Registration failed: ' . $conn->error];
    }
    
    $user_id = $conn->insert_id;
    
    // Create role-specific records
    if ($role === 'donor') {
        $blood_group = sanitize($data['blood_group'] ?? 'O+');
        $phone = sanitize($data['phone'] ?? '');
        
        if (!validatePhone($phone)) {
            return ['success' => false, 'message' => 'Invalid phone number'];
        }
        
        $stmt = $conn->prepare(
            'INSERT INTO donors (user_id, blood_group, phone) VALUES (?, ?, ?)'
        );
        $stmt->bind_param('iss', $user_id, $blood_group, $phone);
        $stmt->execute();
    } elseif ($role === 'hospital') {
        $hospital_name = sanitize($data['hospital_name'] ?? $full_name);
        $address = sanitize($data['address'] ?? '');
        $contact_number = sanitize($data['contact_number'] ?? '');
        
        if (!validatePhone($contact_number)) {
            return ['success' => false, 'message' => 'Invalid contact number'];
        }
        
        $stmt = $conn->prepare(
            'INSERT INTO hospitals (user_id, hospital_name, address, contact_number) VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('isss', $user_id, $hospital_name, $address, $contact_number);
        $stmt->execute();
    }
    
    return ['success' => true, 'message' => 'Registration successful', 'user_id' => $user_id];
}

/**
 * Login user
 * @param string $email User email
 * @param string $password User password
 * @return array Response with status and message
 */
function loginUser($email, $password) {
    global $conn;
    
    // Validate input
    if (empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Email and password are required'];
    }
    
    // Sanitize email
    $email = sanitize($email);
    
    // Get user
    $stmt = $conn->prepare('SELECT user_id, full_name, email, password, role FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
    
    $user = $result->fetch_assoc();
    
    // Verify password
    if (!verifyPassword($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
    
    // Set session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['login_time'] = time();
    
    return ['success' => true, 'message' => 'Login successful', 'role' => $user['role']];
}

/**
 * Logout user
 * @return bool Success status
 */
function logoutUser() {
    $_SESSION = [];
    session_destroy();
    return true;
}

/**
 * Check if user is logged in
 * @return bool Login status
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get current user ID
 * @return int|null User ID or null
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 * @return string|null User role or null
 */
function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Check user role
 * @param string|array $role Required role(s)
 * @return bool Role match status
 */
function checkRole($role) {
    if (!isLoggedIn()) {
        return false;
    }
    
    if (is_array($role)) {
        return in_array($_SESSION['role'], $role);
    }
    
    return $_SESSION['role'] === $role;
}

/**
 * Require login
 * Redirects to login if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit();
    }
}

/**
 * Require specific role
 * @param string|array $role Required role(s)
 */
function requireRole($role) {
    requireLogin();
    
    if (!checkRole($role)) {
        die('Access denied. Insufficient permissions.');
    }
}

// ============================================
// SESSION SECURITY
// ============================================

/**
 * Regenerate session ID (security best practice)
 */
function regenerateSession() {
    if (isLoggedIn()) {
        session_regenerate_id(true);
    }
}

/**
 * Check session timeout
 * @param int $timeout Timeout in seconds (default: 3600 = 1 hour)
 * @return bool Timeout status
 */
function checkSessionTimeout($timeout = 3600) {
    if (!isLoggedIn()) {
        return false;
    }
    
    if (time() - $_SESSION['login_time'] > $timeout) {
        logoutUser();
        return true; // Session expired
    }
    
    return false; // Session valid
}

?>
