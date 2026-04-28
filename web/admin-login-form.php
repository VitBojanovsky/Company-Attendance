<?php
require_once 'scripts/config.php';
require_once 'scripts/csrf.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Username and password are required.';
        } else {
            if ($username === 'admin' && $password === 'admin123') {
                session_regenerate_id(true);
                $_SESSION['logged_in'] = true;
                $_SESSION['username'] = $username;
                $_SESSION['login_time'] = time();
                
                header('Location: admin-dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        }
    }
}

if ($_SESSION['logged_in'] ?? false) {
    header('Location: admin-dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body { max-width: 400px; margin: 50px auto; }
    </style>
</head>
<body>
    <div class="login-form">
        <h1>Admin Login</h1>
        
        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        
        <form method="post" action="admin-login-form.php">
            <?php echo getCSRFField(); ?>
            
            <label for="username">Username:</label><br>
            <input type="text" id="username" name="username" required 
                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            
            <label for="password">Password:</label><br>
            <input type="password" id="password" name="password" required>
            
            <input type="submit" value="Login">
        </form>
    </div>
    
    <br>
    <a href="index.html">Back to home</a>
</body>
</html>
