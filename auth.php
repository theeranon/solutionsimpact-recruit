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
        $cookie_parts = explode('|', $_COOKIE['si_recruit_auth']);
        if (count($cookie_parts) === 2) {
            $username = $cookie_parts[0];
            $cookie_hash = $cookie_parts[1];
            
            $users = APP_USERS;
            $username_lower = strtolower($username);
            $users_lower = [];
            foreach ($users as $k => $v) {
                $users_lower[strtolower($k)] = $k;
            }
            
            if (isset($users_lower[$username_lower])) {
                $real_username = $users_lower[$username_lower];
                $expected_val = hash('sha256', $real_username . SECRET_KEY);
                if (hash_equals($expected_val, $cookie_hash)) {
                    $_SESSION['logged_in'] = true;
                    $_SESSION['username'] = $real_username;
                    return true;
                }
            }
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
