<?php

require_once 'scripts/csrf.php';

if ($_SESSION['logged_in'] ?? false) {
    header('Location: admin-dashboard.php');
    exit;
}

header('Location: admin-login-form.php');
exit;
?>