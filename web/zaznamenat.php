<?php
require_once 'scripts/config.php';
require_once 'scripts/csrf.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $employee_id = trim($_POST['employee_id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $date = $_POST['date'] ?? '';
        $time_in = $_POST['time_in'] ?? '';
        $time_out = $_POST['time_out'] ?? '';
        
        if (empty($employee_id)) {
            $error = 'Employee ID is required.';
        } elseif (empty($name)) {
            $error = 'Name is required.';
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
                $sql = "INSERT INTO testovaqi_table (employee_id, name, date, time_in, time_out) 
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
            } catch (Exception $e) {
                error_log("Attendance insert error: " . $e->getMessage());
                $error = 'Failed to record attendance. Please try again.';
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
        
        <label for="name">Jméno:</label>
        <input type="text" id="name" name="name" required 
               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"><br><br>

        <label for="date">Datum:</label>
        <input type="date" id="date" name="date" required 
               value="<?php echo htmlspecialchars($_POST['date'] ?? ''); ?>"><br><br>
        
        <label for="time_in">Příchod:</label>
        <input type="time" id="time_in" name="time_in" required 
               value="<?php echo htmlspecialchars($_POST['time_in'] ?? ''); ?>"><br><br>
        
        <label for="time_out">Odchod:</label>
        <input type="time" id="time_out" name="time_out" 
               value="<?php echo htmlspecialchars($_POST['time_out'] ?? ''); ?>"><br><br>
        
        <input type="submit" value="Zaznamenat">
    </form>
    
    <br>
    <a href="index.html">Zpet na hlavni stranku</a>
</body>
</html>
