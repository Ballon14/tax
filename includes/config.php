<?php
// Konfigurasi database
define('DB_HOST', '100.127.253.4');
define('DB_USER', 'iqbal'); // Ganti dengan username database Anda
define('DB_PASS', 'iqbal'); // Ganti dengan password database Anda
define('DB_NAME', 'tax_system'); // Ganti dengan nama database Anda

// Membuat koneksi
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");
?>