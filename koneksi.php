<?php
$host = "localhost";
$user = "root"; // Username default XAMPP
$pass = "";     // Password default XAMPP (kosong)
$db   = "db_apsi_material";

// Membangun koneksi
$conn = mysqli_connect($host, $user, $pass, $db);

// Cek koneksi
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
// Jika ingin memastikan koneksi berhasil saat testing, hapus komentar di bawah ini:
// echo "Koneksi berhasil, Boskuh!";
