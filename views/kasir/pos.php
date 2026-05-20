<?php
session_start();

// Proteksi Halaman: Kasir (dan Pemilik untuk testing)
if (!isset($_SESSION['id_user']) || ($_SESSION['role'] !== 'Kasir' && $_SESSION['role'] !== 'Pemilik')) {
    header("Location: ../../auth/login.php");
    exit;
}

require_once '../../config/koneksi.php';
/** @var mysqli $conn */

$error = '';
$success = '';

// Ambil data barang yang stoknya lebih dari 0 untuk ditampilkan di katalog
$query_barang = mysqli_query($conn, "SELECT * FROM barang WHERE stok_aktual > 0 ORDER BY nama_barang ASC");
$katalog_barang = [];
while ($row = mysqli_fetch_assoc($query_barang)) {
    $katalog_barang[] = $row;
}

// Proses Transaksi (Checkout)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['checkout'])) {
    $id_user = $_SESSION['id_user'];
    $tanggal_jual = date('Y-m-d H:i:s');
    $total_bayar = floatval($_POST['total_bayar_hidden']);
    $nominal_bayar = floatval($_POST['nominal_bayar']);
    $kembalian = $nominal_bayar - $total_bayar;

    // Validasi sederhana dari sisi server
    if ($nominal_bayar < $total_bayar) {
        $error = "Nominal pembayaran kurang dari total belanja!";
    } else if (empty($_POST['id_barang'])) {
        $error = "Keranjang belanja kosong!";
    } else {
        mysqli_begin_transaction($conn);
        try {
            // 1. Insert ke tabel penjualan (Header)
            $query_jual = "INSERT INTO penjualan (id_user, tanggal_jual, total_bayar, metode_bayar, kembalian, status) 
                           VALUES ($id_user, '$tanggal_jual', $total_bayar, 'Tunai', $kembalian, 'Selesai')";
            mysqli_query($conn, $query_jual);
            $id_jual = mysqli_insert_id($conn);

            // 2. Loop detail keranjang
            $items = $_POST['id_barang'];
            $qtys = $_POST['qty'];
            $hargas = $_POST['harga'];
            $satuans = $_POST['satuan']; // Kita ambil dari input hidden

            for ($i = 0; $i < count($items); $i++) {
                $id_b = intval($items[$i]);
                $qty_b = floatval($qtys[$i]);
                $harga_b = floatval($hargas[$i]);
                $satuan_b = mysqli_real_escape_string($conn, $satuans[$i]);
                $subtotal_b = $qty_b * $harga_b;

                // Cek stok terakhir untuk mencegah bentrok data (race condition)
                $cek_stok = mysqli_query($conn, "SELECT stok_aktual, nama_barang FROM barang WHERE id_barang = $id_b");
                $stok_db = mysqli_fetch_assoc($cek_stok);

                if ($stok_db['stok_aktual'] < $qty_b) {
                    throw new Exception("Stok {$stok_db['nama_barang']} tidak mencukupi! Sisa: {$stok_db['stok_aktual']}");
                }

                // Insert Detail Jual
                $q_detail = "INSERT INTO detail_jual (id_jual, id_barang, kuantitas, satuan_jual, harga_satuan, subtotal) 
                             VALUES ($id_jual, $id_b, $qty_b, '$satuan_b', $harga_b, $subtotal_b)";
                mysqli_query($conn, $q_detail);

                // Potong Stok
                $q_potong = "UPDATE barang SET stok_aktual = stok_aktual - $qty_b WHERE id_barang = $id_b";
                mysqli_query($conn, $q_potong);
            }

            mysqli_commit($conn);
            $success = "Transaksi berhasil! Kembalian: Rp " . number_format($kembalian, 0, ',', '.');

            // Refresh data katalog agar stok terbaru langsung muncul
            $query_barang = mysqli_query($conn, "SELECT * FROM barang WHERE stok_aktual > 0 ORDER BY nama_barang ASC");
            $katalog_barang = [];
            while ($row = mysqli_fetch_assoc($query_barang)) {
                $katalog_barang[] = $row;
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Transaksi gagal: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem POS - Kasir</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Agar scrollbar keranjang tidak terlalu tebal */
        .custom-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scroll::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 4px;
        }
    </style>
</head>

<body class="bg-gray-100 flex h-screen overflow-hidden">

    <aside class="w-20 lg:w-64 bg-blue-900 text-white flex flex-col h-full shadow-lg transition-all duration-300">
        <div class="p-4 lg:p-6 border-b border-blue-800 text-center lg:text-left">
            <h1 class="text-xl font-bold hidden lg:block">TB. Pesona</h1>
            <span class="text-2xl lg:hidden">🏪</span>
        </div>
        <nav class="flex-1 p-2 lg:p-4 space-y-2">
            <a href="pos.php" class="flex items-center p-3 bg-blue-800 rounded-lg font-semibold transition-colors justify-center lg:justify-start">
                <span class="text-xl lg:mr-3">🛒</span>
                <span class="hidden lg:block">Kasir POS</span>
            </a>
            <a href="#" class="flex items-center p-3 hover:bg-blue-800 rounded-lg transition-colors justify-center lg:justify-start">
                <span class="text-xl lg:mr-3">📄</span>
                <span class="hidden lg:block">Riwayat Transaksi</span>
            </a>
        </nav>
        <div class="p-4 border-t border-blue-800">
            <a href="../../auth/login.php" class="flex items-center p-3 text-red-300 hover:text-white hover:bg-red-600 rounded-lg transition-colors justify-center lg:justify-start">
                <span class="text-xl lg:mr-3">🚪</span>
                <span class="hidden lg:block">Logout</span>
            </a>
        </div>
    </aside>

    <main class="flex-1 flex flex-col lg:flex-row h-full">

        <div class="w-full lg:w-2/3 h-full flex flex-col p-4 bg-gray-50">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-800">Katalog Produk</h2>
                <div class="text-sm text-gray-500">Kasir: <?= htmlspecialchars($_SESSION['nama_lengkap']); ?> | <?= date('d M Y'); ?></div>
            </div>

            <?php if ($error != ''): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4"><?= $error; ?></div>
            <?php endif; ?>
            <?php if ($success != ''): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4"><?= $success; ?></div>
            <?php endif; ?>

            <div class="mb-4 relative">
                <input type="text" id="searchInput" placeholder="🔍 Cari nama barang atau scan SKU..."
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 text-lg">
            </div>

            <div class="flex-1 overflow-y-auto custom-scroll pr-2">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4" id="katalogGrid">
                    <?php foreach ($katalog_barang as $brg): ?>
                        <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm hover:shadow-md cursor-pointer transition-shadow item-card"
                            onclick="addToCart(<?= $brg['id_barang']; ?>, '<?= addslashes($brg['nama_barang']); ?>', <?= $brg['harga_jual']; ?>, <?= $brg['stok_aktual']; ?>, 'Pcs')">
                            <div class="font-bold text-gray-800 text-sm md:text-base mb-1 nama-barang"><?= $brg['nama_barang']; ?></div>
                            <div class="text-xs text-gray-500 mb-2 sku-barang">SKU: <?= $brg['kode_sku']; ?></div>
                            <div class="flex justify-between items-end mt-4">
                                <div class="text-blue-600 font-bold">Rp <?= number_format($brg['harga_jual'], 0, ',', '.'); ?></div>
                                <div class="text-xs bg-gray-100 px-2 py-1 rounded text-gray-600 font-semibold">Stok: <?= $brg['stok_aktual']; ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="w-full lg:w-1/3 h-full bg-white border-l border-gray-200 flex flex-col shadow-xl z-10">
            <div class="p-4 border-b border-gray-200 bg-blue-50">
                <h3 class="font-bold text-lg text-blue-900">Pesanan Saat Ini</h3>
            </div>

            <form action="" method="POST" id="formPOS" class="flex flex-col h-full">
                <div class="flex-1 overflow-y-auto p-4 custom-scroll" id="cartContainer">
                    <div class="text-center text-gray-400 mt-10" id="emptyCartMsg">
                        <span class="text-4xl block mb-2">🛒</span>
                        Belum ada barang di keranjang
                    </div>
                </div>

                <div class="p-4 bg-gray-50 border-t border-gray-200">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-gray-600 font-bold">Total:</span>
                        <span class="text-2xl font-bold text-gray-900" id="displayTotal">Rp 0</span>
                        <input type="hidden" name="total_bayar_hidden" id="total_bayar_hidden" value="0">
                    </div>

                    <div class="mb-4 mt-4">
                        <input type="number" id="nominal_bayar" name="nominal_bayar" placeholder="Nominal Bayar (Rp)" required
                            class="w-full border-2 border-gray-300 rounded-lg px-4 py-3 text-xl font-bold text-right focus:outline-none focus:border-green-500">
                    </div>

                    <div class="flex justify-between items-center mb-4 text-green-700 hidden" id="kembalianContainer">
                        <span class="font-bold">Kembalian:</span>
                        <span class="text-xl font-bold" id="displayKembalian">Rp 0</span>
                    </div>

                    <button type="submit" name="checkout" id="btnCheckout" disabled
                        class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-4 rounded-lg shadow-lg transition-colors opacity-50 cursor-not-allowed">
                        Bayar & Cetak Nota
                    </button>
                </div>
            </form>
        </div>

    </main>

    <script>
        let cart = [];

        // Fungsi Filter Pencarian Barang & Deteksi Barcode Scanner
        const searchInput = document.getElementById('searchInput');

        // 1. Filter Real-time (Saat Mengetik)
        searchInput.addEventListener('keyup', function(e) {
            let filter = this.value.toLowerCase();
            let cards = document.querySelectorAll('.item-card');

            cards.forEach(card => {
                let textNama = card.querySelector('.nama-barang').innerText.toLowerCase();
                let textSKU = card.querySelector('.sku-barang').innerText.toLowerCase();
                if (textNama.includes(filter) || textSKU.includes(filter)) {
                    card.style.display = "";
                } else {
                    card.style.display = "none";
                }
            });
        });

        // 2. Deteksi Barcode Scanner (Saat tombol Enter ditekan)
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault(); // Mencegah halaman reload
                let keyword = this.value.toLowerCase().trim();
                if (keyword === '') return;

                let cards = document.querySelectorAll('.item-card');
                let matchedCard = null;

                // Cari barang yang SKU-nya sama persis dengan hasil scan
                cards.forEach(card => {
                    let textSKU = card.querySelector('.sku-barang').innerText.toLowerCase();
                    // Teks di UI adalah "sku: cat-05", kita pisahkan untuk dapat kode aslinya
                    if (textSKU.includes(keyword)) {
                        matchedCard = card;
                    }
                });

                if (matchedCard) {
                    // Simulasikan klik pada kartu barang tersebut
                    matchedCard.click();

                    // Bersihkan inputan scanner agar siap scan barang berikutnya
                    this.value = '';

                    // Kembalikan semua kartu agar tampil kembali
                    cards.forEach(c => c.style.display = "");
                } else {
                    // Jika barcode tidak dikenali di database
                    alert('Bip! Barang dengan SKU/Barcode tersebut tidak ditemukan!');
                    this.value = '';
                    cards.forEach(c => c.style.display = "");
                }
            }
        });

        // Tambah ke Keranjang (Perbaikan Tipe Data)
        function addToCart(id, nama, harga, stokMax, satuan) {
            let itemId = parseInt(id);
            let existingItem = cart.find(item => item.id === itemId);

            if (existingItem) {
                if (existingItem.qty < stokMax) {
                    existingItem.qty += 1;
                } else {
                    alert('Stok tidak mencukupi!');
                }
            } else {
                cart.push({
                    id: itemId,
                    nama: nama,
                    harga: parseFloat(harga),
                    qty: 1,
                    max: parseFloat(stokMax),
                    satuan: satuan
                });
            }
            renderCart();
        }

        // Hapus dari Keranjang
        function removeFromCart(id) {
            cart = cart.filter(item => item.id !== parseInt(id));
            renderCart();
        }

        // Ubah Qty Manual di Keranjang (Perbaikan Real-time)
        function ubahQty(id, value, max) {
            let item = cart.find(item => item.id === parseInt(id));
            if (item) {
                let val = parseFloat(value);
                if (val > max) {
                    alert('Melebihi stok! Maksimal: ' + max);
                    item.qty = max;
                } else if (val <= 0 || isNaN(val)) {
                    // Jangan langsung ubah ke 1 saat diketik, biarkan kosong sementara
                    item.qty = val;
                } else {
                    item.qty = val;
                }
                renderCart(false); // Render tanpa menghilangkan fokus kursor
            }
        }

        // Render HTML Keranjang
        function renderCart(hilangkanFokus = true) {
            const container = document.getElementById('cartContainer');
            const emptyMsg = document.getElementById('emptyCartMsg');
            const btnCheckout = document.getElementById('btnCheckout');
            let total = 0;

            if (cart.length === 0) {
                emptyMsg.style.display = 'block';
                container.innerHTML = '';
                container.appendChild(emptyMsg);
                btnCheckout.disabled = true;
                btnCheckout.classList.add('opacity-50', 'cursor-not-allowed');
                document.getElementById('total_bayar_hidden').value = 0;
                document.getElementById('displayTotal').innerText = 'Rp 0';
                calculateKembalian();
                return;
            }

            emptyMsg.style.display = 'none';

            // Simpan elemen yang sedang aktif (difokuskan) agar saat dirender kursor tidak hilang
            let activeElementId = document.activeElement ? document.activeElement.id : null;

            let htmlContent = '';

            cart.forEach(item => {
                let qtyAman = isNaN(item.qty) ? 0 : item.qty;
                let subtotal = item.harga * qtyAman;
                total += subtotal;

                let formatHarga = new Intl.NumberFormat('id-ID').format(item.harga);
                let formatSub = new Intl.NumberFormat('id-ID').format(subtotal);
                let inputId = `input_qty_${item.id}`;

                htmlContent += `
            <div class="mb-3 p-3 bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="flex justify-between items-start mb-2">
                    <div class="font-bold text-gray-800 text-sm leading-tight">${item.nama}</div>
                    <button type="button" onclick="removeFromCart(${item.id})" class="text-red-500 hover:text-red-700 font-bold ml-2">✕</button>
                </div>
                <div class="flex justify-between items-center mt-2">
                    <div class="flex items-center">
                        <input type="number" step="0.01" id="${inputId}" value="${isNaN(item.qty) ? '' : item.qty}" oninput="ubahQty(${item.id}, this.value, ${item.max})" 
                               class="w-16 border border-gray-300 rounded px-1 py-1 text-center text-sm mr-2 focus:outline-none focus:border-blue-500">
                        <span class="text-xs text-gray-500 hidden md:inline">x Rp ${formatHarga}</span>
                    </div>
                    <div class="font-bold text-gray-900">Rp ${formatSub}</div>
                </div>
                
                <input type="hidden" name="id_barang[]" value="${item.id}">
                <input type="hidden" name="qty[]" value="${qtyAman}">
                <input type="hidden" name="harga[]" value="${item.harga}">
                <input type="hidden" name="satuan[]" value="${item.satuan}">
            </div>
        `;
            });

            container.innerHTML = htmlContent;

            // Kembalikan fokus kursor ke input yang sedang diketik pengguna
            if (activeElementId && document.getElementById(activeElementId) && hilangkanFokus === false) {
                let el = document.getElementById(activeElementId);
                el.focus();
                // Taruh kursor di akhir angka
                let val = el.value;
                el.value = '';
                el.value = val;
            }

            // Update Total
            document.getElementById('total_bayar_hidden').value = total;
            document.getElementById('displayTotal').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);

            btnCheckout.disabled = false;
            btnCheckout.classList.remove('opacity-50', 'cursor-not-allowed');
            calculateKembalian();
        }

        function calculateKembalian() {
            let total = parseFloat(document.getElementById('total_bayar_hidden').value) || 0;
            let bayar = parseFloat(document.getElementById('nominal_bayar').value) || 0;
            let kContainer = document.getElementById('kembalianContainer');
            let btnCheckout = document.getElementById('btnCheckout');

            if (bayar > 0 && cart.length > 0) {
                if (bayar >= total) {
                    let kembalian = bayar - total;
                    document.getElementById('displayKembalian').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(kembalian);
                    kContainer.classList.remove('hidden');

                    btnCheckout.disabled = false;
                    btnCheckout.classList.remove('opacity-50', 'cursor-not-allowed');
                } else {
                    document.getElementById('displayKembalian').innerText = 'Kurang bayar!';
                    kContainer.classList.remove('hidden');

                    btnCheckout.disabled = true;
                    btnCheckout.classList.add('opacity-50', 'cursor-not-allowed');
                }
            } else {
                kContainer.classList.add('hidden');
            }
        }

        document.getElementById('nominal_bayar').addEventListener('input', calculateKembalian);
    </script>
</body>

</html>