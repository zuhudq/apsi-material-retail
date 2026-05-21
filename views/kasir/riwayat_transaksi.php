<?php
session_start();

// Proteksi Halaman
if (!isset($_SESSION['id_user']) || ($_SESSION['role'] !== 'Kasir' && $_SESSION['role'] !== 'Pemilik')) {
    header("Location: ../../auth/login.php");
    exit;
}

require_once '../../config/koneksi.php';
/** @var mysqli $conn */

// --- LOGIKA FILTER PENCARIAN ---
$where_clause = "WHERE 1=1"; // Default (Ambil Semua)

// Jika ada pencarian No Transaksi
if (isset($_GET['search']) && $_GET['search'] != '') {
    $search = intval(preg_replace('/[^0-9]/', '', $_GET['search'])); // Ambil angkanya saja
    if ($search > 0) {
        $where_clause .= " AND p.id_jual = $search";
    }
}

// Jika ada filter Tanggal
if (isset($_GET['tanggal']) && $_GET['tanggal'] != '') {
    $tanggal = mysqli_real_escape_string($conn, $_GET['tanggal']);
    $where_clause .= " AND DATE(p.tanggal_jual) = '$tanggal'";
}

// Eksekusi Query dengan Filter
$query_riwayat = "SELECT p.*, u.nama_lengkap 
                  FROM penjualan p 
                  JOIN users u ON p.id_user = u.id_user 
                  $where_clause
                  ORDER BY p.tanggal_jual DESC";
$result_riwayat = mysqli_query($conn, $query_riwayat);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi - Kasir POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .custom-scroll::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .custom-scroll::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 4px;
        }
    </style>
</head>

<body class="bg-gray-100 flex h-screen overflow-hidden">

    <aside class="w-20 lg:w-64 bg-blue-900 text-white flex flex-col h-full shadow-lg transition-all duration-300">
        <div class="p-4 lg:p-6 border-b border-blue-800 text-center lg:text-left">
            <h1 class="text-xl font-bold hidden lg:block">TB. Pesona</h1>
            <span class="text-2xl lg:hidden">🏪</span>
        </div>
        <nav class="flex-1 p-2 lg:p-4 space-y-2">
            <a href="pos.php" class="flex items-center p-3 hover:bg-blue-800 rounded-lg transition-colors justify-center lg:justify-start">
                <span class="text-xl lg:mr-3">🛒</span>
                <span class="hidden lg:block">Kasir POS</span>
            </a>
            <a href="riwayat_transaksi.php" class="flex items-center p-3 bg-blue-800 rounded-lg font-semibold transition-colors justify-center lg:justify-start">
                <span class="text-xl lg:mr-3">📄</span>
                <span class="hidden lg:block">Riwayat Transaksi</span>
            </a>
        </nav>
        <div class="p-4 border-t border-blue-800">
            <a href="../../auth/login.php" class="flex items-center p-3 text-red-300 hover:text-white hover:bg-red-600 rounded-lg transition-colors justify-center lg:justify-start">
                <span class="text-xl lg:mr-3">🚪</span>
                <span class="hidden lg:block">Logout</span>
            </a>
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-full p-6 bg-gray-50 overflow-y-auto custom-scroll">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Riwayat Penjualan</h2>
                <p class="text-gray-500 text-sm mt-1">Daftar seluruh transaksi kasir.</p>
            </div>
            <a href="pos.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow transition-colors">
                + Transaksi Baru
            </a>
        </div>

        <?php if (isset($_GET['pesan'])): ?>
            <?php if ($_GET['pesan'] == 'batal_sukses'): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">Transaksi berhasil dibatalkan dan stok telah dikembalikan ke gudang.</div>
            <?php elseif ($_GET['pesan'] == 'batal_gagal'): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">Gagal membatalkan transaksi karena kesalahan sistem.</div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="bg-white p-4 rounded-lg shadow mb-6">
            <form action="" method="GET" class="flex flex-col md:flex-row gap-4 items-end">
                <div class="w-full md:w-1/3">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Cari No. Transaksi (TRX)</label>
                    <input type="text" name="search" placeholder="Contoh: 19" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:border-blue-500">
                </div>
                <div class="w-full md:w-1/3">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Filter Tanggal</label>
                    <input type="date" name="tanggal" value="<?= isset($_GET['tanggal']) ? htmlspecialchars($_GET['tanggal']) : ''; ?>" class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:border-blue-500">
                </div>
                <div class="w-full md:w-auto flex gap-2">
                    <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white font-bold py-2 px-6 rounded transition-colors">Cari</button>
                    <a href="riwayat_transaksi.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded transition-colors">Reset</a>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-blue-50 text-blue-900 text-sm uppercase">
                            <th class="py-4 px-6 border-b">No. Transaksi</th>
                            <th class="py-4 px-6 border-b">Waktu Penjualan</th>
                            <th class="py-4 px-6 border-b">Kasir</th>
                            <th class="py-4 px-6 border-b">Total Belanja</th>
                            <th class="py-4 px-6 border-b text-center">Status</th>
                            <th class="py-4 px-6 border-b text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 text-sm">
                        <?php if (mysqli_num_rows($result_riwayat) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result_riwayat)): ?>
                                <tr class="hover:bg-gray-50 border-b transition-colors <?= $row['status'] == 'Dibatalkan' ? 'bg-red-50 opacity-75' : ''; ?>">
                                    <td class="py-4 px-6 font-mono font-bold <?= $row['status'] == 'Dibatalkan' ? 'text-red-700 line-through' : 'text-gray-800' ?>">
                                        TRX-<?= sprintf("%05d", $row['id_jual']); ?>
                                    </td>
                                    <td class="py-4 px-6">
                                        <?= date('d M Y, H:i', strtotime($row['tanggal_jual'])); ?>
                                    </td>
                                    <td class="py-4 px-6 font-semibold">
                                        <?= $row['nama_lengkap']; ?>
                                    </td>
                                    <td class="py-4 px-6 font-bold <?= $row['status'] == 'Dibatalkan' ? 'text-red-500 line-through' : 'text-blue-600' ?>">
                                        Rp <?= number_format($row['total_bayar'], 0, ',', '.'); ?>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <?php if ($row['status'] == 'Selesai'): ?>
                                            <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full font-bold text-xs">Selesai</span>
                                        <?php else: ?>
                                            <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full font-bold text-xs">Dibatalkan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6 text-center flex justify-center space-x-2">
                                        <button onclick="cetakUlang(<?= $row['id_jual']; ?>)" class="bg-gray-700 hover:bg-gray-900 text-white py-1 px-3 rounded text-xs font-bold transition-colors flex items-center shadow-sm" title="Cetak Nota">
                                            🖨️
                                        </button>

                                        <?php if ($row['status'] == 'Selesai'): ?>
                                            <a href="batal_transaksi.php?id=<?= $row['id_jual']; ?>"
                                                onclick="return confirm('PERINGATAN!\n\nApakah Anda yakin ingin membatalkan TRX-<?= sprintf("%05d", $row['id_jual']); ?>?\n\nAksi ini akan mengubah status transaksi menjadi batal dan mengembalikan seluruh stok barang ke sistem.');"
                                                class="bg-red-500 hover:bg-red-600 text-white py-1 px-3 rounded text-xs font-bold transition-colors shadow-sm flex items-center" title="Batal/Void Transaksi">
                                                ❌ Void
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="py-8 text-center text-gray-400 italic">
                                    <span class="text-4xl block mb-2">📄</span>
                                    Tidak ada transaksi yang sesuai kriteria.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        function cetakUlang(idTransaksi) {
            let url = 'cetak_nota.php?id=' + idTransaksi;
            window.open(url, 'CetakNota', 'width=800,height=700,left=200,top=100');
        }
    </script>
</body>

</html>