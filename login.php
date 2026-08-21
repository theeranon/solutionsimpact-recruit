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
    
    if ($username === APP_USERNAME && $password === APP_PASSWORD) {
        $_SESSION['logged_in'] = true;
        
        // Set cookie forever (10 years)
        $cookie_val = hash('sha256', APP_USERNAME . SECRET_KEY);
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
