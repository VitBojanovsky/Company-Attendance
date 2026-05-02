<?php
require_once 'scripts/config.php';
require_once 'scripts/csrf.php';

try {
    $conn = getDatabase();
    
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    $countResult = $conn->query("SELECT COUNT(*) as total FROM attendance_logs");
    $countRow = $countResult->fetch_assoc();
    $total = $countRow['total'];
    $totalPages = ceil($total / $limit);
    
    $sql = "SELECT employee_id, name, date, time_in, time_out 
            FROM attendance_logs 
            ORDER BY date DESC, time_in DESC 
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
} catch (Exception $e) {
    error_log("Attendance view error: " . $e->getMessage());
    $error = 'Failed to load attendance records.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prehled dochazky</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Prehled dochazky</h1>
    
    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php else: ?>
        <?php if ($result && $result->num_rows > 0): ?>
            <p>Total records: <?php echo $total; ?></p>
            <table>
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Date</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): 
                        $duration = 'N/A';
                        if ($row['time_out']) {
                            $timeIn = new DateTime($row['time_in']);
                            $timeOut = new DateTime($row['time_out']);
                            $interval = $timeIn->diff($timeOut);
                            $duration = $interval->format('%H:%I');
                        }
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['employee_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['date']); ?></td>
                        <td><?php echo htmlspecialchars($row['time_in']); ?></td>
                        <td><?php echo $row['time_out'] ? htmlspecialchars($row['time_out']) : 'N/A'; ?></td>
                        <td><?php echo $duration; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1">First</a>
                    <a href="?page=<?php echo $page - 1; ?>">Previous</a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>">Next</a>
                    <a href="?page=<?php echo $totalPages; ?>">Last</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p>No attendance records found.</p>
        <?php endif; ?>
    <?php endif; ?>
    
    <br><br>
    <a href="index.html">Back to home</a>
</body>
</html>
