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
$action = $_GET['action'] ?? 'list';
$records = [];
$totalRecords = 0;
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Handle POST actions (Add, Update, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $post_action = $_POST['action'] ?? '';
        
        if ($post_action === 'add') {
            $employee_id = trim($_POST['employee_id'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $date = trim($_POST['date'] ?? '');
            $time_in = trim($_POST['time_in'] ?? '');
            $time_out = trim($_POST['time_out'] ?? '');
            
            if (!$employee_id || !$name || !$date) {
                $error = 'Please fill in all required fields.';
            } else {
                $sql = "INSERT INTO testovaqi_table (employee_id, name, date, time_in, time_out) 
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("sssss", $employee_id, $name, $date, $time_in, $time_out);
                    if ($stmt->execute()) {
                        $success = 'Record added successfully!';
                    } else {
                        $error = 'Failed to add record: ' . $conn->error;
                    }
                    $stmt->close();
                } else {
                    $error = 'Database error: ' . $conn->error;
                }
            }
        } elseif ($post_action === 'update') {
            $old_employee_id = $_POST['old_employee_id'] ?? '';
            $old_date = $_POST['old_date'] ?? '';
            $old_time_in = $_POST['old_time_in'] ?? '';
            
            $employee_id = trim($_POST['employee_id'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $date = trim($_POST['date'] ?? '');
            $time_in = trim($_POST['time_in'] ?? '');
            $time_out = trim($_POST['time_out'] ?? '');
            
            if (!$employee_id || !$name || !$date) {
                $error = 'Please fill in all required fields.';
            } else {
                $sql = "UPDATE testovaqi_table 
                        SET employee_id = ?, name = ?, date = ?, time_in = ?, time_out = ? 
                        WHERE employee_id = ? AND date = ? AND time_in = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("ssssssss", $employee_id, $name, $date, $time_in, $time_out, 
                                      $old_employee_id, $old_date, $old_time_in);
                    if ($stmt->execute()) {
                        $success = 'Record updated successfully!';
                    } else {
                        $error = 'Failed to update record: ' . $conn->error;
                    }
                    $stmt->close();
                } else {
                    $error = 'Database error: ' . $conn->error;
                }
            }
        } elseif ($post_action === 'delete') {
            $employee_id = $_POST['employee_id'] ?? '';
            $date = $_POST['date'] ?? '';
            $time_in = $_POST['time_in'] ?? '';
            
            if (!$employee_id || !$date) {
                $error = 'Invalid record data.';
            } else {
                $sql = "DELETE FROM testovaqi_table WHERE employee_id = ? AND date = ? AND time_in = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("sss", $employee_id, $date, $time_in);
                    if ($stmt->execute()) {
                        $success = 'Record deleted successfully!';
                    } else {
                        $error = 'Failed to delete record: ' . $conn->error;
                    }
                    $stmt->close();
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
    <title>Admin Dashboard - Database Management</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>Admin Dashboard</h1>
                <p>Database Management System</p>
            </div>
            <div class="header-right">
                <p>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></p>
                <a href="logout.php" style="color: white; text-decoration: none; margin-top: 10px; display: inline-block;">
                    <button class="button-secondary" style="padding: 8px 16px; font-size: 12px;">Logout</button>
                </a>
            </div>
        </div>
        
        <div class="nav-tabs">
            <button class="nav-tab <?php echo ($action === 'list' || $action === '') ? 'active' : ''; ?>" 
                    onclick="location.href='?action=list'">View Records</button>
            <button class="nav-tab <?php echo $action === 'add' ? 'active' : ''; ?>" 
                    onclick="location.href='?action=add'">Add New Record</button>
            <a href="server-status-page.php" class="nav-tab">Server Status</a>
        </div>
        
        <div class="content">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($action === 'list' || $action === ''): ?>
                <!-- VIEW RECORDS -->
                <h2>Attendance Records</h2>
                <p style="color: #666; margin-bottom: 20px;">Total records: <strong><?php echo $totalRecords; ?></strong></p>
                
                <?php if (empty($records)): ?>
                    <div class="empty-state">
                        <p>No records found. <a href="?action=add">Add your first record</a></p>
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
                                                <a href="?action=edit&employee_id=<?php echo urlencode($record['employee_id']); ?>&date=<?php echo urlencode($record['date']); ?>&time_in=<?php echo urlencode($record['time_in'] ?? ''); ?>" class="edit-btn">Edit</a>
                                                <form method="POST" style="display: inline;" 
                                                      onsubmit="return confirm('Are you sure you want to delete this record?');">
                                                    <?php echo getCSRFField(); ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="employee_id" value="<?php echo htmlspecialchars($record['employee_id']); ?>">
                                                    <input type="hidden" name="date" value="<?php echo htmlspecialchars($record['date']); ?>">
                                                    <input type="hidden" name="time_in" value="<?php echo htmlspecialchars($record['time_in'] ?? ''); ?>">
                                                    <button type="submit" class="delete-btn">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- PAGINATION -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?action=list&page=1">« First</a>
                                <a href="?action=list&page=<?php echo $page - 1; ?>">< Previous</a>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            if ($startPage > 1):
                                echo '<a href="?action=list&page=1">1</a>';
                                if ($startPage > 2) echo '<span>...</span>';
                            endif;
                            
                            for ($i = $startPage; $i <= $endPage; $i++):
                                if ($i == $page):
                                    echo '<span class="current">' . $i . '</span>';
                                else:
                                    echo '<a href="?action=list&page=' . $i . '">' . $i . '</a>';
                                endif;
                            endfor;
                            
                            if ($endPage < $totalPages):
                                if ($endPage < $totalPages - 1) echo '<span>...</span>';
                                echo '<a href="?action=list&page=' . $totalPages . '">' . $totalPages . '</a>';
                            endif;
                            ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?action=list&page=<?php echo $page + 1; ?>">Next ></a>
                                <a href="?action=list&page=<?php echo $totalPages; ?>">Last »</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
            <?php elseif ($action === 'add'): ?>
                <!-- ADD NEW RECORD -->
                <h2>Add New Attendance Record</h2>
                
                <div class="form-section">
                    <form method="POST">
                        <?php echo getCSRFField(); ?>
                        <input type="hidden" name="action" value="add">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="employee_id">Employee ID <span class="required">*</span></label>
                                <input type="text" id="employee_id" name="employee_id" placeholder="e.g., EMP001" required>
                                <div class="form-hint">Unique identifier for the employee</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="name">Employee Name <span class="required">*</span></label>
                                <input type="text" id="name" name="name" placeholder="John Doe" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="date">Date <span class="required">*</span></label>
                                <input type="date" id="date" name="date" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="time_in">Time In</label>
                                <input type="time" id="time_in" name="time_in">
                                <div class="form-hint">Leave blank if not applicable</div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="time_out">Time Out</label>
                            <input type="time" id="time_out" name="time_out">
                            <div class="form-hint">Leave blank if not applicable</div>
                        </div>
                        
                        <div class="button-group">
                            <button type="submit" class="button-primary">Add Record</button>
                            <a href="?action=list" class="button button-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
                
            <?php elseif ($action === 'edit' && $editRecord): ?>
                <!-- EDIT RECORD -->
                <h2>Edit Attendance Record</h2>
                
                <div class="form-section">
                    <form method="POST">
                        <?php echo getCSRFField(); ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="old_employee_id" value="<?php echo htmlspecialchars($editRecord['employee_id']); ?>">
                        <input type="hidden" name="old_date" value="<?php echo htmlspecialchars($editRecord['date']); ?>">
                        <input type="hidden" name="old_time_in" value="<?php echo htmlspecialchars($editRecord['time_in'] ?? ''); ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="employee_id">Employee ID <span class="required">*</span></label>
                                <input type="text" id="employee_id" name="employee_id" 
                                       value="<?php echo htmlspecialchars($editRecord['employee_id']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="name">Employee Name <span class="required">*</span></label>
                                <input type="text" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($editRecord['name']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="date">Date <span class="required">*</span></label>
                                <input type="date" id="date" name="date" 
                                       value="<?php echo htmlspecialchars($editRecord['date']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="time_in">Time In</label>
                                <input type="time" id="time_in" name="time_in" 
                                       value="<?php echo htmlspecialchars($editRecord['time_in'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="time_out">Time Out</label>
                            <input type="time" id="time_out" name="time_out" 
                                   value="<?php echo htmlspecialchars($editRecord['time_out'] ?? ''); ?>">
                        </div>
                        
                        <div class="button-group">
                            <button type="submit" class="button-primary">Save Changes</button>
                            <a href="?action=list" class="button button-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
                
            <?php elseif ($action === 'edit'): ?>
                <div class="alert alert-error">Record not found.</div>
                <a href="?action=list" class="button button-secondary">Back to Records</a>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <p>&copy; 2026 Company Attendance System - Admin Dashboard</p>
        </div>
    </div>
<!--
    <style>
        #statusFrame {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 350px;
            height: 400px;
            border: 1px solid #333;
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            overflow: auto;
        }
    </style>

    <iframe id="statusFrame" src="/server-status"></iframe>

    <script>
        const iframe = document.getElementById("statusFrame");

        iframe.onload = () => {
            const doc = iframe.contentDocument;

            const link = doc.createElement("link");
            link.rel = "stylesheet";
            link.href = "/styles.css"; 

            doc.head.appendChild(link);
        };
    </script>
            -->
</body>
</html>