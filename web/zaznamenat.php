<?php

require_once 'scripts/csrf.php';

$error = '';
$success = '';

$current_date = date('Y-m-d');
$current_time = date('H:i');
$current_time_plus_6h = date('H:i', strtotime('+6 hours'));
$employee_name = '';

if (isset($_GET['lookup'])) {
    header('Content-Type: application/json');
    $lookup_id = trim($_GET['lookup']);

    if ($lookup_id === '') {
        echo json_encode(['error' => 'Employee ID is required.']);
        exit;
    }

    try {
        $conn = getDatabase();
        $lookupStmt = $conn->prepare("SELECT name FROM employees WHERE employee_id = ?");
        if ($lookupStmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $lookupStmt->bind_param('s', $lookup_id);
        $lookupStmt->execute();
        $lookupResult = $lookupStmt->get_result();
        $employeeRow = $lookupResult->fetch_assoc();
        $lookupStmt->close();

        if ($employeeRow) {
            echo json_encode(['name' => $employeeRow['name']]);
        } else {
            echo json_encode(['error' => 'Employee ID not found.']);
        }
    } catch (Exception $e) {
        error_log("Employee lookup error: " . $e->getMessage());
        echo json_encode(['error' => 'Unable to fetch employee name.']);
    }

    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $employee_id = trim($_POST['employee_id'] ?? '');
        $date = $_POST['date'] ?? '';
        $time_in = $_POST['time_in'] ?? '';
        $time_out = $_POST['time_out'] ?? '';
        
        if (empty($employee_id)) {
            $error = 'Employee ID is required.';
        } elseif (empty($date)) {
            $error = 'Date is required.';
        } elseif (empty($time_in)) {
            $error = 'Time in is required.';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $error = 'Invalid date format.';
        } elseif (!preg_match('/^\d{2}:\d{2}$/', $time_in)) {
            $error = 'Invalid time in format.';
        } elseif (!empty($time_out) && !preg_match('/^\d{2}:\d{2}$/', $time_out)) {
            $error = 'Invalid time out format.';
        } else {
            try {
                $conn = getDatabase();
                $lookupStmt = $conn->prepare("SELECT name FROM employees WHERE employee_id = ?");
                if ($lookupStmt === false) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }

                $lookupStmt->bind_param('s', $employee_id);
                $lookupStmt->execute();
                $lookupResult = $lookupStmt->get_result();
                $employeeRow = $lookupResult->fetch_assoc();
                $lookupStmt->close();

                if (!$employeeRow) {
                    $error = 'Employee ID not found. Please register the employee first.';
                } else {
                    $name = $employeeRow['name'];
                    $employee_name = $name;
                }

                if (empty($error)) {
                    $sql = "INSERT INTO attendance_logs (employee_id, name, date, time_in, time_out) 
                            VALUES (?, ?, ?, ?, ?)";

                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }

                    $time_out = !empty($time_out) ? $time_out : null;
                    $stmt->bind_param("sssss", $employee_id, $name, $date, $time_in, $time_out);

                    if (!$stmt->execute()) {
                        if (strpos($stmt->error, 'Duplicate entry') !== false) {
                            $error = 'This attendance record already exists for this date and time.';
                        } else {
                            throw new Exception("Execute failed: " . $stmt->error);
                        }
                    } else {
                        $success = 'Attendance recorded successfully!';
                    }
                    
                    $stmt->close();
                }
            } catch (Exception $e) {
                error_log("Attendance insert error: " . $e->getMessage());
                if (empty($error)) {
                    $error = 'Failed to record attendance. Please try again.';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zaznamenat dochazku</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Zaznamenat dochazku</h1>
    
    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <p class="success"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>
    
    <form action="zaznamenat.php" method="post">
        <?php echo getCSRFField(); ?>
        
        <label for="employee_id">ID Zaměstnance:</label>
        <input type="text" id="employee_id" name="employee_id" required 
               value="<?php echo htmlspecialchars($_POST['employee_id'] ?? ''); ?>"><br><br>
        
        <label for="name_display">Jméno:</label>
        <input type="text" id="name_display" readonly 
               value="<?php echo htmlspecialchars($employee_name ?? ''); ?>"><br><br>
        <input type="hidden" id="name" name="name" 
               value="<?php echo htmlspecialchars($employee_name ?? ''); ?>">
        <p id="lookupMessage" class="error" style="display:none;"></p>

        <label for="date">Datum:</label>
        <input type="date" id="date" name="date" required 
               value="<?php echo htmlspecialchars($_POST['date'] ?? $current_date); ?>"><br><br>
        
        <label for="time_in">Příchod:</label>
        <input type="time" id="time_in" name="time_in" required 
               value="<?php echo htmlspecialchars($_POST['time_in'] ?? $current_time); ?>"><br><br>
        
        <label for="time_out">Odchod:</label>
        <input type="time" id="time_out" name="time_out" 
               value="<?php echo htmlspecialchars($_POST['time_out'] ?? $current_time_plus_6h); ?>"><br><br>
        
        <input type="submit" value="Zaznamenat">
    </form>
    <script>
        const employeeInput = document.getElementById('employee_id');
        const nameDisplay = document.getElementById('name_display');
        const nameHidden = document.getElementById('name');
        const lookupMessage = document.getElementById('lookupMessage');

        async function lookupEmployeeName() {
            const employeeId = employeeInput.value.trim();
            if (!employeeId) {
                nameDisplay.value = '';
                nameHidden.value = '';
                lookupMessage.style.display = 'none';
                return;
            }

            try {
                const response = await fetch(`zaznamenat.php?lookup=${encodeURIComponent(employeeId)}`);
                const result = await response.json();
                if (result.name) {
                    nameDisplay.value = result.name;
                    nameHidden.value = result.name;
                    lookupMessage.style.display = 'none';
                } else {
                    nameDisplay.value = '';
                    nameHidden.value = '';
                    lookupMessage.textContent = result.error || 'Employee not found.';
                    lookupMessage.style.display = 'block';
                }
            } catch (err) {
                nameDisplay.value = '';
                nameHidden.value = '';
                lookupMessage.textContent = 'Unable to resolve employee name.';
                lookupMessage.style.display = 'block';
            }
        }

        employeeInput.addEventListener('blur', lookupEmployeeName);
        employeeInput.addEventListener('keyup', (event) => {
            if (event.key === 'Enter' || event.key === 'Tab') {
                lookupEmployeeName();
            }
        });
    </script>
    <br>
    <a href="index.html">Zpet na hlavni stranku</a>
</body>
</html>
