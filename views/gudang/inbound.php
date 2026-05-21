<?php
session_start();

if (!isset($_SESSION['id_user']) || ($_SESSION['role'] !== 'Staf Gudang' && $_SESSION['role'] !== 'Pemilik')) {
    header("Location: ../../auth/login.php");
    exit;
}

require_once '../../config/koneksi.php';
/** @var mysqli $conn */

$success = '';
$error = '';

// Proses Validasi Penerimaan Barang & Laporan Rusak
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['terima_barang'])) {
    $id_po_terima = intval($_POST['id_po']);
    $id_barangs = $_POST['id_barang'];
    $qty_bagus = $_POST['qty_bagus'];
    $qty_rusak = $_POST['qty_rusak'];
    $catatan = $_POST['catatan_kondisi'];

    $ada_rusak = false;

    mysqli_begin_transaction($conn);
    try {
        for ($i = 0; $i < count($id_barangs); $i++) {
            $id_b = intval($id_barangs[$i]);
            $qb = floatval($qty_bagus[$i]);
            $qr = floatval($qty_rusak[$i]);
            $cat = mysqli_real_escape_string($conn, $catatan[$i]);

            if ($qr > 0) $ada_rusak = true;

            // 1. Catat rincian bagus/rusak ke detail_po
            mysqli_query($conn, "UPDATE detail_po SET qty_bagus = $qb, qty_rusak = $qr, catatan_kondisi = '$cat' WHERE id_po = $id_po_terima AND id_barang = $id_b");

            // 2. HANYA tambahkan qty_bagus ke stok aktual toko
            if ($qb > 0) {
                mysqli_query($conn, "UPDATE barang SET stok_aktual = stok_aktual + $qb WHERE id_barang = $id_b");
            }
        }

        // 3. Ubah status PO (Selesai bersih ATAU Selesai dengan masalah klaim)
        $status_baru = $ada_rusak ? 'Selesai dengan Klaim' : 'Selesai';
        mysqli_query($conn, "UPDATE purchase_order SET status = '$status_baru' WHERE id_po = $id_po_terima");

        mysqli_commit($conn);
        if ($ada_rusak) {
            $success = "⚠️ Validasi disimpan! Ditemukan barang rusak. Laporan klaim telah diteruskan ke Pemilik.";
        } else {
            $success = "✅ Validasi berhasil! Semua barang dalam kondisi baik dan stok telah ditambahkan.";
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "Terjadi kesalahan saat validasi barang masuk.";
    }
}

// Ambil Daftar Pengiriman
$query_inbound = mysqli_query($conn, "SELECT po.*, v.nama_vendor 
                                      FROM purchase_order po 
                                      JOIN vendor v ON po.id_vendor = v.id_vendor 
                                      WHERE po.status = 'Dikirim' 
                                      ORDER BY po.tanggal_po ASC");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penerimaan Barang (Inbound) - Gudang</title>
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

    <main class="flex-1 p-8 overflow-y-auto custom-scroll">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Validasi Penerimaan Barang</h2>
            <p class="text-gray-500 text-sm mt-1">Periksa fisik barang dan pisahkan jika ada yang rusak/cacat di jalan.</p>
        </div>

        <?php if ($error != ''): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                <p><?= $error; ?></p>
            </div>
        <?php endif; ?>
        <?php if ($success != ''): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                <p><?= $success; ?></p>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-700 text-sm uppercase">
                        <th class="py-4 px-6 border-b">No. Referensi (PO)</th>
                        <th class="py-4 px-6 border-b">Asal Vendor</th>
                        <th class="py-4 px-6 border-b text-center">Aksi Validasi</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    <?php if (mysqli_num_rows($query_inbound) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($query_inbound)):
                            // Siapkan JSON data barang untuk JavaScript
                            $id_po_curr = $row['id_po'];
                            $q_det = mysqli_query($conn, "SELECT d.id_barang, d.qty_pesan, b.nama_barang, b.kode_sku FROM detail_po d JOIN barang b ON d.id_barang = b.id_barang WHERE d.id_po = $id_po_curr");
                            $items = [];
                            while ($det = mysqli_fetch_assoc($q_det)) {
                                $items[] = $det;
                            }
                            $json_items = htmlspecialchars(json_encode($items), ENT_QUOTES, 'UTF-8');
                        ?>
                            <tr class="hover:bg-gray-50 border-b">
                                <td class="py-4 px-6 font-mono font-bold text-teal-700">PO-<?= sprintf("%05d", $row['id_po']); ?></td>
                                <td class="py-4 px-6 font-semibold"><?= htmlspecialchars($row['nama_vendor']); ?></td>
                                <td class="py-4 px-6 text-center">
                                    <button onclick="bukaModalValidasi(<?= $row['id_po']; ?>, '<?= htmlspecialchars($row['nama_vendor']); ?>', '<?= $json_items; ?>')" class="bg-teal-600 hover:bg-teal-700 text-white py-2 px-4 rounded text-xs font-bold transition-colors">
                                        📦 Cek Fisik & Terima
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="py-10 text-center text-gray-400 italic">Tidak ada pengiriman barang yang dijadwalkan hari ini.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div id="modalValidasi" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg w-2/3 p-6 shadow-xl max-h-[90vh] flex flex-col">
            <div class="flex justify-between items-center mb-4 border-b pb-3">
                <h3 class="text-xl font-bold text-gray-800">Validasi Fisik & Kondisi Barang</h3>
                <button onclick="document.getElementById('modalValidasi').classList.add('hidden')" class="text-gray-500 hover:text-red-500 text-2xl font-bold">&times;</button>
            </div>

            <form action="" method="POST" class="flex-1 overflow-y-auto custom-scroll pr-2">
                <input type="hidden" name="id_po" id="val_id_po">
                <p class="text-sm text-gray-600 mb-4">Surat Jalan / PO dari: <strong id="val_vendor" class="text-teal-700"></strong></p>

                <div id="val_list_barang" class="space-y-4 mb-6"></div>

                <div class="flex justify-end space-x-2 border-t pt-4">
                    <button type="button" onclick="document.getElementById('modalValidasi').classList.add('hidden')" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">Batal</button>
                    <button type="submit" name="terima_barang" onclick="return confirm('Data sudah akurat? Barang rusak tidak akan ditambahkan ke stok toko.')" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-6 rounded shadow">✓ Validasi & Masukkan Stok</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function bukaModalValidasi(id_po, vendor, json_items) {
            document.getElementById('val_id_po').value = id_po;
            document.getElementById('val_vendor').innerText = vendor;

            let items = JSON.parse(json_items);
            let htmlForm = '';

            items.forEach(item => {
                htmlForm += `
                <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
                    <p class="font-bold text-gray-800 text-sm mb-3">[${item.kode_sku}] ${item.nama_barang} <span class="text-teal-600">(Dipesan: ${item.qty_pesan})</span></p>
                    <input type="hidden" name="id_barang[]" value="${item.id_barang}">
                    <div class="flex space-x-4">
                        <div class="w-1/4">
                            <label class="block text-xs font-bold text-green-600 mb-1">Diterima Bagus</label>
                            <input type="number" step="any" name="qty_bagus[]" max="${item.qty_pesan}" value="${item.qty_pesan}" required class="w-full border rounded px-2 py-1 focus:border-teal-500 focus:outline-none">
                        </div>
                        <div class="w-1/4">
                            <label class="block text-xs font-bold text-red-600 mb-1">Diterima Rusak</label>
                            <input type="number" step="any" name="qty_rusak[]" min="0" max="${item.qty_pesan}" value="0" required class="w-full border rounded px-2 py-1 focus:border-red-500 focus:outline-none">
                        </div>
                        <div class="w-2/4">
                            <label class="block text-xs font-bold text-gray-500 mb-1">Keterangan Rusak (Bila ada)</label>
                            <input type="text" name="catatan_kondisi[]" placeholder="Contoh: Pecah, basah, penyok..." class="w-full border rounded px-2 py-1 focus:border-teal-500 focus:outline-none">
                        </div>
                    </div>
                </div>
                `;
            });

            document.getElementById('val_list_barang').innerHTML = htmlForm;
            document.getElementById('modalValidasi').classList.remove('hidden');
        }
    </script>
</body>

</html>