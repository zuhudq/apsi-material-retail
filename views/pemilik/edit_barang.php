<?php
session_start();

// Proteksi Halaman
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'Pemilik') {
    header("Location: ../../auth/login.php");
    exit;
}

require_once '../../config/koneksi.php';
/** @var mysqli $conn */

$error = '';
$success = '';

// Memastikan ada ID yang dikirim
if (!isset($_GET['id'])) {
    header("Location: master_barang.php");
    exit;
}

$id_barang = intval($_GET['id']);

// Ambil data barang yang akan diedit
$query_item = "SELECT * FROM barang WHERE id_barang = $id_barang";
$result_item = mysqli_query($conn, $query_item);

if (mysqli_num_rows($result_item) == 0) {
    header("Location: master_barang.php");
    exit;
}
$item = mysqli_fetch_assoc($result_item);

// Ambil data kategori untuk dropdown
$query_kategori = "SELECT * FROM kategori ORDER BY nama_kategori ASC";
$result_kategori = mysqli_query($conn, $query_kategori);

// Memproses pembaruan data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kode_sku = mysqli_real_escape_string($conn, $_POST['kode_sku']);
    $nama_barang = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $id_kategori = mysqli_real_escape_string($conn, $_POST['id_kategori']);
    $harga_modal = mysqli_real_escape_string($conn, $_POST['harga_modal']);
    $harga_jual = mysqli_real_escape_string($conn, $_POST['harga_jual']);
    $stok_minimum = mysqli_real_escape_string($conn, $_POST['stok_minimum']);

    // Cek apakah SKU diubah ke SKU yang sudah dipakai barang lain
    $cek_sku = mysqli_query($conn, "SELECT id_barang FROM barang WHERE kode_sku = '$kode_sku' AND id_barang != $id_barang");

    if (mysqli_num_rows($cek_sku) > 0) {
        $error = "Gagal! Kode SKU '$kode_sku' sudah digunakan barang lain.";
    } else {
        // Eksekusi Update ke tabel barang
        // Catatan: Stok Aktual TIDAK BISA DIEDIT di sini untuk menjaga integritas audit
        $update_query = "UPDATE barang SET 
                            id_kategori = '$id_kategori',
                            kode_sku = '$kode_sku',
                            nama_barang = '$nama_barang',
                            harga_modal = '$harga_modal',
                            harga_jual = '$harga_jual',
                            stok_minimum = '$stok_minimum'
                         WHERE id_barang = $id_barang";

        if (mysqli_query($conn, $update_query)) {
            $success = "Mantap! Data barang berhasil diperbarui.";
            // Perbarui array $item agar form menampilkan data yang baru di-update
            $item['kode_sku'] = $kode_sku;
            $item['nama_barang'] = $nama_barang;
            $item['id_kategori'] = $id_kategori;
            $item['harga_modal'] = $harga_modal;
            $item['harga_jual'] = $harga_jual;
            $item['stok_minimum'] = $stok_minimum;
        } else {
            $error = "Terjadi kesalahan sistem: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Barang - TB. Pesona Rumah Kita</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 flex h-screen overflow-hidden">
    <aside class="w-64 bg-indigo-900 text-white flex flex-col h-full shadow-lg">
        <div class="p-6 border-b border-indigo-800">
            <h1 class="text-xl font-bold">TB. Pesona Rumah Kita</h1>
        </div>
        <nav class="flex-1 p-4 space-y-2">
            <a href="dashboard.php" class="flex items-center p-3 hover:bg-indigo-800 rounded-lg transition-colors">📊 Dashboard</a>
            <a href="master_barang.php" class="flex items-center p-3 bg-indigo-800 rounded-lg font-semibold transition-colors">📦 Master Barang</a>
        </nav>
    </aside>

    <main class="flex-1 p-8 overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Edit Data Barang</h2>
            <a href="master_barang.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded shadow transition-colors text-sm font-bold">
                Kembali ke Daftar
            </a>
        </div>

        <div class="bg-white p-8 rounded-lg shadow max-w-4xl">
            <?php if ($error != ''): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6"><?= $error; ?></div>
            <?php endif; ?>
            <?php if ($success != ''): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6"><?= $success; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Kode SKU</label>
                        <input type="text" name="kode_sku" value="<?= htmlspecialchars($item['kode_sku']); ?>" required
                            class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:border-indigo-500 uppercase">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Kategori</label>
                        <select name="id_kategori" required class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:border-indigo-500">
                            <?php
                            mysqli_data_seek($result_kategori, 0); // Reset pointer
                            while ($kat = mysqli_fetch_assoc($result_kategori)):
                                $selected = ($kat['id_kategori'] == $item['id_kategori']) ? 'selected' : '';
                            ?>
                                <option value="<?= $kat['id_kategori']; ?>" <?= $selected; ?>><?= htmlspecialchars($kat['nama_kategori']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Nama Barang Lengkap</label>
                    <input type="text" name="nama_barang" value="<?= htmlspecialchars($item['nama_barang']); ?>" required
                        class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:border-indigo-500">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Harga Modal (Rp)</label>
                        <input type="number" name="harga_modal" value="<?= floatval($item['harga_modal']); ?>" required
                            class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Harga Jual (Rp)</label>
                        <input type="number" name="harga_jual" value="<?= floatval($item['harga_jual']); ?>" required
                            class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:border-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Stok Aktual (Sistem)</label>
                        <input type="number" value="<?= floatval($item['stok_aktual']); ?>" disabled
                            class="w-full bg-gray-100 border border-gray-300 rounded px-4 py-2 cursor-not-allowed">
                        <p class="text-xs text-red-500 mt-1">Stok aktual tidak dapat diubah di sini. Harus melalui transaksi POS atau Penerimaan Barang.</p>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Batas Stok Minimum</label>
                        <input type="number" step="0.01" name="stok_minimum" value="<?= floatval($item['stok_minimum']); ?>" required
                            class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:border-indigo-500">
                    </div>
                </div>
                <hr class="mb-6">
                <div class="flex justify-end">
                    <button type="submit" class="bg-indigo-600 text-white py-2 px-6 rounded font-bold hover:bg-indigo-800 shadow transition-colors">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>

</html>