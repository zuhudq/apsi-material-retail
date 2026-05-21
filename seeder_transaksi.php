<?php
// FILE INI HANYA UNTUK KEPERLUAN DUMMY DATA LALU HAPUS JIKA SUDAH SELESAI
require_once 'config/koneksi.php';
/** @var mysqli $conn */

echo "<h2>Mulai menanam data transaksi dummy...</h2>";

// 1. Ambil 1 User Kasir/Admin
$q_user = mysqli_query($conn, "SELECT id_user FROM users LIMIT 1");
if (mysqli_num_rows($q_user) == 0) die("Error: Belum ada user di database!");
$user = mysqli_fetch_assoc($q_user);
$id_user = $user['id_user'];

// 2. Ambil semua barang
$q_barang = mysqli_query($conn, "SELECT id_barang, nama_barang, harga_jual FROM barang");
$barang_list = [];
while ($b = mysqli_fetch_assoc($q_barang)) {
    $barang_list[] = $b;
}
if (count($barang_list) == 0) die("Error: Tabel barang masih kosong!");

$total_trx = 0;

// 3. Looping mundur 7 Hari Terakhir
for ($hari = 6; $hari >= 0; $hari--) {
    $tanggal_dasar = date('Y-m-d', strtotime("-$hari days"));

    // Bikin 5 sampai 12 transaksi per hari secara acak
    $jumlah_trx_hari_ini = rand(5, 12);

    for ($i = 0; $i < $jumlah_trx_hari_ini; $i++) {
        // Random jam toko buka (08:00 - 16:59)
        $jam = str_pad(rand(8, 16), 2, "0", STR_PAD_LEFT);
        $menit = str_pad(rand(0, 59), 2, "0", STR_PAD_LEFT);
        $waktu_jual = "$tanggal_dasar $jam:$menit:00";

        // Pilih 1 sampai 4 macam barang secara acak untuk 1 nota
        $jumlah_item = rand(1, 4);
        shuffle($barang_list); // Acak urutan array barang

        $total_bayar = 0;
        $detail_queries = [];

        for ($j = 0; $j < $jumlah_item; $j++) {
            $brg = $barang_list[$j];
            $qty = rand(1, 5); // Beli 1 sampai 5 pcs
            $subtotal = $qty * $brg['harga_jual'];
            $total_bayar += $subtotal;

            // Simpan draft query detail
            $detail_queries[] = [
                'id_barang' => $brg['id_barang'],
                'qty' => $qty,
                'harga' => $brg['harga_jual'],
                'subtotal' => $subtotal
            ];
        }

        // Nominal uang yang dibayar pelanggan (dibulatkan ke atas)
        $pembulatan = 50000;
        $nominal_bayar = ceil($total_bayar / $pembulatan) * $pembulatan;
        $kembalian = $nominal_bayar - $total_bayar;

        // INSERT HEADER PENJUALAN
        $q_header = "INSERT INTO penjualan (id_user, tanggal_jual, total_bayar, metode_bayar, kembalian, status) 
                     VALUES ($id_user, '$waktu_jual', $total_bayar, 'Tunai', $kembalian, 'Selesai')";
        mysqli_query($conn, $q_header);
        $id_jual = mysqli_insert_id($conn);

        // INSERT DETAIL PENJUALAN
        foreach ($detail_queries as $dq) {
            $id_b = $dq['id_barang'];
            $qty = $dq['qty'];
            $harga = $dq['harga'];
            $sub = $dq['subtotal'];

            $q_detail = "INSERT INTO detail_jual (id_jual, id_barang, kuantitas, satuan_jual, harga_satuan, subtotal) 
                         VALUES ($id_jual, $id_b, $qty, 'Pcs/Sak/Btg', $harga, $sub)";
            mysqli_query($conn, $q_detail);
        }

        $total_trx++;
    }
}

echo "<h3 style='color:green;'>Selesai! $total_trx Transaksi berhasil disimulasikan selama 7 hari terakhir.</h3>";
echo "<a href='views/pemilik/dashboard.php'>Lihat Dashboard Pemilik Sekarang!</a>";
