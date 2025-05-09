<?php
// Pastikan session dimulai
session_start();

// Redirect ke halaman login jika belum login
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

// Redirect ke dashboard jika sudah login
header("Location: pages/dashboard.php");
exit();
?>