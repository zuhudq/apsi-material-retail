<?php
session_start();

// Proteksi Halaman
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'Pemilik') {
    header("Location: ../../auth/login.php");
    exit;
}

require_once '../../config/koneksi.php';
/** @var mysqli $conn */

if (isset($_GET['id'])) {
    // Memastikan ID berupa angka untuk keamanan tambahan
    $id_barang = intval($_GET['id']);

    // Eksekusi Hapus
    $delete_query = "DELETE FROM barang WHERE id_barang = $id_barang";

    try {
        // Coba jalankan eksekusi
        if (mysqli_query($conn, $delete_query)) {
            // Jika sukses, kembali ke master barang dengan parameter sukses
            header("Location: master_barang.php?pesan=hapus_sukses");
        } else {
            // Jika gagal tapi tidak melempar exception
            header("Location: master_barang.php?pesan=hapus_gagal");
        }
    } catch (mysqli_sql_exception $e) {
        // TANGKAP ERROR: Jika MySQL menolak karena Foreign Key / relasi data
        // Alih-alih memunculkan Fatal Error, kita alihkan kembali dengan pesan gagal
        header("Location: master_barang.php?pesan=hapus_gagal");
    }
} else {
    header("Location: master_barang.php");
}
exit;
