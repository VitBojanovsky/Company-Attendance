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

try {
    loadEnv(__DIR__ . '/.env');
    $servername = $_ENV['DB_SERVERNAME'];
    $username = $_ENV['DB_USERNAME'];
    $password = $_ENV['DB_PASSWORD'];
    $dbname = $_ENV['DB_NAME'];
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection to database failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Error loading environment variables: " . $e->getMessage());
}

$sql = "CREATE DATABASE IF NOT EXISTS " . $dbname;
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully or already exists.<br>";
} else {
    echo "Error creating database: " . $conn->error;
    exit;
}

echo "Connected to database successfully!<br>";

$employee_id = $_POST['employee_id'] ?? '';
$name = $_POST['name'] ?? '';
$date = $_POST['date'] ?? '';
$time_in = $_POST['time_in'] ?? '';
$time_out = $_POST['time_out'] ?? '';
$sql = "INSERT INTO testovaqi_table (employee_id, name, date, time_in, time_out) VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("issss", $employee_id, $name, $date, $time_in, $time_out);

if ($stmt->execute()) {
    echo "Dochazka zaznamenana";
    echo "<br><a href='index.html'>Zpet na hlavni stranku</a>";
    echo "<br><a href='zobrazit.php'>Zobrazit prehled dochazky</a>";
} else {
    echo "Error inserting data: " . $stmt->error;
}

$stmt->close();
$conn->close();
