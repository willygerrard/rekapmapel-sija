<?php
session_start();
if (!isset($_SESSION['is_login']) || $_SESSION['is_login'] !== true) {
    header("Location: login.php");
    exit();
}

// Redirect sesuai role
if ($_SESSION['role'] === 'admin') {
    header("Location: dashboard_admin.php");
} else {
    header("Location: upload_rekap.php");
}
exit();
?>