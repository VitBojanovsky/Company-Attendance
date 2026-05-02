<?php
require_once 'scripts/config.php';
require_once 'scripts/csrf.php';

if (!($_SESSION['logged_in'] ?? false)) {
    header('Location: admin-login-form.php');
    exit;
}

$conn = getDatabase();
$error = '';
$success = '';
$action = $_GET['action'] ?? 'employees';
$tab = $_GET['tab'] ?? 'employees';
$records = [];
$totalRecords = 0;
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $post_action = $_POST['action'] ?? '';

        if ($post_action === 'add_employee') {
            $employee_id = trim($_POST['employee_id'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $hours_worked = floatval($_POST['hours_worked'] ?? 0);

            if (!$employee_id || !$name) {
                $error = 'Employee ID and name are required.';
            } else {
                $sql = "INSERT INTO employees (employee_id, name, hours_worked) VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE name = VALUES(name), hours_worked = VALUES(hours_worked)";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("ssd", $employee_id, $name, $hours_worked);
                    if ($stmt->execute()) {
                        $success = 'Employee added/updated successfully!';
                    } else {
                        $error = 'Failed to add employee: ' . $conn->error;
                    }
                    $stmt->close();
                }
            }
        } elseif ($post_action === 'update_employee') {
            $old_employee_id = $_POST['old_employee_id'] ?? '';
            $employee_id = trim($_POST['employee_id'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $hours_worked = floatval($_POST['hours_worked'] ?? 0);

            if (!$employee_id || !$name) {
                $error = 'Employee ID and name are required.';
            } else {
                $sql = "UPDATE employees SET employee_id = ?, name = ?, hours_worked = ? WHERE employee_id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("ssds", $employee_id, $name, $hours_worked, $old_employee_id);
                    if ($stmt->execute()) {
                        $success = 'Employee updated successfully!';
                    } else {
                        $error = 'Failed to update employee: ' . $conn->error;
                    }
                    $stmt->close();
                }
            }
        } elseif ($post_action === 'delete_employee') {
            $employee_id = $_POST['employee_id'] ?? '';

            if (!$employee_id) {
                $error = 'Invalid employee ID.';
            } else {
                $sql = "DELETE FROM employees WHERE employee_id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("s", $employee_id);
                    if ($stmt->execute()) {
                        $success = 'Employee deleted successfully!';
                    } else {
                        $error = 'Failed to delete employee: ' . $conn->error;
                    }
                    $stmt->close();
                }
            }
        } elseif ($post_action === 'add_log') {
            $employee_id = trim($_POST['employee_id'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $date = trim($_POST['date'] ?? '');
            $time_in = trim($_POST['time_in'] ?? '');
            $time_out = trim($_POST['time_out'] ?? '');

            if (!$employee_id || !$name || !$date || !$time_in) {
                $error = 'Please fill in all required fields.';
            } else {
                $sql = "INSERT INTO attendance_logs (employee_id, name, date, time_in, time_out) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("sssss", $employee_id, $name, $date, $time_in, $time_out);
                    if ($stmt->execute()) {
                        $success = 'Attendance log added successfully!';
                    } else {
                        $error = 'Failed to add log: ' . $conn->error;
                    }
                    $stmt->close();
                }
            }
        } elseif ($post_action === 'update_log') {
            $id = intval($_POST['id'] ?? 0);
            $employee_id = trim($_POST['employee_id'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $date = trim($_POST['date'] ?? '');
            $time_in = trim($_POST['time_in'] ?? '');
            $time_out = trim($_POST['time_out'] ?? '');

            if (!$id || !$employee_id || !$name || !$date || !$time_in) {
                $error = 'Please fill in all required fields.';
            } else {
                $sql = "UPDATE attendance_logs SET employee_id = ?, name = ?, date = ?, time_in = ?, time_out = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("sssssi", $employee_id, $name, $date, $time_in, $time_out, $id);
                    if ($stmt->execute()) {
                        $success = 'Log updated successfully!';
                    } else {
                        $error = 'Failed to update log: ' . $conn->error;
                    }
                    $stmt->close();
                }
            }
        } elseif ($post_action === 'delete_log') {
            $id = intval($_POST['id'] ?? 0);

            if (!$id) {
                $error = 'Invalid log ID.';
            } else {
                $sql = "DELETE FROM attendance_logs WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("i", $id);
                    if ($stmt->execute()) {
                        $success = 'Log deleted successfully!';
                    } else {
                        $error = 'Failed to delete log: ' . $conn->error;
                    }
                    $stmt->close();
                }
            }
        } elseif ($post_action === 'add_admin') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (!$username || !$password) {
                $error = 'Username and password are required.';
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters long.';
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO admin_accounts (username, password_hash) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("ss", $username, $password_hash);
                    if ($stmt->execute()) {
                        $success = 'Admin account created successfully!';
                    } else {
                        $error = 'Failed to create admin account: ' . $conn->error;
                    }
                    $stmt->close();
                }
            }
        } elseif ($post_action === 'delete_admin') {
            $id = intval($_POST['id'] ?? 0);

            if (!$id) {
                $error = 'Invalid admin ID.';
            } else {
                $sql = "DELETE FROM admin_accounts WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("i", $id);
                    if ($stmt->execute()) {
                        $success = 'Admin account deleted successfully!';
                    } else {
                        $error = 'Failed to delete admin account: ' . $conn->error;
                    }
                    $stmt->close();
                }
            }
        }
    }
}

// Load data based on current tab
if ($tab === 'employees') {
    if ($action === 'edit_employee') {
        $employee_id = $_GET['employee_id'] ?? '';
        if ($employee_id) {
            $sql = "SELECT * FROM employees WHERE employee_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $employee_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $editRecord = $result->fetch_assoc();
            $stmt->close();
        }
    } else {
        $countResult = $conn->query("SELECT COUNT(*) as total FROM employees");
        $countRow = $countResult->fetch_assoc();
        $totalRecords = $countRow['total'];
        $totalPages = max(1, ceil($totalRecords / $limit));

        $sql = "SELECT * FROM employees ORDER BY employee_id LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $records = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
} elseif ($tab === 'logs') {
    if ($action === 'edit_log') {
        $id = intval($_GET['id'] ?? 0);
        if ($id) {
            $sql = "SELECT * FROM attendance_logs WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $editRecord = $result->fetch_assoc();
            $stmt->close();
        }
    } else {
        $countResult = $conn->query("SELECT COUNT(*) as total FROM attendance_logs");
        $countRow = $countResult->fetch_assoc();
        $totalRecords = $countRow['total'];
        $totalPages = max(1, ceil($totalRecords / $limit));

        $sql = "SELECT * FROM attendance_logs ORDER BY date DESC, time_in DESC LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $records = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
} elseif ($tab === 'admins') {
    $countResult = $conn->query("SELECT COUNT(*) as total FROM admin_accounts");
    $countRow = $countResult->fetch_assoc();
    $totalRecords = $countRow['total'];
    $totalPages = max(1, ceil($totalRecords / $limit));

    $sql = "SELECT id, username, created_at, last_login FROM admin_accounts ORDER BY username LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $records = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$editRecord = $editRecord ?? null;
?>
                } else {
                    $error = 'Database error: ' . $conn->error;
                }
            }
        }
    }
}

// Load records for list view
if ($action === 'list' || $action === '') {
    $countResult = $conn->query("SELECT COUNT(*) as total FROM testovaqi_table");
    $countRow = $countResult->fetch_assoc();
    $totalRecords = $countRow['total'];
    $totalPages = max(1, ceil($totalRecords / $limit));
    
    if ($page > $totalPages) {
        $page = $totalPages;
        $offset = ($page - 1) * $limit;
    }
    
    $sql = "SELECT employee_id, name, date, time_in, time_out 
            FROM testovaqi_table 
            ORDER BY date DESC, time_in DESC 
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $records = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Load single record for edit
$editRecord = null;
if ($action === 'edit') {
    $employee_id = $_GET['employee_id'] ?? '';
    $date = $_GET['date'] ?? '';
    $time_in = $_GET['time_in'] ?? '';
    
    if ($employee_id && $date) {
        $sql = "SELECT employee_id, name, date, time_in, time_out FROM testovaqi_table 
                WHERE employee_id = ? AND date = ? AND time_in = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $employee_id, $date, $time_in);
        $stmt->execute();
        $result = $stmt->get_result();
        $editRecord = $result->fetch_assoc();
        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Company Attendance</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .nav-tabs { display: flex; margin-bottom: 20px; border-bottom: 1px solid #ddd; }
        .nav-tab { padding: 10px 20px; border: none; background: #f5f5f5; cursor: pointer; border-bottom: 3px solid transparent; }
        .nav-tab.active { background: #007bff; color: white; border-bottom-color: #0056b3; }
        .nav-tab:hover { background: #e9ecef; }
        .nav-tab.active:hover { background: #0056b3; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .form-row { display: flex; gap: 15px; margin-bottom: 15px; }
        .form-group { flex: 1; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .action-buttons { display: flex; gap: 5px; }
        .edit-btn { padding: 5px 10px; background: #28a745; color: white; text-decoration: none; border-radius: 3px; }
        .delete-btn { padding: 5px 10px; background: #dc3545; color: white; border: none; border-radius: 3px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>Admin Dashboard</h1>
                <p>Company Attendance Management System</p>
            </div>
            <div class="header-right">
                <p>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></p>
                <a href="logout.php" style="color: white; text-decoration: none; margin-top: 10px; display: inline-block;">
                    <button class="button-secondary" style="padding: 8px 16px; font-size: 12px;">Logout</button>
                </a>
            </div>
        </div>

        <div class="nav-tabs">
            <button class="nav-tab <?php echo $tab === 'employees' ? 'active' : ''; ?>"
                    onclick="location.href='?tab=employees'">Employees</button>
            <button class="nav-tab <?php echo $tab === 'logs' ? 'active' : ''; ?>"
                    onclick="location.href='?tab=logs'">Attendance Logs</button>
            <button class="nav-tab <?php echo $tab === 'admins' ? 'active' : ''; ?>"
                    onclick="location.href='?tab=admins'">Admin Accounts</button>
            <a href="server-status-page.php" class="nav-tab">Server Status</a>
        </div>

        <div class="content">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <!-- EMPLOYEES TAB -->
            <div class="tab-content <?php echo $tab === 'employees' ? 'active' : ''; ?>">
                <?php if ($action === 'add_employee' || $action === 'edit_employee'): ?>
                    <h2><?php echo $action === 'add_employee' ? 'Add Employee' : 'Edit Employee'; ?></h2>
                    <form method="POST">
                        <?php echo getCSRFField(); ?>
                        <input type="hidden" name="action" value="<?php echo $action === 'add_employee' ? 'add_employee' : 'update_employee'; ?>">
                        <?php if ($action === 'edit_employee' && $editRecord): ?>
                            <input type="hidden" name="old_employee_id" value="<?php echo htmlspecialchars($editRecord['employee_id']); ?>">
                        <?php endif; ?>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="employee_id">Employee ID:</label>
                                <input type="text" id="employee_id" name="employee_id" required
                                       value="<?php echo htmlspecialchars($editRecord['employee_id'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="name">Name:</label>
                                <input type="text" id="name" name="name" required
                                       value="<?php echo htmlspecialchars($editRecord['name'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="hours_worked">Hours Worked:</label>
                                <input type="number" id="hours_worked" name="hours_worked" step="0.01" min="0"
                                       value="<?php echo htmlspecialchars($editRecord['hours_worked'] ?? '0'); ?>">
                            </div>
                        </div>

                        <button type="submit" class="button-primary"><?php echo $action === 'add_employee' ? 'Add Employee' : 'Update Employee'; ?></button>
                        <a href="?tab=employees" class="button-secondary">Cancel</a>
                    </form>
                <?php else: ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2>Employees</h2>
                        <a href="?tab=employees&action=add_employee" class="button-primary">Add New Employee</a>
                    </div>
                    <p style="color: #666; margin-bottom: 20px;">Total employees: <strong><?php echo $totalRecords; ?></strong></p>

                    <?php if (empty($records)): ?>
                        <div class="empty-state">
                            <p>No employees found. <a href="?tab=employees&action=add_employee">Add your first employee</a></p>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Employee ID</th>
                                        <th>Name</th>
                                        <th>Hours Worked</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($records as $record): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($record['employee_id']); ?></td>
                                            <td><?php echo htmlspecialchars($record['name']); ?></td>
                                            <td><?php echo htmlspecialchars($record['hours_worked']); ?> hours</td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="?tab=employees&action=edit_employee&employee_id=<?php echo urlencode($record['employee_id']); ?>" class="edit-btn">Edit</a>
                                                    <form method="POST" style="display: inline;"
                                                          onsubmit="return confirm('Are you sure you want to delete this employee?');">
                                                        <?php echo getCSRFField(); ?>
                                                        <input type="hidden" name="action" value="delete_employee">
                                                        <input type="hidden" name="employee_id" value="<?php echo htmlspecialchars($record['employee_id']); ?>">
                                                        <button type="submit" class="delete-btn">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- ATTENDANCE LOGS TAB -->
            <div class="tab-content <?php echo $tab === 'logs' ? 'active' : ''; ?>">
                <?php if ($action === 'add_log' || $action === 'edit_log'): ?>
                    <h2><?php echo $action === 'add_log' ? 'Add Attendance Log' : 'Edit Attendance Log'; ?></h2>
                    <form method="POST">
                        <?php echo getCSRFField(); ?>
                        <input type="hidden" name="action" value="<?php echo $action === 'add_log' ? 'add_log' : 'update_log'; ?>">
                        <?php if ($action === 'edit_log' && $editRecord): ?>
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($editRecord['id']); ?>">
                        <?php endif; ?>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="log_employee_id">Employee ID:</label>
                                <input type="text" id="log_employee_id" name="employee_id" required
                                       value="<?php echo htmlspecialchars($editRecord['employee_id'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="log_name">Name:</label>
                                <input type="text" id="log_name" name="name" required
                                       value="<?php echo htmlspecialchars($editRecord['name'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="log_date">Date:</label>
                                <input type="date" id="log_date" name="date" required
                                       value="<?php echo htmlspecialchars($editRecord['date'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="log_time_in">Time In:</label>
                                <input type="time" id="log_time_in" name="time_in" required
                                       value="<?php echo htmlspecialchars($editRecord['time_in'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="log_time_out">Time Out:</label>
                                <input type="time" id="log_time_out" name="time_out"
                                       value="<?php echo htmlspecialchars($editRecord['time_out'] ?? ''); ?>">
                            </div>
                        </div>

                        <button type="submit" class="button-primary"><?php echo $action === 'add_log' ? 'Add Log' : 'Update Log'; ?></button>
                        <a href="?tab=logs" class="button-secondary">Cancel</a>
                    </form>
                <?php else: ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2>Attendance Logs</h2>
                        <a href="?tab=logs&action=add_log" class="button-primary">Add New Log</a>
                    </div>
                    <p style="color: #666; margin-bottom: 20px;">Total logs: <strong><?php echo $totalRecords; ?></strong></p>

                    <?php if (empty($records)): ?>
                        <div class="empty-state">
                            <p>No attendance logs found. <a href="?tab=logs&action=add_log">Add your first log</a></p>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Employee ID</th>
                                        <th>Name</th>
                                        <th>Date</th>
                                        <th>Time In</th>
                                        <th>Time Out</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($records as $record): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($record['employee_id']); ?></td>
                                            <td><?php echo htmlspecialchars($record['name']); ?></td>
                                            <td><?php echo htmlspecialchars($record['date']); ?></td>
                                            <td><?php echo htmlspecialchars($record['time_in'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($record['time_out'] ?? '-'); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="?tab=logs&action=edit_log&id=<?php echo urlencode($record['id']); ?>" class="edit-btn">Edit</a>
                                                    <form method="POST" style="display: inline;"
                                                          onsubmit="return confirm('Are you sure you want to delete this log?');">
                                                        <?php echo getCSRFField(); ?>
                                                        <input type="hidden" name="action" value="delete_log">
                                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($record['id']); ?>">
                                                        <button type="submit" class="delete-btn">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- ADMIN ACCOUNTS TAB -->
            <div class="tab-content <?php echo $tab === 'admins' ? 'active' : ''; ?>">
                <?php if ($action === 'add_admin'): ?>
                    <h2>Add Admin Account</h2>
                    <form method="POST">
                        <?php echo getCSRFField(); ?>
                        <input type="hidden" name="action" value="add_admin">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="admin_username">Username:</label>
                                <input type="text" id="admin_username" name="username" required>
                            </div>
                            <div class="form-group">
                                <label for="admin_password">Password:</label>
                                <input type="password" id="admin_password" name="password" required minlength="6">
                            </div>
                        </div>

                        <button type="submit" class="button-primary">Create Admin Account</button>
                        <a href="?tab=admins" class="button-secondary">Cancel</a>
                    </form>
                <?php else: ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2>Admin Accounts</h2>
                        <a href="?tab=admins&action=add_admin" class="button-primary">Add New Admin</a>
                    </div>
                    <p style="color: #666; margin-bottom: 20px;">Total admins: <strong><?php echo $totalRecords; ?></strong></p>

                    <?php if (empty($records)): ?>
                        <div class="empty-state">
                            <p>No admin accounts found. <a href="?tab=admins&action=add_admin">Create your first admin account</a></p>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Created At</th>
                                        <th>Last Login</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($records as $record): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($record['username']); ?></td>
                                            <td><?php echo htmlspecialchars($record['created_at']); ?></td>
                                            <td><?php echo htmlspecialchars($record['last_login'] ?? 'Never'); ?></td>
                                            <td>
                                                <form method="POST" style="display: inline;"
                                                      onsubmit="return confirm('Are you sure you want to delete this admin account?');">
                                                    <?php echo getCSRFField(); ?>
                                                    <input type="hidden" name="action" value="delete_admin">
                                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($record['id']); ?>">
                                                    <button type="submit" class="delete-btn">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
