<?php
require_once 'auth.php';

$error = '';

if (isAuthenticated()) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $users = APP_USERS;
    $username_lower = strtolower($username);
    
    // Create a lowercase mapped array
    $users_lower = [];
    foreach ($users as $k => $v) {
        $users_lower[strtolower($k)] = [
            'original_username' => $k,
            'password' => $v
        ];
    }
    
    if (isset($users_lower[$username_lower]) && $users_lower[$username_lower]['password'] === $password) {
        $real_username = $users_lower[$username_lower]['original_username'];
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $real_username;
        
        $cookie_val = $real_username . '|' . hash('sha256', $real_username . SECRET_KEY);
        setcookie('si_recruit_auth', $cookie_val, time() + (86400 * 365 * 10), "/");
        
        header("Location: index.php");
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SolutionsIMPACT Recruit</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">
    <div class="login-container">
        <h2>SolutionsIMPACT<br>Recruit</h2>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
    </div>
</body>
</html>
