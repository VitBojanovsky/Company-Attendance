<?php
require_once 'scripts/config.php';
require_once 'scripts/csrf.php';

if (!($_SESSION['logged_in'] ?? false)) {
    header('Location: admin-login-form.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Admin Dashboard</h1>
    <p>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!</p>
    
    <h2>Execute SQL Query</h2>
    <p><strong>Note:</strong> Only SELECT, SHOW, and DESCRIBE queries are allowed.</p>
    
    <form method="post" action="execute.php">
        <?php echo getCSRFField(); ?>
        <input type="text" name="sql_query" placeholder="Example: SELECT * FROM testovaqi_table" required>
        <input type="submit" value="Execute Query">
    </form>
    
    <div class="actions">
        <h3>Quick Links:</h3>
        <a href="zobrazit.php">View All Attendance</a>
        <a href="logout.php" class="logout">Logout</a>
    </div>
</body>
</html>
