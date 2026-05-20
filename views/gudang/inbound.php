<?php
session_start();

// Proteksi Halaman: Hanya Staf Gudang (dan Pemilik) yang boleh akses
if (!isset($_SESSION['id_user']) || ($_SESSION['role'] !== 'Staf Gudang' && $_SESSION['role'] !== 'Pemilik')) {
    header("Location: ../../auth/login.php");
    exit;
}

require_once '../../config/koneksi.php';
/** @var mysqli $conn */

$error = '';
$success = '';

// Ambil data supplier dan barang untuk dropdown
$query_supplier = mysqli_query($conn, "SELECT * FROM supplier ORDER BY nama_supplier ASC");
$query_barang = mysqli_query($conn, "SELECT id_barang, kode_sku, nama_barang FROM barang ORDER BY nama_barang ASC");

// Simpan data barang ke array untuk dipakai di JavaScript (Dynamic Row)
$barang_options = "";
while ($b = mysqli_fetch_assoc($query_barang)) {
    $barang_options .= "<option value='{$b['id_barang']}'>{$b['kode_sku']} - {$b['nama_barang']}</option>";
}

// Proses form saat disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_supplier = intval($_POST['id_supplier']);
    $no_sj = mysqli_real_escape_string($conn, $_POST['no_sj']);
    $tanggal_terima = mysqli_real_escape_string($conn, $_POST['tanggal_terima']);
    $catatan = mysqli_real_escape_string($conn, $_POST['catatan']);
    $id_user = $_SESSION['id_user'];

    // Memulai Transaksi Atomik MySQL
    mysqli_begin_transaction($conn);

    try {
        // 1. Simpan Header Penerimaan
        $query_terima = "INSERT INTO penerimaan (id_supplier, id_user, tanggal_terima, no_sj, catatan) 
                         VALUES ($id_supplier, $id_user, '$tanggal_terima', '$no_sj', '$catatan')";
        mysqli_query($conn, $query_terima);

        // Ambil ID penerimaan yang baru saja terbuat
        $id_terima = mysqli_insert_id($conn);

        // 2. Looping array item barang yang diinput dinamis
        $items = $_POST['id_barang'];
        $qtys = $_POST['kuantitas'];
        $satuans = $_POST['satuan'];
        $hargas = $_POST['harga_beli'];

        for ($i = 0; $i < count($items); $i++) {
            $id_barang = intval($items[$i]);
            $qty = floatval($qtys[$i]);
            $satuan = mysqli_real_escape_string($conn, $satuans[$i]);
            $harga = floatval($hargas[$i]);

            // Insert ke tabel detail_terima
            $query_detail = "INSERT INTO detail_terima (id_terima, id_barang, kuantitas_terima, satuan_terima, harga_beli) 
                             VALUES ($id_terima, $id_barang, $qty, '$satuan', $harga)";
            mysqli_query($conn, $query_detail);

            // 3. Update stok aktual di tabel barang
            $query_update_stok = "UPDATE barang SET stok_aktual = stok_aktual + $qty WHERE id_barang = $id_barang";
            mysqli_query($conn, $query_update_stok);
        }

        // Jika semua query sukses tanpa error, resmikan perubahan di database
        mysqli_commit($conn);
        $success = "Penerimaan barang dari Surat Jalan '$no_sj' berhasil disimpan dan stok telah diperbarui otomatis!";
    } catch (Exception $e) {
        // Jika ada 1 saja query yang gagal, batalkan semuanya (Rollback) agar data tidak belang
        mysqli_rollback($conn);
        $error = "Gagal memproses penerimaan: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penerimaan Barang - Staf Gudang</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 flex h-screen overflow-hidden">

    <aside class="w-64 bg-teal-900 text-white flex flex-col h-full shadow-lg">
        <div class="p-6 border-b border-teal-800">
            <h1 class="text-xl font-bold">Modul Gudang</h1>
            <p class="text-sm text-teal-300 mt-1">Halo, <?= htmlspecialchars($_SESSION['nama_lengkap']); ?></p>
        </div>
        <nav class="flex-1 p-4 space-y-2">
            <a href="inbound.php" class="flex items-center p-3 bg-teal-800 rounded-lg font-semibold transition-colors">
                <span class="mr-3">📥</span> Terima Barang (Inbound)
            </a>
            <a href="konversi.php" class="flex items-center p-3 hover:bg-teal-800 rounded-lg transition-colors">
                <span class="mr-3">🔄</span> Konversi Satuan
            </a>
        </nav>
        <div class="p-4 border-t border-teal-800">
            <a href="../../auth/login.php" class="flex items-center p-3 text-red-300 hover:text-white hover:bg-red-600 rounded-lg transition-colors">
                <span class="mr-3">🚪</span> Logout
            </a>
        </div>
    </aside>

    <main class="flex-1 p-8 overflow-y-auto">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Input Penerimaan Barang (Surat Jalan)</h2>

        <?php if ($error != ''): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6"><?= $error; ?></div>
        <?php endif; ?>
        <?php if ($success != ''): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6"><?= $success; ?></div>
        <?php endif; ?>

        <form action="" method="POST" class="bg-white p-6 rounded-lg shadow">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">No. Surat Jalan</label>
                    <input type="text" name="no_sj" required class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Tanggal Terima</label>
                    <input type="date" name="tanggal_terima" value="<?= date('Y-m-d'); ?>" required class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Supplier</label>
                    <select name="id_supplier" required class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-teal-500">
                        <option value="" disabled selected>-- Pilih Supplier --</option>
                        <option value="1">PT Supplier Utama (Dummy)</option>
                        <?php
                        if (mysqli_num_rows($query_supplier) > 0) {
                            while ($sup = mysqli_fetch_assoc($query_supplier)) {
                                echo "<option value='{$sup['id_supplier']}'>{$sup['nama_supplier']}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="mb-6 border border-gray-200 rounded">
                <table class="w-full text-left" id="tabel-item">
                    <thead class="bg-gray-100 border-b border-gray-200 text-gray-700 text-sm">
                        <tr>
                            <th class="p-3 w-2/5">Nama Barang / SKU</th>
                            <th class="p-3">Kuantitas</th>
                            <th class="p-3">Satuan</th>
                            <th class="p-3">Harga Beli (Rp)</th>
                            <th class="p-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-item">
                        <tr class="border-b border-gray-100">
                            <td class="p-3">
                                <select name="id_barang[]" required class="w-full border border-gray-300 rounded px-2 py-1">
                                    <option value="" disabled selected>Pilih Barang</option>
                                    <?= $barang_options; ?>
                                </select>
                            </td>
                            <td class="p-3"><input type="number" step="0.01" name="kuantitas[]" required class="w-full border border-gray-300 rounded px-2 py-1"></td>
                            <td class="p-3"><input type="text" name="satuan[]" placeholder="cth: Sak" required class="w-full border border-gray-300 rounded px-2 py-1"></td>
                            <td class="p-3">
                                <input type="number" name="harga_beli[]" placeholder="cth: 40000" title="Harga per satuan" required class="w-full border border-gray-300 rounded px-2 py-1">
                            </td>
                            <td class="p-3 text-center">
                                <button type="button" class="bg-red-500 hover:bg-red-600 text-white rounded px-3 py-1 font-bold hapus-baris">X</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="p-3 bg-gray-50 border-t border-gray-200">
                    <button type="button" id="btn-tambah" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-1 px-4 rounded text-sm transition-colors">
                        + Tambah Baris Item
                    </button>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">Catatan Tambahan (Opsional)</label>
                <textarea name="catatan" rows="2" class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-teal-500"></textarea>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-teal-700 hover:bg-teal-900 text-white font-bold py-2 px-8 rounded shadow transition-colors">
                    Simpan Penerimaan
                </button>
            </div>
        </form>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tbody = document.getElementById('tbody-item');
            const btnTambah = document.getElementById('btn-tambah');

            // Template baris baru
            const templateBaris = `
                <tr class="border-b border-gray-100">
                    <td class="p-3">
                        <select name="id_barang[]" required class="w-full border border-gray-300 rounded px-2 py-1">
                            <option value="" disabled selected>Pilih Barang</option>
                            <?= $barang_options; ?>
                        </select>
                    </td>
                    <td class="p-3"><input type="number" step="0.01" name="kuantitas[]" required class="w-full border border-gray-300 rounded px-2 py-1"></td>
                    <td class="p-3"><input type="text" name="satuan[]" placeholder="cth: Sak" required class="w-full border border-gray-300 rounded px-2 py-1"></td>
                    <td class="p-3"><input type="number" name="harga_beli[]" placeholder="cth: 40000" required class="w-full border border-gray-300 rounded px-2 py-1"></td>
                    <td class="p-3 text-center">
                        <button type="button" class="bg-red-500 hover:bg-red-600 text-white rounded px-3 py-1 font-bold hapus-baris">X</button>
                    </td>
                </tr>
            `;

            // Fungsi tambah baris
            btnTambah.addEventListener('click', function() {
                tbody.insertAdjacentHTML('beforeend', templateBaris);
            });

            // Fungsi hapus baris menggunakan event delegation
            tbody.addEventListener('click', function(e) {
                if (e.target.classList.contains('hapus-baris')) {
                    const rowCount = tbody.getElementsByTagName('tr').length;
                    if (rowCount > 1) {
                        e.target.closest('tr').remove();
                    } else {
                        alert("Minimal harus ada 1 item barang yang diterima!");
                    }
                }
            });
        });
    </script>
</body>

</html>