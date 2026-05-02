<?php
session_start();

function getCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function verifyCSRFToken($token) {
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token ?? '');
}
function getCSRFField() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(getCSRFToken()) . '">';
}
function calculateHoursWorked($conn, $employee_id) {
    $sql = "SELECT COALESCE(SUM(" .
           "CASE " .
           "WHEN time_out >= time_in THEN TIME_TO_SEC(time_out) - TIME_TO_SEC(time_in) " .
           "ELSE TIME_TO_SEC(time_out) + 86400 - TIME_TO_SEC(time_in) " .
           "END" .
           ")/3600, 0) AS hours " .
           "FROM attendance_logs WHERE employee_id = ? AND time_out IS NOT NULL";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        return 0;
    }
    $stmt->bind_param('s', $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    $hours = (float)($row['hours'] ?? 0);
    return round($hours, 2);
}