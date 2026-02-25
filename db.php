<?php
function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception('.env file not found');
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
}



loadEnv(__DIR__ . '/.env');
$servername = $_ENV['DB_SERVERNAME'];
$username = $_ENV['DB_USERNAME'];
$password = $_ENV['DB_PASSWORD'];
$dbname = $_ENV['DB_NAME'];

$conn = new mysqli($servername, $username, $password);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "CREATE DATABASE IF NOT EXISTS " . $dbname;
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully or already exists.<br>";
} else {
    echo "Error creating database: " . $conn->error;
    exit;
}

$conn->close();

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection to database failed: " . $conn->connect_error);
}

echo "Connected to database successfully!<br>";

$sql = "CREATE TABLE IF NOT EXISTS testovaqi_table (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    create_time DATETIME DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql) === TRUE) {
    echo "Table created successfully or already exists.<br>";
} else {
    echo "Error creating table: " . $conn->error;
}

$sql = "ALTER TABLE testovaqi_table ADD COLUMN user_type VARCHAR(255) COMMENT '' AFTER `email`";
$conn->query($sql);

/*
$sql = "INSERT INTO testovaqi_table (name, email) VALUES ('John', 'john@example.com')";
$conn->query($sql);
*/

$username = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$userType = $_POST['user-type'] ?? '';
$sql = "INSERT INTO testovaqi_table (name, email, user_type) VALUES ('$username', '$email', '$userType')";
$conn->query($sql);




$result = $conn->query("SELECT * FROM testovaqi_table");
while($row = $result->fetch_assoc()) {
    echo $row['id'] . " " . $row['name'] . " " . $row['email'] . " " . $row['user_type'] . " " . $row['create_time'] . "<br>";
}


?>
