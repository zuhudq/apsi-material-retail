<?php
session_start();

// Proteksi Halaman
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'Pemilik') {
    header("Location: ../../auth/login.php");
    exit;
}

require_once '../../config/koneksi.php';
/** @var mysqli $conn */

// Query Canggih: Menggabungkan saldo barang dengan total mutasi (Masuk, Jual, Konversi)
$query_stok = "
    SELECT 
        b.kode_sku, 
        b.nama_barang, 
        b.stok_aktual,
        COALESCE((SELECT SUM(kuantitas_terima) FROM detail_terima dt JOIN penerimaan p ON dt.id_terima = p.id_terima WHERE dt.id_barang = b.id_barang), 0) AS total_masuk,
        COALESCE((SELECT SUM(kuantitas) FROM detail_jual dj JOIN penjualan pj ON dj.id_jual = pj.id_jual WHERE dj.id_barang = b.id_barang AND pj.status = 'Selesai'), 0) AS total_keluar_jual,
        COALESCE((SELECT SUM(qty_sumber) FROM log_konversi lk JOIN konversi_satuan ks ON lk.id_konversi = ks.id_konversi WHERE ks.id_barang_besar = b.id_barang), 0) AS konversi_keluar,
        COALESCE((SELECT SUM(qty_target) FROM log_konversi lk JOIN konversi_satuan ks ON lk.id_konversi = ks.id_konversi WHERE ks.id_barang_kecil = b.id_barang), 0) AS konversi_masuk
    FROM barang b
    ORDER BY b.nama_barang ASC
";

$result_stok = mysqli_query($conn, $query_stok);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Stok Opname - TB. Pesona Rumah Kita</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {

            /* 1. Atur ukuran kertas menjadi Lanskap (memanjang) agar tabel muat */
            @page {
                size: landscape;
                margin: 10mm;
            }

            /* 2. Sembunyikan elemen yang tidak perlu dicetak (Sidebar & Tombol) */
            aside,
            button {
                display: none !important;
            }

            /* 3. Maksimalkan lebar konten */
            body,
            main {
                background-color: white !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            /* 4. Rapikan tabel dan border agar jelas saat diprint hitam-putih */
            table {
                width: 100% !important;
                border-collapse: collapse !important;
            }

            th,
            td {
                border: 1px solid #333 !important;
                padding: 8px !important;
                font-size: 12px !important;
                color: black !important;
            }

            /* 5. Hilangkan background warna-warni agar hemat tinta */
            th,
            tr,
            td {
                background-color: transparent !important;
            }

            /* 6. Modifikasi kotak input fisik menjadi garis bawah agar mudah ditulis pakai pulpen */
            input[type="text"] {
                border: none !important;
                border-bottom: 1px dashed #000 !important;
                background: transparent !important;
            }
        }
    </style>
</head>
</head>

<body class="bg-gray-50 flex h-screen overflow-hidden">

    <aside class="w-64 bg-indigo-900 text-white flex flex-col h-full shadow-lg">
        <div class="p-6 border-b border-indigo-800">
            <h1 class="text-xl font-bold">TB. Pesona Rumah Kita</h1>
            <p class="text-sm text-indigo-300 mt-1">Pemilik: <?= htmlspecialchars($_SESSION['nama_lengkap']); ?></p>
        </div>
        <nav class="flex-1 p-4 space-y-2">
            <a href="dashboard.php" class="flex items-center p-3 hover:bg-indigo-800 rounded-lg transition-colors">📊 Dashboard</a>
            <a href="master_barang.php" class="flex items-center p-3 hover:bg-indigo-800 rounded-lg transition-colors">📦 Master Barang</a>
            <a href="laporan_stok.php" class="flex items-center p-3 bg-indigo-800 rounded-lg font-semibold transition-colors">📋 Laporan Stok</a>
        </nav>
        <div class="p-4 border-t border-indigo-800">
            <a href="../../auth/login.php" class="flex items-center p-3 text-red-300 hover:text-white hover:bg-red-600 rounded-lg transition-colors">🚪 Logout</a>
        </div>
    </aside>

    <main class="flex-1 p-8 overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Lembar Kerja Stok Opname</h2>
            <button onclick="window.print()" class="bg-green-600 hover:bg-green-800 text-white font-bold py-2 px-4 rounded shadow transition-colors">
                🖨️ Cetak / Ekspor PDF
            </button>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-indigo-50 text-indigo-900 text-sm uppercase">
                            <th class="py-4 px-6 border-b" rowspan="2">SKU</th>
                            <th class="py-4 px-6 border-b" rowspan="2">Nama Barang</th>
                            <th class="py-2 px-6 border-b border-l border-indigo-200 text-center" colspan="2">Mutasi Masuk</th>
                            <th class="py-2 px-6 border-b border-l border-indigo-200 text-center" colspan="2">Mutasi Keluar</th>
                            <th class="py-4 px-6 border-b border-l border-indigo-200 text-center" rowspan="2">Saldo Akhir<br>(Sistem)</th>
                            <th class="py-4 px-6 border-b border-l border-indigo-200 text-center bg-yellow-50" rowspan="2">Saldo Fisik<br>(Isi Manual)</th>
                        </tr>
                        <tr class="bg-indigo-50 text-indigo-900 text-xs uppercase">
                            <th class="py-2 px-4 border-b border-l border-indigo-200 text-center">Penerimaan</th>
                            <th class="py-2 px-4 border-b border-l border-indigo-200 text-center text-blue-600">Dari Konversi</th>
                            <th class="py-2 px-4 border-b border-l border-indigo-200 text-center">Penjualan</th>
                            <th class="py-2 px-4 border-b border-l border-indigo-200 text-center text-red-600">Dipecah (Konversi)</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 text-sm">
                        <?php if (mysqli_num_rows($result_stok) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result_stok)): ?>
                                <tr class="hover:bg-gray-50 border-b">
                                    <td class="py-4 px-6 font-mono"><?= $row['kode_sku']; ?></td>
                                    <td class="py-4 px-6 font-semibold"><?= $row['nama_barang']; ?></td>

                                    <td class="py-4 px-4 text-center border-l border-gray-200 text-green-600 font-bold">+<?= floatval($row['total_masuk']); ?></td>
                                    <td class="py-4 px-4 text-center border-l border-gray-100 text-blue-600 font-bold">+<?= floatval($row['konversi_masuk']); ?></td>

                                    <td class="py-4 px-4 text-center border-l border-gray-200 text-red-500 font-bold">-<?= floatval($row['total_keluar_jual']); ?></td>
                                    <td class="py-4 px-4 text-center border-l border-gray-100 text-red-500 font-bold">-<?= floatval($row['konversi_keluar']); ?></td>

                                    <td class="py-4 px-6 text-center border-l border-gray-200 text-lg text-gray-900 font-black">
                                        <?= floatval($row['stok_aktual']); ?>
                                    </td>

                                    <td class="py-4 px-6 border-l border-gray-200 bg-yellow-50">
                                        <input type="text" class="w-full border-b border-gray-400 bg-transparent focus:outline-none focus:border-indigo-500 text-center">
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="py-6 text-center text-gray-500 italic">Belum ada data barang.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

</body>

</html>