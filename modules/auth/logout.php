<?php
session_start();

// Function to clear session completely
function logoutUser() {
    global $pdo; // If you need database access
    
    // Unset all session variables
    $_SESSION = array();
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Clear remember me token from database if user was logged in
    if (isset($_SESSION['user']['id'])) {
        try {
            require_once __DIR__ . '/../../app/config/database.php';
            $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
            $stmt->execute([$_SESSION['user']['id']]);
        } catch (Exception $e) {
            // Log error but don't stop logout process
            error_log("Logout database error: " . $e->getMessage());
        }
    }
    
    // Clear remember me cookie
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    }
    
    // Clear any other cookies you might have set
    // setcookie('other_cookie', '', time() - 3600, '/');
}

// Handle AJAX logout request
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    
    logoutUser();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
    exit;
}

// Handle normal logout
logoutUser();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Redirect to login page
header("Location: login.php");
exit;
?>