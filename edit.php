<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editovaci Stranka Pro Adminy</title>
</head>
<body>
    <h1>Editovaci Stranka Pro Adminy</h1>
    <p>Logged in as: <?php echo htmlspecialchars($_SESSION['admin_name']); ?></p>
    <form action="edit-db.php" method="post">
        <label for="id">ID:</label><br>
        <input type="text" id="id" name="id" required><br>
        <label for="name_to_update">Name:</label><br>
        <input type="text" id="name_to_update" name="name_to_update" required><br>
        <label for="email_to_update">Email:</label><br>
        <input type="email" id="email_to_update" name="email_to_update" required><br><br>
        <input type="submit" value="Update">
    </form>
    <br>
    <a href="logout.php">Logout</a>
</body>
</html>
