<?php
require_once __DIR__ . '/../scripts/config.php';

$conn = getDatabase();

// Create employees table
$sql_employees = "CREATE TABLE IF NOT EXISTS employees (
    employee_id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    hours_worked DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql_employees) === TRUE) {
    echo "Employees table created successfully\n";
} else {
    echo "Error creating employees table: " . $conn->error . "\n";
}

// Create attendance_logs table (renamed from testovaqi_table)
$sql_logs = "CREATE TABLE IF NOT EXISTS attendance_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    time_in TIME NOT NULL,
    time_out TIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_employee_date (employee_id, date),
    INDEX idx_date (date)
)";

if ($conn->query($sql_logs) === TRUE) {
    echo "Attendance logs table created successfully\n";
} else {
    echo "Error creating attendance logs table: " . $conn->error . "\n";
}

// Create admin_accounts table
$sql_admins = "CREATE TABLE IF NOT EXISTS admin_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
)";

if ($conn->query($sql_admins) === TRUE) {
    echo "Admin accounts table created successfully\n";
} else {
    echo "Error creating admin accounts table: " . $conn->error . "\n";
}

// Migrate existing data from testovaqi_table to attendance_logs if it exists
$result = $conn->query("SHOW TABLES LIKE 'testovaqi_table'");
if ($result->num_rows > 0) {
    echo "Migrating data from testovaqi_table to attendance_logs...\n";

    $migrate_sql = "INSERT INTO attendance_logs (employee_id, name, date, time_in, time_out)
                    SELECT employee_id, name, date, time_in, time_out FROM testovaqi_table";

    if ($conn->query($migrate_sql) === TRUE) {
        echo "Data migration completed successfully\n";

        // Optional: Drop the old table after migration
        // $conn->query("DROP TABLE testovaqi_table");
        // echo "Old table dropped\n";
    } else {
        echo "Error migrating data: " . $conn->error . "\n";
    }
}

// Insert default admin account if none exists
$check_admin = $conn->query("SELECT COUNT(*) as count FROM admin_accounts");
$row = $check_admin->fetch_assoc();
if ($row['count'] == 0) {
    $default_username = 'admin';
    $default_password = password_hash('admin123', PASSWORD_DEFAULT);

    $insert_admin = $conn->prepare("INSERT INTO admin_accounts (username, password_hash) VALUES (?, ?)");
    $insert_admin->bind_param("ss", $default_username, $default_password);

    if ($insert_admin->execute()) {
        echo "Default admin account created (username: admin, password: admin123)\n";
    } else {
        echo "Error creating default admin: " . $conn->error . "\n";
    }
    $insert_admin->close();
}

$conn->close();
echo "Database migration completed!\n";
?>