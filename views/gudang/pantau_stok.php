<?php
session_start();

if (!isset($_SESSION['id_user']) || ($_SESSION['role'] !== 'Staf Gudang' && $_SESSION['role'] !== 'Pemilik')) {
    header("Location: ../../auth/login.php");
    exit;
}

require_once '../../config/koneksi.php';
/** @var mysqli $conn */

// Mengambil seluruh data barang beserta nama kategorinya
$query_stok = mysqli_query($conn, "SELECT b.*, k.nama_kategori 
                                   FROM barang b 
                                   JOIN kategori k ON b.id_kategori = k.id_kategori 
                                   ORDER BY b.nama_barang ASC");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pantau Stok - Modul Gudang</title>
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
            <a href="../../auth/login.php" class="flex items-center p-3 text-red-300 hover:text-white hover:bg-red-600 rounded-lg transition-colors">🚪 Logout</a>
        </div>
    </aside>

    <main class="flex-1 p-8 overflow-y-auto custom-scroll bg-gray-50">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Pemantauan Stok Real-Time</h2>
                <p class="text-gray-500 text-sm mt-1">Visibilitas inventaris gudang secara langsung. (Mode: Read-Only)</p>
            </div>
            <div class="w-1/3">
                <input type="text" id="pencarian" onkeyup="cariBarang()" placeholder="🔍 Cari SKU atau Nama Barang..." class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:border-teal-500 shadow-sm">
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <table class="w-full text-left border-collapse" id="tabelStok">
                <thead>
                    <tr class="bg-teal-50 text-teal-900 text-sm uppercase">
                        <th class="py-4 px-6 border-b">SKU</th>
                        <th class="py-4 px-6 border-b">Nama Barang</th>
                        <th class="py-4 px-6 border-b">Kategori</th>
                        <th class="py-4 px-6 border-b text-center">Stok Fisik Saat Ini</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    <?php while ($row = mysqli_fetch_assoc($query_stok)): ?>
                        <tr class="hover:bg-gray-50 border-b baris-barang">
                            <td class="py-4 px-6 font-mono text-gray-500 sku-item"><?= $row['kode_sku']; ?></td>
                            <td class="py-4 px-6 font-bold text-gray-800 nama-item"><?= $row['nama_barang']; ?></td>
                            <td class="py-4 px-6"><span class="bg-gray-100 px-2 py-1 rounded text-xs"><?= $row['nama_kategori']; ?></span></td>
                            <td class="py-4 px-6 text-center">
                                <?php if ($row['stok_aktual'] <= $row['stok_minimum']): ?>
                                    <span class="bg-red-100 text-red-700 font-bold px-3 py-1 rounded-full"><?= floatval($row['stok_aktual']); ?> (Kritis)</span>
                                <?php else: ?>
                                    <span class="text-teal-700 font-bold text-lg"><?= floatval($row['stok_aktual']); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        function cariBarang() {
            let input = document.getElementById("pencarian").value.toLowerCase();
            let baris = document.getElementsByClassName("baris-barang");

            for (let i = 0; i < baris.length; i++) {
                let sku = baris[i].querySelector(".sku-item").innerText.toLowerCase();
                let nama = baris[i].querySelector(".nama-item").innerText.toLowerCase();

                if (sku.includes(input) || nama.includes(input)) {
                    baris[i].style.display = "";
                } else {
                    baris[i].style.display = "none";
                }
            }
        }
    </script>
</body>

</html>