<?php
// admin/logout.php - Admin logout
session_start();
require_once 'config.php';

// Log the logout action if admin was logged in
if (isAdminLoggedIn()) {
    logAdminActivity('admin_logout', 'employee', $_SESSION[ADMIN_SESSION_PREFIX . 'id'], 'Admin logged out');
}

// Clear admin session variables
unset($_SESSION[ADMIN_SESSION_PREFIX . 'id']);
unset($_SESSION[ADMIN_SESSION_PREFIX . 'name']);
unset($_SESSION[ADMIN_SESSION_PREFIX . 'role']);
unset($_SESSION[ADMIN_SESSION_PREFIX . 'email']);

// Clear CSRF token
unset($_SESSION['csrf_token']);

// Destroy session if no other data exists
if (empty($_SESSION)) {
    session_destroy();
}

// Redirect to admin login
header('Location: login.php');
exit;
?>