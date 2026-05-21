<?php
session_start();

// Proteksi Halaman Khusus Gudang/Pemilik
if (!isset($_SESSION['id_user']) || ($_SESSION['role'] !== 'Staf Gudang' && $_SESSION['role'] !== 'Pemilik')) {
    header("Location: ../../auth/login.php");
    exit;
}

require_once '../../config/koneksi.php';
/** @var mysqli $conn */

// --- AUTO MIGRATE: Buat tabel riwayat_opname jika belum ada ---
$cek_tabel = mysqli_query($conn, "SHOW TABLES LIKE 'riwayat_opname'");
if (mysqli_num_rows($cek_tabel) == 0) {
    mysqli_query($conn, "CREATE TABLE riwayat_opname (
        id_opname INT AUTO_INCREMENT PRIMARY KEY,
        id_barang INT,
        id_user INT,
        tanggal DATETIME,
        jenis ENUM('Penambahan', 'Pengurangan'),
        qty INT,
        keterangan TEXT
    )");
}

$success = '';
$error = '';

// --- PROSES FORM PENYESUAIAN STOK ---
if (isset($_POST['simpan_opname'])) {
    $id_barang = intval($_POST['id_barang']);
    $jenis = mysqli_real_escape_string($conn, $_POST['jenis']);
    $qty = floatval($_POST['qty']);
    $ket = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $id_user = $_SESSION['id_user'];
    $tgl = date('Y-m-d H:i:s');

    if ($qty <= 0) {
        $error = "Gagal! Kuantitas harus lebih besar dari 0.";
    } else {
        mysqli_begin_transaction($conn);
        try {
            // 1. Update stok di tabel barang
            if ($jenis == 'Penambahan') {
                mysqli_query($conn, "UPDATE barang SET stok_aktual = stok_aktual + $qty WHERE id_barang = $id_barang");
            } else {
                mysqli_query($conn, "UPDATE barang SET stok_aktual = stok_aktual - $qty WHERE id_barang = $id_barang");
            }

            // 2. Catat riwayat audit
            mysqli_query($conn, "INSERT INTO riwayat_opname (id_barang, id_user, tanggal, jenis, qty, keterangan) 
                                 VALUES ($id_barang, $id_user, '$tgl', '$jenis', $qty, '$ket')");

            mysqli_commit($conn);
            $success = "Penyesuaian stok berhasil disimpan dan dicatat dalam riwayat audit!";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Terjadi kesalahan sistem saat menyimpan data.";
        }
    }
}

// Ambil data barang untuk dropdown
$q_barang = mysqli_query($conn, "SELECT id_barang, kode_sku, nama_barang, stok_aktual FROM barang ORDER BY nama_barang ASC");

// Ambil riwayat opname terbaru
$q_riwayat = mysqli_query($conn, "SELECT r.*, b.nama_barang, u.nama_lengkap 
                                  FROM riwayat_opname r 
                                  JOIN barang b ON r.id_barang = b.id_barang 
                                  JOIN users u ON r.id_user = u.id_user 
                                  ORDER BY r.tanggal DESC LIMIT 15");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Opname - Modul Gudang</title>
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
            <h2 class="text-2xl font-bold text-gray-800">Stok Opname & Penyesuaian</h2>
            <p class="text-gray-500 text-sm mt-1">Lakukan penyesuaian jika terjadi selisih antara stok fisik di gudang dengan data sistem (Barang Rusak, Hilang, atau Kelebihan).</p>
        </div>

        <?php if ($error != ''): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 shadow-sm">
                <p><?= $error; ?></p>
            </div>
        <?php endif; ?>
        <?php if ($success != ''): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 shadow-sm">
                <p><?= $success; ?></p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 lg:col-span-1 h-fit">
                <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Form Penyesuaian Stok</h3>
                <form action="" method="POST">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Pilih Barang</label>
                        <select name="id_barang" id="id_barang" required class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-teal-500 text-sm" onchange="tampilkanSisaStok()">
                            <option value="">-- Cari Nama Barang / SKU --</option>
                            <?php while ($b = mysqli_fetch_assoc($q_barang)): ?>
                                <option value="<?= $b['id_barang']; ?>" data-stok="<?= floatval($b['stok_aktual']); ?>">
                                    [<?= $b['kode_sku']; ?>] <?= $b['nama_barang']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Stok Tercatat Saat Ini: <strong id="stok_tercatat" class="text-teal-600">-</strong></p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Jenis Penyesuaian</label>
                        <div class="flex space-x-4">
                            <label class="flex items-center">
                                <input type="radio" name="jenis" value="Pengurangan" required class="mr-2 text-teal-600" checked>
                                <span class="text-sm text-red-600 font-bold">Kurangi (-)</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="jenis" value="Penambahan" required class="mr-2 text-teal-600">
                                <span class="text-sm text-green-600 font-bold">Tambah (+)</span>
                            </label>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Jumlah / Kuantitas Selisih</label>
                        <input type="number" step="any" name="qty" required min="0.1" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-teal-500" placeholder="Contoh: 2">
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Alasan Penyesuaian</label>
                        <textarea name="keterangan" required rows="3" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:border-teal-500 text-sm" placeholder="Contoh: 2 sak semen mengeras terkena air hujan"></textarea>
                    </div>

                    <button type="submit" name="simpan_opname" onclick="return confirm('Apakah data penyesuaian sudah benar? Aksi ini akan mengubah saldo inventaris.');" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-bold py-3 px-4 rounded shadow transition-colors">
                        Simpan Penyesuaian
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 lg:col-span-2">
                <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Riwayat Audit Opname Terakhir</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-100 text-gray-700 text-xs uppercase">
                                <th class="py-3 px-4 border-b">Waktu & Petugas</th>
                                <th class="py-3 px-4 border-b">Barang</th>
                                <th class="py-3 px-4 border-b text-center">Selisih</th>
                                <th class="py-3 px-4 border-b w-1/3">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 text-sm">
                            <?php if (mysqli_num_rows($q_riwayat) > 0): ?>
                                <?php while ($r = mysqli_fetch_assoc($q_riwayat)): ?>
                                    <tr class="hover:bg-gray-50 border-b">
                                        <td class="py-3 px-4">
                                            <div class="font-bold text-xs"><?= date('d M Y, H:i', strtotime($r['tanggal'])); ?></div>
                                            <div class="text-xs text-gray-500">Oleh: <?= htmlspecialchars($r['nama_lengkap']); ?></div>
                                        </td>
                                        <td class="py-3 px-4 font-semibold text-xs"><?= $r['nama_barang']; ?></td>
                                        <td class="py-3 px-4 text-center font-bold">
                                            <?php if ($r['jenis'] == 'Penambahan'): ?>
                                                <span class="text-green-600">+<?= floatval($r['qty']); ?></span>
                                            <?php else: ?>
                                                <span class="text-red-600">-<?= floatval($r['qty']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-4 text-xs italic text-gray-600">
                                            "<?= htmlspecialchars($r['keterangan']); ?>"
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-gray-400 italic">Belum ada riwayat penyesuaian stok.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <script>
        // Fitur pintar untuk menampilkan sisa stok secara real-time saat barang dipilih
        function tampilkanSisaStok() {
            let select = document.getElementById('id_barang');
            let stokTercatat = document.getElementById('stok_tercatat');

            if (select.selectedIndex > 0) {
                let opsiDipilih = select.options[select.selectedIndex];
                let jumlahStok = opsiDipilih.getAttribute('data-stok');
                stokTercatat.innerText = jumlahStok;
            } else {
                stokTercatat.innerText = '-';
            }
        }
    </script>
</body>

</html>