<?php
session_start();
require_once '../../config/koneksi.php';
/** @var mysqli $conn */

// Pastikan ada ID transaksi yang dikirim
if (!isset($_GET['id'])) {
    die("Data nota tidak ditemukan.");
}

$id_jual = intval($_GET['id']);

// Ambil Data Header Penjualan
$query_header = mysqli_query($conn, "SELECT p.*, u.nama_lengkap FROM penjualan p JOIN users u ON p.id_user = u.id_user WHERE p.id_jual = $id_jual");
$header = mysqli_fetch_assoc($query_header);

if (!$header) {
    die("Transaksi tidak valid.");
}

// Ambil Data Detail Penjualan
$query_detail = mysqli_query($conn, "SELECT d.*, b.nama_barang FROM detail_jual d JOIN barang b ON d.id_barang = b.id_barang WHERE d.id_jual = $id_jual");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Nota - <?= $id_jual; ?></title>
    <style>
        /* CSS Penyesuaian Ekstra Besar untuk Bukti Laporan PDF */
        @page {
            margin: 15mm;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 18px;
            /* Font dinaikkan agar tebal di PDF A4 */
            color: #000;
            width: 100%;
            max-width: 120mm;
            /* Lebar nota diekspansi */
            margin: 0 auto;
            padding: 20px;
            border: 2px dashed #999;
            /* Bingkai dipertebal */
            box-shadow: 3px 3px 10px rgba(0, 0, 0, 0.1);
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .bold {
            font-weight: bold;
        }

        .garis {
            border-bottom: 2px dashed #000;
            margin: 10px 0;
        }

        .tabel-nota {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .tabel-nota td {
            vertical-align: top;
            padding: 4px 0;
        }

        /* Menyesuaikan tampilan saat dialog print PDF muncul */
        @media print {
            body {
                border: 2px dashed #000;
                box-shadow: none;
            }
        }
    </style>
</head>

<body>
    <div class="text-center">
        <h3 style="margin: 0; font-size: 20px;">TB. PESONA RUMAH KITA</h3>
        <p style="margin: 8px 0; font-size: 14px;">Jl. Pembangunan No. 1, Tangsel<br>Telp: 0812-3456-7890</p>
    </div>

    <div class="garis"></div>

    <table class="tabel-nota">
        <tr>
            <td>No</td>
            <td>: TRX-<?= sprintf("%05d", $header['id_jual']); ?></td>
        </tr>
        <tr>
            <td>Tgl</td>
            <td>: <?= date('d/m/Y H:i', strtotime($header['tanggal_jual'])); ?></td>
        </tr>
        <tr>
            <td>Kasir</td>
            <td>: <?= $header['nama_lengkap']; ?></td>
        </tr>
    </table>

    <div class="garis"></div>

    <table class="tabel-nota">
        <?php while ($row = mysqli_fetch_assoc($query_detail)): ?>
            <tr>
                <td colspan="3"><?= $row['nama_barang']; ?></td>
            </tr>
            <tr>
                <td><?= floatval($row['kuantitas']); ?> <?= $row['satuan_jual']; ?></td>
                <td>x <?= number_format($row['harga_satuan'], 0, ',', '.'); ?></td>
                <td class="text-right"><?= number_format($row['subtotal'], 0, ',', '.'); ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

    <div class="garis"></div>

    <table class="tabel-nota bold">
        <tr>
            <td>TOTAL</td>
            <td class="text-right">Rp <?= number_format($header['total_bayar'], 0, ',', '.'); ?></td>
        </tr>
        <tr>
            <td>TUNAI</td>
            <td class="text-right">Rp <?= number_format($header['total_bayar'] + $header['kembalian'], 0, ',', '.'); ?></td>
        </tr>
        <tr>
            <td>KEMBALI</td>
            <td class="text-right">Rp <?= number_format($header['kembalian'], 0, ',', '.'); ?></td>
        </tr>
    </table>

    <div class="garis"></div>

    <div class="text-center" style="margin-top: 15px; font-size: 14px;">
        <p>*** TERIMA KASIH ***<br>Barang yang sudah dibeli tidak dapat ditukar/dikembalikan.</p>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
        // Menutup jendela pop-up secara otomatis setelah selesai
        window.onafterprint = function() {
            window.close();
        }
    </script>
</body>

</html>