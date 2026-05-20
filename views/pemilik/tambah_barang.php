<?php
session_start();

// Proteksi Halaman
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'Pemilik') {
    header("Location: ../../auth/login.php");
    exit;
}

require_once '../../config/koneksi.php';

$error = '';
$success = '';

// Ambil data kategori untuk pilihan dropdown
$query_kategori = "SELECT * FROM kategori ORDER BY nama_kategori ASC";
/** @var mysqli $conn */
$result_kategori = mysqli_query($conn, $query_kategori);

// Memproses inputan form ketika tombol simpan ditekan
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kode_sku = mysqli_real_escape_string($conn, $_POST['kode_sku']);
    $nama_barang = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $id_kategori = mysqli_real_escape_string($conn, $_POST['id_kategori']);
    $harga_modal = mysqli_real_escape_string($conn, $_POST['harga_modal']);
    $harga_jual = mysqli_real_escape_string($conn, $_POST['harga_jual']);
    $stok_awal = mysqli_real_escape_string($conn, $_POST['stok_awal']);
    $stok_minimum = mysqli_real_escape_string($conn, $_POST['stok_minimum']);

    // Cek apakah SKU sudah pernah terdaftar (karena SKU sifatnya UNIQUE)
    $cek_sku = mysqli_query($conn, "SELECT id_barang FROM barang WHERE kode_sku = '$kode_sku'");

    if (mysqli_num_rows($cek_sku) > 0) {
        $error = "Gagal! Kode SKU '$kode_sku' sudah digunakan. Silakan pakai SKU lain.";
    } else {
        // Eksekusi Insert ke tabel barang
        $insert_query = "INSERT INTO barang (id_kategori, kode_sku, nama_barang, harga_modal, harga_jual, stok_aktual, stok_minimum) 
                         VALUES ('$id_kategori', '$kode_sku', '$nama_barang', '$harga_modal', '$harga_jual', '$stok_awal', '$stok_minimum')";

        if (mysqli_query($conn, $insert_query)) {
            $success = "Mantap! Data barang '$nama_barang' berhasil ditambahkan.";
            // Reset POST data agar form kosong kembali atau bisa juga redirect
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
    <title>Tambah Barang - TB. Pesona Rumah Kita</title>
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
        </nav>
    </aside>

    <main class="flex-1 p-8 overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Tambah Barang Baru</h2>
            <a href="master_barang.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded shadow transition-colors text-sm font-bold">
                Kembali ke Daftar
            </a>
        </div>

        <div class="bg-white p-8 rounded-lg shadow max-w-4xl">

            <?php if ($error != ''): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline"><?= $error; ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success != ''): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline"><?= $success; ?></span>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Kode SKU</label>
                        <input type="text" name="kode_sku" required placeholder="Contoh: SMN-03"
                            class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 uppercase">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Kategori</label>
                        <select name="id_kategori" required class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:border-indigo-500">
                            <option value="" disabled selected>-- Pilih Kategori --</option>
                            <?php while ($kat = mysqli_fetch_assoc($result_kategori)): ?>
                                <option value="<?= $kat['id_kategori']; ?>"><?= $kat['nama_kategori']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Nama Barang Lengkap</label>
                    <input type="text" name="nama_barang" required placeholder="Contoh: Semen Gresik (Sak)"
                        class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:border-indigo-500">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Harga Modal (Rp)</label>
                        <input type="number" name="harga_modal" required placeholder="50000" min="0"
                            class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:border-indigo-500">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Harga Jual (Rp)</label>
                        <input type="number" name="harga_jual" required placeholder="65000" min="0"
                            class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:border-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Stok Awal (Aktual)</label>
                        <input type="number" step="0.01" name="stok_awal" required placeholder="100" min="0"
                            class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:border-indigo-500">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Batas Stok Minimum</label>
                        <input type="number" step="0.01" name="stok_minimum" required placeholder="10" min="0"
                            class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:border-indigo-500">
                        <p class="text-xs text-gray-500 mt-1">Sistem akan memberi peringatan jika stok di bawah angka ini.</p>
                    </div>
                </div>

                <hr class="mb-6">

                <div class="flex justify-end space-x-3">
                    <button type="reset" class="bg-white border border-gray-300 text-gray-700 py-2 px-6 rounded font-bold hover:bg-gray-50 transition-colors">
                        Reset
                    </button>
                    <button type="submit" class="bg-indigo-600 text-white py-2 px-6 rounded font-bold hover:bg-indigo-800 shadow transition-colors">
                        Simpan Data
                    </button>
                </div>
            </form>
        </div>
    </main>

</body>

</html>