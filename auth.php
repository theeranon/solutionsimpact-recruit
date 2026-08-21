<?php
session_start();
require_once 'config.php';

// Check if user is logged in
function isAuthenticated() {
    // Check session first
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        return true;
    }
    
    // Check cookie (Remember Forever)
    if (isset($_COOKIE['si_recruit_auth'])) {
        $cookie_val = $_COOKIE['si_recruit_auth'];
        $expected_val = hash('sha256', APP_USERNAME . SECRET_KEY);
        
        if (hash_equals($expected_val, $cookie_val)) {
            $_SESSION['logged_in'] = true;
            return true;
        }
    }
    
    return false;
}

// Require login to access a page
function requireLogin() {
    if (!isAuthenticated()) {
        header("Location: login.php");
        exit;
    }
}
?>
