<?php
session_start();

// Proteksi Halaman
if (!isset($_SESSION['id_user']) || ($_SESSION['role'] !== 'Kasir' && $_SESSION['role'] !== 'Pemilik')) {
    header("Location: ../../auth/login.php");
    exit;
}

require_once '../../config/koneksi.php';
/** @var mysqli $conn */

if (isset($_GET['id'])) {
    $id_jual = intval($_GET['id']);

    // Cek dulu apakah statusnya sudah dibatalkan sebelumnya
    $cek_status = mysqli_query($conn, "SELECT status FROM penjualan WHERE id_jual = $id_jual");
    $data_status = mysqli_fetch_assoc($cek_status);

    if ($data_status['status'] == 'Dibatalkan') {
        header("Location: riwayat_transaksi.php?pesan=sudah_batal");
        exit;
    }

    // Mulai Transaksi Database (Atomic)
    mysqli_begin_transaction($conn);

    try {
        // 1. Ubah status transaksi di header menjadi Dibatalkan
        mysqli_query($conn, "UPDATE penjualan SET status = 'Dibatalkan' WHERE id_jual = $id_jual");

        // 2. Ambil detail barang apa saja yang dibeli pada transaksi ini
        $query_detail = mysqli_query($conn, "SELECT id_barang, kuantitas FROM detail_jual WHERE id_jual = $id_jual");

        // 3. Looping untuk mengembalikan stok ke tabel barang
        while ($item = mysqli_fetch_assoc($query_detail)) {
            $id_b = $item['id_barang'];
            $qty = floatval($item['kuantitas']);

            // Tambahkan kembali stoknya
            mysqli_query($conn, "UPDATE barang SET stok_aktual = stok_aktual + $qty WHERE id_barang = $id_b");
        }

        // Resmikan perubahan (Commit)
        mysqli_commit($conn);
        header("Location: riwayat_transaksi.php?pesan=batal_sukses");
    } catch (Exception $e) {
        // Jika gagal, batalkan semua perintah (Rollback)
        mysqli_rollback($conn);
        header("Location: riwayat_transaksi.php?pesan=batal_gagal");
    }
} else {
    header("Location: riwayat_transaksi.php");
}
exit;
