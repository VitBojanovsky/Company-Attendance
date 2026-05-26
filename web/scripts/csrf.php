<?php
session_start();

function getCSRFToken()
{
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf_token"];
}
function verifyCSRFToken($token)
{
    if (empty($_SESSION["csrf_token"])) {
        return false;
    }
    return hash_equals($_SESSION["csrf_token"], $token ?? "");
}
function getCSRFField()
{
    return '<input type="hidden" name="csrf_token" value="' .
        htmlspecialchars(getCSRFToken()) .
        '">';
}
function calculateHoursWorked($time_in, $time_out)
{
    if (!$time_in || !$time_out) {
        return "N/A";
    }
    $in = strtotime($time_in);
    $out = strtotime($time_out);
    if (!$in || !$out) {
        return "N/A";
    }
    $seconds = $out >= $in ? $out - $in : $out + 86400 - $in;
    return number_format($seconds / 3600, 2) . " hours";
}
