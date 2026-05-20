<?php
session_start();

// Proteksi Halaman
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'Pemilik') {
    header("Location: ../../auth/login.php");
    exit;
}

require_once '../../config/koneksi.php';
/** @var mysqli $conn */

// --- BAGIAN LOGIKA (APPLICATION TIER) ---
$hari_ini = date('Y-m-d');
// Mencari tanggal kemarin
$kemarin = date('Y-m-d', strtotime('-1 day', strtotime($hari_ini)));

// 1. Hitung KPI Hari Ini
$query_hari_ini = "SELECT SUM(total_bayar) as total_pendapatan, COUNT(id_jual) as total_transaksi 
                   FROM penjualan 
                   WHERE DATE(tanggal_jual) = '$hari_ini' AND status = 'Selesai'";
$res_hari_ini = mysqli_query($conn, $query_hari_ini);
$data_hari_ini = mysqli_fetch_assoc($res_hari_ini);

$pendapatan_hari_ini = $data_hari_ini['total_pendapatan'] ? $data_hari_ini['total_pendapatan'] : 0;
$transaksi_hari_ini = $data_hari_ini['total_transaksi'] ? $data_hari_ini['total_transaksi'] : 0;

// 2. Hitung KPI Kemarin (Untuk Pembanding Delta)
$query_kemarin = "SELECT SUM(total_bayar) as total_pendapatan, COUNT(id_jual) as total_transaksi 
                  FROM penjualan 
                  WHERE DATE(tanggal_jual) = '$kemarin' AND status = 'Selesai'";
$res_kemarin = mysqli_query($conn, $query_kemarin);
$data_kemarin = mysqli_fetch_assoc($res_kemarin);

$pendapatan_kemarin = $data_kemarin['total_pendapatan'] ? $data_kemarin['total_pendapatan'] : 0;
$transaksi_kemarin = $data_kemarin['total_transaksi'] ? $data_kemarin['total_transaksi'] : 0;

// 3. Kalkulasi Persentase Delta (Pendapatan)
$delta_pendapatan = 0;
if ($pendapatan_kemarin > 0) {
    $delta_pendapatan = (($pendapatan_hari_ini - $pendapatan_kemarin) / $pendapatan_kemarin) * 100;
} else if ($pendapatan_kemarin == 0 && $pendapatan_hari_ini > 0) {
    $delta_pendapatan = 100; // Jika kemarin 0 dan hari ini ada pemasukan, naik 100%
}

// 4. Kalkulasi Persentase Delta (Transaksi)
$delta_transaksi = 0;
if ($transaksi_kemarin > 0) {
    $delta_transaksi = (($transaksi_hari_ini - $transaksi_kemarin) / $transaksi_kemarin) * 100;
} else if ($transaksi_kemarin == 0 && $transaksi_hari_ini > 0) {
    $delta_transaksi = 100;
}

// 5. Fungsi Helper untuk Format Tampilan Delta
function formatDelta($nilai)
{
    if ($nilai > 0) {
        return ['warna' => 'text-green-500', 'icon' => '▲', 'teks' => '+' . number_format($nilai, 1) . '%'];
    } else if ($nilai < 0) {
        return ['warna' => 'text-red-500', 'icon' => '▼', 'teks' => number_format($nilai, 1) . '%'];
    } else {
        return ['warna' => 'text-gray-400', 'icon' => '−', 'teks' => '0.0%'];
    }
}

$ui_pendapatan = formatDelta($delta_pendapatan);
$ui_transaksi = formatDelta($delta_transaksi);

// 6. Hitung KPI: Peringatan Stok
$query_kritis_count = "SELECT COUNT(id_barang) as total_kritis FROM barang WHERE stok_aktual <= stok_minimum";
$result_kritis_count = mysqli_query($conn, $query_kritis_count);
$data_kritis = mysqli_fetch_assoc($result_kritis_count);
$item_kritis = $data_kritis['total_kritis'];

// 7. Ambil Data Detail Item Kritis
$query_tabel_kritis = "SELECT kode_sku, nama_barang, stok_aktual, stok_minimum FROM barang WHERE stok_aktual <= stok_minimum ORDER BY stok_aktual ASC";
$result_tabel_kritis = mysqli_query($conn, $query_tabel_kritis);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pemilik - TB. Pesona Rumah Kita</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 flex h-screen overflow-hidden">

    <aside class="w-64 bg-indigo-900 text-white flex flex-col h-full shadow-lg">
        <div class="p-6 border-b border-indigo-800">
            <h1 class="text-xl font-bold">TB. Pesona Rumah Kita</h1>
            <p class="text-sm text-indigo-300 mt-1">Pemilik: <?= htmlspecialchars($_SESSION['nama_lengkap']); ?></p>
        </div>
        <nav class="flex-1 p-4 space-y-2">
            <a href="dashboard.php" class="flex items-center p-3 bg-indigo-800 rounded-lg font-semibold transition-colors">📊 Dashboard</a>
            <a href="master_barang.php" class="flex items-center p-3 hover:bg-indigo-800 rounded-lg transition-colors">📦 Master Barang</a>
            <a href="laporan_stok.php" class="flex items-center p-3 hover:bg-indigo-800 rounded-lg transition-colors">📋 Laporan Stok</a>
        </nav>
        <div class="p-4 border-t border-indigo-800">
            <a href="../../auth/login.php" class="flex items-center p-3 text-red-300 hover:text-white hover:bg-red-600 rounded-lg transition-colors">🚪 Logout</a>
        </div>
    </aside>

    <main class="flex-1 p-8 overflow-y-auto">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Dashboard Ringkasan</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-500 flex flex-col justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-semibold mb-1">Pendapatan Hari Ini</p>
                    <p class="text-3xl font-bold text-gray-800">Rp <?= number_format($pendapatan_hari_ini, 0, ',', '.'); ?></p>
                </div>
                <div class="mt-3 flex items-center text-sm">
                    <span class="<?= $ui_pendapatan['warna']; ?> font-bold mr-2">
                        <?= $ui_pendapatan['icon']; ?> <?= $ui_pendapatan['teks']; ?>
                    </span>
                    <span class="text-gray-400">vs kemarin</span>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow border-l-4 border-green-500 flex flex-col justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-semibold mb-1">Total Transaksi (Hari Ini)</p>
                    <p class="text-3xl font-bold text-gray-800"><?= $transaksi_hari_ini; ?> <span class="text-lg font-normal text-gray-500">Trx</span></p>
                </div>
                <div class="mt-3 flex items-center text-sm">
                    <span class="<?= $ui_transaksi['warna']; ?> font-bold mr-2">
                        <?= $ui_transaksi['icon']; ?> <?= $ui_transaksi['teks']; ?>
                    </span>
                    <span class="text-gray-400">vs kemarin</span>
                </div>
            </div>

            <div class="bg-red-50 p-6 rounded-lg shadow border-l-4 border-red-500 flex flex-col justify-between">
                <div>
                    <p class="text-sm text-red-500 font-semibold mb-1">Peringatan Stok!</p>
                    <p class="text-3xl font-bold text-red-700"><?= $item_kritis; ?> <span class="text-lg font-normal text-red-500">Item Kritis</span></p>
                </div>
                <div class="mt-3 text-sm text-red-400">
                    Segera lakukan re-stock barang.
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-5 border-b border-gray-200 bg-red-50 flex items-center">
                <span class="text-red-500 mr-2">⚠️</span>
                <h3 class="text-lg font-bold text-red-700">Peringatan Stok Menipis (Butuh Restock)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-gray-700 text-sm uppercase">
                            <th class="py-4 px-6 border-b">SKU</th>
                            <th class="py-4 px-6 border-b">Nama Barang</th>
                            <th class="py-4 px-6 border-b text-center">Stok Aktual</th>
                            <th class="py-4 px-6 border-b text-center">Batas Min.</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 text-sm">
                        <?php if (mysqli_num_rows($result_tabel_kritis) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result_tabel_kritis)): ?>
                                <tr class="hover:bg-gray-50 border-b">
                                    <td class="py-4 px-6 font-mono"><?= $row['kode_sku']; ?></td>
                                    <td class="py-4 px-6 font-semibold"><?= $row['nama_barang']; ?></td>
                                    <td class="py-4 px-6 text-center text-red-600 font-bold"><?= floatval($row['stok_aktual']); ?></td>
                                    <td class="py-4 px-6 text-center text-gray-500"><?= floatval($row['stok_minimum']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="py-6 text-center text-gray-500 italic">Semua stok barang dalam kondisi aman.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</body>

</html>