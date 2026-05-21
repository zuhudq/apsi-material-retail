<?php
session_start();

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'Pemilik') {
    header("Location: ../../auth/login.php");
    exit;
}

require_once '../../config/koneksi.php';
/** @var mysqli $conn */

$success = '';
$error = '';

// --- SISTEM OTOMATIS: Ubah PO 'Menunggu' jadi 'Dikirim' jika sudah lewat 24 Jam ---
mysqli_query($conn, "UPDATE purchase_order SET status = 'Dikirim' WHERE status = 'Menunggu' AND DATE_ADD(tanggal_po, INTERVAL 1 DAY) <= NOW()");

// Proses Simpan PO Baru (Sama seperti sebelumnya)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['simpan_po'])) {
    $id_vendor = intval($_POST['id_vendor']);
    $tanggal_po = date('Y-m-d H:i:s'); // Diubah ke format datetime agar presisi
    $id_user = $_SESSION['id_user'];
    $items = $_POST['id_barang'];
    $qtys = $_POST['qty'];

    mysqli_begin_transaction($conn);
    try {
        mysqli_query($conn, "INSERT INTO purchase_order (id_vendor, tanggal_po, status, id_user) VALUES ($id_vendor, '$tanggal_po', 'Menunggu', $id_user)");
        $id_po = mysqli_insert_id($conn);

        for ($i = 0; $i < count($items); $i++) {
            $id_b = intval($items[$i]);
            $qty_p = floatval($qtys[$i]);
            if ($id_b > 0 && $qty_p > 0) {
                mysqli_query($conn, "INSERT INTO detail_po (id_po, id_barang, qty_pesan) VALUES ($id_po, $id_b, $qty_p)");
            }
        }
        mysqli_commit($conn);
        $success = "Purchase Order (PO) berhasil dibuat. Sistem vendor akan memprosesnya dalam 24 jam.";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "Gagal membuat PO: " . $e->getMessage();
    }
}

// Proses Simulasi Bypass 24 Jam (Khusus untuk Demo/Presentasi)
if (isset($_POST['bypass_kirim'])) {
    $id_po_bypass = intval($_POST['id_po']);
    mysqli_query($conn, "UPDATE purchase_order SET status = 'Dikirim' WHERE id_po = $id_po_bypass");
    $success = "[Mode Demo] Vendor telah memproses dan mengirim pesanan!";
}

// Ambil Data
$q_vendor = mysqli_query($conn, "SELECT * FROM vendor ORDER BY nama_vendor ASC");
$q_barang = mysqli_query($conn, "SELECT id_barang, kode_sku, nama_barang, stok_aktual, stok_minimum FROM barang ORDER BY nama_barang ASC");
$barang_list = [];
while ($b = mysqli_fetch_assoc($q_barang)) {
    $barang_list[] = $b;
}

$q_po = mysqli_query($conn, "SELECT po.*, v.nama_vendor, u.nama_lengkap 
                             FROM purchase_order po 
                             JOIN vendor v ON po.id_vendor = v.id_vendor 
                             JOIN users u ON po.id_user = u.id_user 
                             ORDER BY po.id_po DESC");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengadaan Barang (PO) - TB. Pesona</title>
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

<body class="bg-gray-50 flex h-screen overflow-hidden">

    <aside class="w-64 bg-indigo-900 text-white flex flex-col h-full shadow-lg">
        <div class="p-6 border-b border-indigo-800">
            <h1 class="text-xl font-bold">TB. Pesona Rumah Kita</h1>
            <p class="text-sm text-indigo-300 mt-1">Pemilik: <?= htmlspecialchars($_SESSION['nama_lengkap']); ?></p>
        </div>
        <nav class="flex-1 p-4 space-y-2">
            <a href="dashboard.php" class="flex items-center p-3 hover:bg-indigo-800 rounded-lg transition-colors">📊 Dashboard</a>
            <a href="master_barang.php" class="flex items-center p-3 hover:bg-indigo-800 rounded-lg transition-colors">📦 Master Barang</a>
            <a href="pengadaan.php" class="flex items-center p-3 bg-indigo-800 rounded-lg font-semibold transition-colors">📝 Pengadaan (PO)</a>
            <a href="laporan_stok.php" class="flex items-center p-3 hover:bg-indigo-800 rounded-lg transition-colors">📋 Laporan Stok</a>
            <a href="manajemen_user.php" class="flex items-center p-3 hover:bg-indigo-800 rounded-lg transition-colors">👥 Manajemen Akun</a>
        </nav>
        <div class="p-4 border-t border-indigo-800">
            <a href="../../auth/login.php" class="flex items-center p-3 text-red-300 hover:text-white hover:bg-red-600 rounded-lg transition-colors">🚪 Logout</a>
        </div>
    </aside>

    <main class="flex-1 p-8 overflow-y-auto custom-scroll">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Purchase Order (Pengadaan)</h2>
                <p class="text-gray-500 text-sm mt-1">Sistem otomatis meneruskan pesanan ke vendor dalam 24 jam.</p>
            </div>
            <button onclick="document.getElementById('modalPO').classList.remove('hidden')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded shadow transition-colors">
                + Buat PO Baru
            </button>
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

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-100 text-gray-700 text-sm uppercase">
                        <th class="py-4 px-6 border-b">No. PO</th>
                        <th class="py-4 px-6 border-b">Tanggal Pengajuan</th>
                        <th class="py-4 px-6 border-b">Vendor Tujuan</th>
                        <th class="py-4 px-6 border-b text-center">Status</th>
                        <th class="py-4 px-6 border-b text-center">Aksi Sistem</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    <?php if (mysqli_num_rows($q_po) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($q_po)): ?>
                            <tr class="hover:bg-gray-50 border-b transition-colors">
                                <td class="py-4 px-6 font-mono font-bold text-indigo-700">PO-<?= sprintf("%05d", $row['id_po']); ?></td>
                                <td class="py-4 px-6"><?= date('d M Y, H:i', strtotime($row['tanggal_po'])); ?></td>
                                <td class="py-4 px-6 font-semibold"><?= htmlspecialchars($row['nama_vendor']); ?></td>
                                <td class="py-4 px-6 text-center">
                                    <?php
                                    $bg = $row['status'] == 'Selesai' ? 'bg-green-100 text-green-700' : ($row['status'] == 'Dikirim' ? 'bg-blue-100 text-blue-700 animate-pulse' : 'bg-yellow-100 text-yellow-700');
                                    ?>
                                    <span class="px-3 py-1 rounded-full text-xs font-bold <?= $bg; ?>">
                                        <?= $row['status'] == 'Dikirim' ? '🚚 Dikirim' : $row['status']; ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <?php if ($row['status'] == 'Menunggu'): ?>
                                        <span class="text-xs text-gray-400">Otomatis kirim dlm 24j</span>
                                        <form action="" method="POST" class="inline ml-2">
                                            <input type="hidden" name="id_po" value="<?= $row['id_po']; ?>">
                                            <button type="submit" name="bypass_kirim" class="text-xs bg-indigo-50 text-indigo-500 hover:bg-indigo-500 hover:text-white px-2 py-1 rounded transition-colors" title="Bypass waktu untuk presentasi">🚀 Kirim Paksa</button>
                                        </form>
                                    <?php elseif ($row['status'] == 'Dikirim'): ?>
                                        <span class="text-xs text-blue-500 font-bold">Menunggu Validasi Gudang</span>
                                    <?php else: ?>
                                        <span class="text-xs text-green-500 font-bold">✓ Barang Diterima</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="py-8 text-center text-gray-400 italic">Belum ada dokumen Purchase Order.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div id="modalPO" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg w-3/4 max-h-[90vh] flex flex-col shadow-xl">
            <div class="flex justify-between items-center p-6 border-b">
                <h3 class="text-xl font-bold text-gray-800">Form Purchase Order Baru</h3>
                <button onclick="document.getElementById('modalPO').classList.add('hidden')" class="text-gray-500 hover:text-red-500 text-2xl font-bold">&times;</button>
            </div>

            <form action="" method="POST" class="flex-1 overflow-y-auto p-6 custom-scroll">
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Pilih Vendor / Supplier</label>
                    <select name="id_vendor" required class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:border-indigo-500">
                        <option value="">-- Pilih Vendor Tujuan --</option>
                        <?php while ($v = mysqli_fetch_assoc($q_vendor)): ?>
                            <option value="<?= $v['id_vendor']; ?>"><?= htmlspecialchars($v['nama_vendor']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-4 flex justify-between items-end border-b pb-2">
                    <label class="block text-gray-700 text-sm font-bold">Daftar Barang yang Dipesan</label>
                    <button type="button" onclick="tambahBaris()" class="bg-blue-100 hover:bg-blue-200 text-blue-700 font-bold py-1 px-3 rounded text-sm transition-colors">+ Tambah Baris</button>
                </div>

                <div id="container-barang" class="space-y-3 mb-6">
                    <div class="flex items-center space-x-3">
                        <select name="id_barang[]" required class="flex-1 border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none">
                            <option value="">-- Pilih Barang --</option>
                            <?php foreach ($barang_list as $brg): ?>
                                <option value="<?= $brg['id_barang']; ?>">
                                    <?= $brg['stok_aktual'] <= $brg['stok_minimum'] ? '⚠️ ' : ''; ?>[<?= $brg['kode_sku']; ?>] <?= htmlspecialchars($brg['nama_barang']); ?> (Stok: <?= $brg['stok_aktual']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" step="any" name="qty[]" required placeholder="Qty" min="0.1" class="w-24 border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none">
                    </div>
                </div>

                <div class="flex justify-end space-x-2 pt-4 border-t">
                    <button type="button" onclick="document.getElementById('modalPO').classList.add('hidden')" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-6 rounded">Batal</button>
                    <button type="submit" name="simpan_po" onclick="return confirm('Proses dan simpan Purchase Order ini?')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded">Simpan & Terbitkan PO</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const opsiBarang = `
            <option value="">-- Pilih Barang --</option>
            <?php foreach ($barang_list as $brg): ?>
                <option value="<?= $brg['id_barang']; ?>">
                    <?= $brg['stok_aktual'] <= $brg['stok_minimum'] ? '⚠️ ' : ''; ?>[<?= $brg['kode_sku']; ?>] <?= addslashes(htmlspecialchars($brg['nama_barang'])); ?> (Stok: <?= $brg['stok_aktual']; ?>)
                </option>
            <?php endforeach; ?>
        `;

        function tambahBaris() {
            const container = document.getElementById('container-barang');
            const row = document.createElement('div');
            row.className = 'flex items-center space-x-3';
            row.innerHTML = `
                <select name="id_barang[]" required class="flex-1 border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none">
                    ${opsiBarang}
                </select>
                <input type="number" step="any" name="qty[]" required placeholder="Qty" min="0.1" class="w-24 border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none">
                <button type="button" onclick="this.parentElement.remove()" class="bg-red-100 text-red-600 hover:bg-red-200 px-3 py-2 rounded font-bold">X</button>
            `;
            container.appendChild(row);
        }
    </script>
</body>

</html>