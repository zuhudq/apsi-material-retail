<?php
session_start();

// Proteksi Halaman
if (!isset($_SESSION['id_user']) || ($_SESSION['role'] !== 'Staf Gudang' && $_SESSION['role'] !== 'Pemilik')) {
    header("Location: ../../auth/login.php");
    exit;
}

require_once '../../config/koneksi.php';
/** @var mysqli $conn */

$error = '';
$success = '';

// Memproses konversi saat form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_konversi = intval($_POST['id_konversi']);
    $qty_sumber = floatval($_POST['qty_sumber']);
    $id_user = $_SESSION['id_user'];
    $tanggal = date('Y-m-d H:i:s');

    // Tarik data detail aturan konversi
    $query_aturan = mysqli_query($conn, "SELECT * FROM konversi_satuan WHERE id_konversi = $id_konversi");
    $aturan = mysqli_fetch_assoc($query_aturan);

    $id_sumber = $aturan['id_barang_besar'];
    $id_target = $aturan['id_barang_kecil'];
    $faktor = $aturan['faktor_konversi'];
    $qty_target = $qty_sumber * $faktor;

    // Cek ketersediaan stok sumber
    $cek_stok = mysqli_query($conn, "SELECT stok_aktual, nama_barang FROM barang WHERE id_barang = $id_sumber");
    $data_stok = mysqli_fetch_assoc($cek_stok);

    if ($data_stok['stok_aktual'] < $qty_sumber) {
        $error = "Gagal! Stok '{$data_stok['nama_barang']}' tidak mencukupi untuk dikonversi. Sisa stok: " . $data_stok['stok_aktual'];
    } else {
        // Mulai Transaksi Atomik
        mysqli_begin_transaction($conn);

        try {
            // 1. Kurangi stok barang besar (Sumber)
            mysqli_query($conn, "UPDATE barang SET stok_aktual = stok_aktual - $qty_sumber WHERE id_barang = $id_sumber");

            // 2. Tambah stok barang kecil (Target)
            mysqli_query($conn, "UPDATE barang SET stok_aktual = stok_aktual + $qty_target WHERE id_barang = $id_target");

            // 3. Catat di Log Konversi (Audit Trail)
            mysqli_query($conn, "INSERT INTO log_konversi (id_konversi, id_user, tanggal, qty_sumber, qty_target) 
                                 VALUES ($id_konversi, $id_user, '$tanggal', $qty_sumber, $qty_target)");

            // Resmikan perubahan
            mysqli_commit($conn);
            $success = "Konversi berhasil! $qty_sumber Satuan Besar telah dipecah menjadi $qty_target Satuan Eceran.";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Terjadi kesalahan sistem saat memproses konversi: " . $e->getMessage();
        }
    }
}

// Mengambil daftar aturan konversi beserta nama barang dan stok aktualnya untuk form
$query_list = "SELECT k.id_konversi, k.faktor_konversi, k.deskripsi,
                      b1.nama_barang AS nama_sumber, b1.stok_aktual AS stok_sumber,
                      b2.nama_barang AS nama_target, b2.stok_aktual AS stok_target
               FROM konversi_satuan k
               JOIN barang b1 ON k.id_barang_besar = b1.id_barang
               JOIN barang b2 ON k.id_barang_kecil = b2.id_barang";
$result_list = mysqli_query($conn, $query_list);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konversi Satuan - Staf Gudang</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 flex h-screen overflow-hidden">

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

    <main class="flex-1 p-8 overflow-y-auto">
        <div class="flex items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Konversi Satuan Material</h2>
        </div>

        <?php if ($error != ''): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6"><?= $error; ?></div>
        <?php endif; ?>
        <?php if ($success != ''): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6"><?= $success; ?></div>
        <?php endif; ?>

        <div class="bg-white p-8 rounded-lg shadow max-w-3xl">
            <form action="" method="POST" id="form-konversi">

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Pilih Item yang Akan Dipecah (Aturan Konversi)</label>
                    <select name="id_konversi" id="id_konversi" required class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-teal-500 bg-gray-50 text-gray-800">
                        <option value="" disabled selected>-- Pilih Aturan Konversi Tersedia --</option>
                        <?php while ($row = mysqli_fetch_assoc($result_list)): ?>
                            <option value="<?= $row['id_konversi']; ?>"
                                data-sumber="<?= $row['nama_sumber']; ?>"
                                data-stoksumber="<?= $row['stok_sumber']; ?>"
                                data-target="<?= $row['nama_target']; ?>"
                                data-stoktarget="<?= $row['stok_target']; ?>"
                                data-faktor="<?= $row['faktor_konversi']; ?>">
                                <?= $row['nama_sumber']; ?> ➔ <?= $row['nama_target']; ?> (1 = <?= $row['faktor_konversi']; ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="flex justify-center mb-4">
                    <span class="text-2xl text-teal-600">⬇️</span>
                </div>

                <div class="mb-8">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Jumlah Satuan Besar yang Dipecah</label>
                    <input type="number" step="0.01" min="0.01" name="qty_sumber" id="qty_sumber" required placeholder="Contoh: 1" class="w-full border border-gray-300 rounded px-4 py-3 text-lg font-bold text-center focus:ring-teal-500">
                </div>

                <div id="panel-pratinjau" class="bg-blue-50 border border-blue-200 rounded-lg p-5 mb-8 hidden">
                    <h3 class="text-blue-800 font-bold mb-3 flex items-center">
                        <span class="mr-2">ℹ️</span> Pratinjau Rekonsiliasi
                    </h3>
                    <div class="text-sm space-y-2">
                        <p class="text-gray-700">Stok <strong id="lbl_sumber" class="text-gray-900">-</strong> akan berkurang:
                            <span class="font-mono bg-white px-2 py-1 border rounded" id="calc_sumber">0 ➔ 0</span>
                        </p>
                        <p class="text-gray-700">Stok <strong id="lbl_target" class="text-gray-900">-</strong> akan bertambah:
                            <span class="font-mono bg-white px-2 py-1 border rounded" id="calc_target">0 ➔ 0</span>
                        </p>
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <button type="reset" class="px-6 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-100 font-bold transition-colors">Batal</button>
                    <button type="submit" id="btn-submit" class="bg-teal-700 hover:bg-teal-900 text-white px-8 py-2 rounded shadow font-bold transition-colors">
                        Konfirmasi Konversi
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectKonversi = document.getElementById('id_konversi');
            const inputQty = document.getElementById('qty_sumber');
            const panelPratinjau = document.getElementById('panel-pratinjau');

            const lblSumber = document.getElementById('lbl_sumber');
            const lblTarget = document.getElementById('lbl_target');
            const calcSumber = document.getElementById('calc_sumber');
            const calcTarget = document.getElementById('calc_target');
            const btnSubmit = document.getElementById('btn-submit');

            function updatePreview() {
                const option = selectKonversi.options[selectKonversi.selectedIndex];
                const qty = parseFloat(inputQty.value) || 0;

                if (selectKonversi.value === "") {
                    panelPratinjau.classList.add('hidden');
                    return;
                }

                panelPratinjau.classList.remove('hidden');

                // Ambil data dari atribut custom (data-*) HTML
                const namaSumber = option.getAttribute('data-sumber');
                const namaTarget = option.getAttribute('data-target');
                const stokSumber = parseFloat(option.getAttribute('data-stoksumber'));
                const stokTarget = parseFloat(option.getAttribute('data-stoktarget'));
                const faktor = parseFloat(option.getAttribute('data-faktor'));

                lblSumber.textContent = namaSumber;
                lblTarget.textContent = namaTarget;

                const hasilSumber = stokSumber - qty;
                const qtyTambah = qty * faktor;
                const hasilTarget = stokTarget + qtyTambah;

                // Update text
                calcSumber.innerHTML = `${stokSumber} <span class="text-red-500 font-bold">(-${qty})</span> ➔ <strong>${hasilSumber}</strong>`;
                calcTarget.innerHTML = `${stokTarget} <span class="text-green-600 font-bold">(+${qtyTambah})</span> ➔ <strong>${hasilTarget}</strong>`;

                // Validasi Frontend (Cegah submit jika stok minus)
                if (hasilSumber < 0) {
                    calcSumber.innerHTML += ` <span class="text-red-600 text-xs ml-2">⚠️ Stok tidak cukup!</span>`;
                    btnSubmit.disabled = true;
                    btnSubmit.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    btnSubmit.disabled = false;
                    btnSubmit.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }

            selectKonversi.addEventListener('change', updatePreview);
            inputQty.addEventListener('input', updatePreview);
        });
    </script>
</body>

</html>