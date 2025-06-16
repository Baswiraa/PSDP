<?php
// config/database.php (atau conn.php)

$servername = "localhost";
$username = "root"; // Ganti dengan username database Anda
$password = "";     // Ganti dengan password database Anda
$dbname = "konveksi_scm"; // Nama database Anda

// Buat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi database GAGAL: " . $conn->connect_error);
}
// echo "Koneksi database berhasil!"; // Opsional: Untuk debugging
?>