<?php

require_once 'scripts/csrf.php';

$error = '';
$result = null;
$queryType = '';

if (!($_SESSION['logged_in'] ?? false)) {
    header('Location: admin-login.html');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $sql_query = trim($_POST['sql_query'] ?? '');
        
        if (empty($sql_query)) {
            $error = 'Please enter a SQL query.';
        } else {
            $allowed_queries = [
                'SELECT' => '/^SELECT\s+/i',
                'SHOW' => '/^SHOW\s+/i',
                'DESCRIBE' => '/^DESCRIBE\s+/i',
            ];
            
            $isAllowed = false;
            foreach ($allowed_queries as $type => $pattern) {
                if (preg_match($pattern, $sql_query)) {
                    $queryType = $type;
                    $isAllowed = true;
                    break;
                }
            }
            
            if (!$isAllowed) {
                $error = 'Only SELECT, SHOW, and DESCRIBE queries are allowed.';
            } else {
                try {
                    $conn = getDatabase();
                    $queryResult = $conn->query($sql_query);
                    
                    if ($queryResult === false) {
                        $error = 'Query error. Please check your syntax.';
                        error_log("SQL Error: " . $conn->error);
                    } else {
                        if (is_object($queryResult)) {
                            if ($queryResult->num_rows > 0) {
                                $result = [];
                                while ($row = $queryResult->fetch_assoc()) {
                                    $result[] = $row;
                                }
                            } else {
                                $result = [];
                            }
                        } else {
                            $result = "Query executed successfully.";
                        }
                    }
                } catch (Exception $e) {
                    error_log("Query execution error: " . $e->getMessage());
                    $error = 'Failed to execute query.';
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
    <title>Admin - Execute Query</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Admin Dashboard - Query Executor</h1>
    <p>Logged in as: <?php echo htmlspecialchars($_SESSION['username'] ?? 'admin'); ?></p>
    
    <h2>Execute Query</h2>
    <p><strong>Note:</strong> Only SELECT, SHOW, and DESCRIBE queries are allowed.</p>
    
    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    
    <form method="post" action="execute.php">
        <?php echo getCSRFField(); ?>
        <input type="text" name="sql_query" placeholder="SELECT * FROM testovaqi_table" 
               value="<?php echo htmlspecialchars($_POST['sql_query'] ?? ''); ?>" 
               style="width: 100%; padding: 8px;" required>
        <input type="submit" value="Execute">
    </form>
    
    <?php if ($result !== null): ?>
        <h3>Query Results:</h3>
        <?php if (is_array($result)): ?>
            <?php if (count($result) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <?php foreach (array_keys($result[0]) as $column): ?>
                                <th><?php echo htmlspecialchars($column); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result as $row): ?>
                            <tr>
                                <?php foreach ($row as $value): ?>
                                    <td><?php echo htmlspecialchars($value ?? 'NULL'); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No results found.</p>
            <?php endif; ?>
        <?php else: ?>
            <p class="success"><?php echo htmlspecialchars($result); ?></p>
        <?php endif; ?>
    <?php endif; ?>
    
    <br><br>
    <a href="logout.php">Logout</a>
</body>
</html>