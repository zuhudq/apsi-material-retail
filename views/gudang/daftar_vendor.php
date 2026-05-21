<?php
session_start();

// Proteksi Halaman: Khusus Staf Gudang (dan Pemilik jika ingin akses)
if (!isset($_SESSION['id_user']) || ($_SESSION['role'] !== 'Staf Gudang' && $_SESSION['role'] !== 'Pemilik')) {
    header("Location: ../../auth/login.php");
    exit;
}

require_once '../../config/koneksi.php';
/** @var mysqli $conn */

// Mengambil seluruh data vendor dari database
$query_vendor = mysqli_query($conn, "SELECT * FROM vendor ORDER BY nama_vendor ASC");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Vendor - Modul Gudang</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .custom-scroll::-webkit-scrollbar {
            width: 8px;
        }

        .custom-scroll::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 4px;
        }
    </style>
</head>

<body class="bg-gray-100 flex h-screen overflow-hidden">

    <aside class="w-64 bg-teal-900 text-white flex flex-col h-full shadow-lg">
        <div class="p-6 border-b border-teal-800">
            <h1 class="text-xl font-bold">Modul Gudang</h1>
            <p class="text-sm text-teal-300 mt-1">Halo, <?= htmlspecialchars($_SESSION['nama_lengkap']); ?></p>
        </div>
        <nav class="flex-1 p-4 space-y-2">
            <a href="pantau_stok.php" class="flex items-center p-3 bg-teal-800 rounded-lg font-semibold transition-colors">
                <span class="mr-3">👁️</span> Pantau Stok
            </a>
            <a href="inbound.php" class="flex items-center p-3 hover:bg-teal-800 rounded-lg transition-colors">
                <span class="mr-3">📥</span> Terima Barang
            </a>
            <a href="konversi.php" class="flex items-center p-3 hover:bg-teal-800 rounded-lg transition-colors">
                <span class="mr-3">🔄</span> Konversi Satuan
            </a>
            <a href="stok_opname.php" class="flex items-center p-3 hover:bg-teal-800 rounded-lg transition-colors">
                <span class="mr-3">📋</span> Stok Opname
            </a>
            <a href="daftar_vendor.php" class="flex items-center p-3 hover:bg-teal-800 rounded-lg transition-colors">
                <span class="mr-3">🏢</span> Data Vendor
            </a>
        </nav>
        <div class="p-4 border-t border-teal-800">
            <a href="../../auth/login.php" class="flex items-center p-3 text-red-300 hover:text-white hover:bg-red-600 rounded-lg transition-colors">
                <span class="mr-3">🚪</span> Logout
            </a>
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-full p-8 bg-gray-50 overflow-y-auto custom-scroll">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Informasi Vendor & Supplier</h2>
            <p class="text-gray-500 text-sm mt-1">Daftar kontak perusahaan material yang bekerja sama dengan TB. Pesona Rumah Kita.</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-teal-50 text-teal-900 text-sm uppercase">
                            <th class="py-4 px-6 border-b w-1/4">Nama Perusahaan</th>
                            <th class="py-4 px-6 border-b w-1/6">Kontak / No. Telp</th>
                            <th class="py-4 px-6 border-b w-1/3">Alamat</th>
                            <th class="py-4 px-6 border-b">Catatan Supplier</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 text-sm">
                        <?php if (mysqli_num_rows($query_vendor) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($query_vendor)): ?>
                                <tr class="hover:bg-gray-50 border-b transition-colors">
                                    <td class="py-4 px-6 font-bold text-gray-800">
                                        🏢 <?= htmlspecialchars($row['nama_vendor']); ?>
                                    </td>
                                    <td class="py-4 px-6 font-mono text-teal-700 font-semibold">
                                        📞 <?= htmlspecialchars($row['kontak']); ?>
                                    </td>
                                    <td class="py-4 px-6 text-gray-600">
                                        <?= htmlspecialchars($row['alamat']); ?>
                                    </td>
                                    <td class="py-4 px-6 text-xs text-gray-500 italic">
                                        <?= htmlspecialchars($row['keterangan']); ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="py-8 text-center text-gray-400 italic">
                                    <span class="text-3xl block mb-2">📭</span>
                                    Belum ada data vendor yang terdaftar di sistem.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>

</html>