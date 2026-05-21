<?php
session_start();

// Proteksi Halaman
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'Pemilik') {
    header("Location: ../../auth/login.php");
    exit;
}

require_once '../../config/koneksi.php';

// Ambil data barang beserta nama kategorinya
$query_barang = "SELECT b.*, k.nama_kategori 
                 FROM barang b 
                 JOIN kategori k ON b.id_kategori = k.id_kategori 
                 ORDER BY b.nama_barang ASC";
/** @var mysqli $conn */
$result_barang = mysqli_query($conn, $query_barang);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Barang - TB. Pesona Rumah Kita</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 flex h-screen overflow-hidden">

    <aside class="w-64 bg-indigo-900 text-white flex flex-col h-full shadow-lg">
        <div class="p-6 border-b border-indigo-800">
            <h1 class="text-xl font-bold">TB. Pesona Rumah Kita</h1>
            <p class="text-sm text-indigo-300 mt-1">Halo, <?= htmlspecialchars($_SESSION['nama_lengkap']); ?></p>
        </div>
        <nav class="flex-1 p-4 space-y-2">
            <a href="dashboard.php" class="flex items-center p-3 hover:bg-indigo-800 rounded-lg transition-colors">
                <span class="mr-3">📊</span> Dashboard
            </a>
            <a href="master_barang.php" class="flex items-center p-3 bg-indigo-800 rounded-lg font-semibold transition-colors">
                <span class="mr-3">📦</span> Master Barang
            </a>
            <a href="laporan_stok.php" class="flex items-center p-3 hover:bg-indigo-800 rounded-lg transition-colors">
                <span class="mr-3">📄</span> Laporan Penjualan
            </a>
            <a href="pengadaan.php" class="flex items-center p-3 hover:bg-indigo-800 rounded-lg transition-colors">📝 Pengadaan (PO)</a>
            <a href="manajemen_user.php" class="flex items-center p-3 hover:bg-indigo-800 rounded-lg transition-colors">👥 Manajemen Akun</a>
        </nav>
        <div class="p-4 border-t border-indigo-800">
            <a href="../../auth/login.php" class="flex items-center p-3 text-red-300 hover:text-white hover:bg-red-600 rounded-lg transition-colors">
                <span class="mr-3">🚪</span> Logout
            </a>
        </div>
    </aside>

    <main class="flex-1 p-8 overflow-y-auto">

        <?php if (isset($_GET['pesan'])): ?>
            <?php if ($_GET['pesan'] == 'hapus_sukses'): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline">Mantap! Data barang berhasil dihapus secara permanen.</span>
                </div>
            <?php elseif ($_GET['pesan'] == 'hapus_gagal'): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline"><strong>Gagal menghapus!</strong> Barang ini dilindungi oleh sistem karena masih terikat dengan Aturan Konversi Satuan atau memiliki riwayat Transaksi.</span>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Data Master Barang</h2>
            <a href="tambah_barang.php" class="bg-indigo-600 hover:bg-indigo-800 text-white font-bold py-2 px-4 rounded shadow transition-colors inline-block">
                + Tambah Barang Baru
            </a>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-gray-700 text-sm uppercase">
                            <th class="py-4 px-6 border-b">SKU</th>
                            <th class="py-4 px-6 border-b">Nama Barang</th>
                            <th class="py-4 px-6 border-b">Kategori</th>
                            <th class="py-4 px-6 border-b text-right">Harga Modal</th>
                            <th class="py-4 px-6 border-b text-right">Harga Jual</th>
                            <th class="py-4 px-6 border-b text-center">Stok</th>
                            <th class="py-4 px-6 border-b text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 text-sm">
                        <?php if (mysqli_num_rows($result_barang) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result_barang)): ?>
                                <tr class="hover:bg-gray-50 border-b">
                                    <td class="py-4 px-6 font-mono font-semibold"><?= $row['kode_sku']; ?></td>
                                    <td class="py-4 px-6"><?= $row['nama_barang']; ?></td>
                                    <td class="py-4 px-6">
                                        <span class="bg-gray-200 text-gray-700 py-1 px-2 rounded-full text-xs"><?= $row['nama_kategori']; ?></span>
                                    </td>
                                    <td class="py-4 px-6 text-right">Rp <?= number_format($row['harga_modal'], 0, ',', '.'); ?></td>
                                    <td class="py-4 px-6 text-right text-green-600 font-semibold">Rp <?= number_format($row['harga_jual'], 0, ',', '.'); ?></td>
                                    <td class="py-4 px-6 text-center">
                                        <?php if ($row['stok_aktual'] <= $row['stok_minimum']): ?>
                                            <span class="text-red-600 font-bold"><?= $row['stok_aktual']; ?></span>
                                        <?php else: ?>
                                            <span class="text-gray-800 font-bold"><?= $row['stok_aktual']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6 text-center flex justify-center space-x-2">
                                        <a href="edit_barang.php?id=<?= $row['id_barang']; ?>" class="bg-amber-400 hover:bg-amber-500 text-white py-1 px-3 rounded text-xs font-bold transition-colors">Edit</a>

                                        <a href="hapus_barang.php?id=<?= $row['id_barang']; ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus data <?= $row['nama_barang']; ?> ini?');" class="bg-red-500 hover:bg-red-600 text-white py-1 px-3 rounded text-xs font-bold transition-colors">Hapus</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="py-6 text-center text-gray-500 italic">Belum ada data barang.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

</body>

</html>