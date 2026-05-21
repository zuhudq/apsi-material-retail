<?php
session_start();

// Proteksi Halaman
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'Pemilik') {
    header("Location: ../../auth/login.php");
    exit;
}

require_once '../../config/koneksi.php';
/** @var mysqli $conn */

$success = '';
$error = '';

// --- AUTO MIGRATE: Tambah kolom status jika belum ada di database ---
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'status'");
if (mysqli_num_rows($check_col) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD status ENUM('Aktif', 'Nonaktif') DEFAULT 'Aktif' AFTER role");
}

// --- PROSES CRUD ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Tambah Akun Baru
    if (isset($_POST['tambah_user'])) {
        $nama = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $role = mysqli_real_escape_string($conn, $_POST['role']);
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Enkripsi tingkat tinggi

        // Cek username apakah sudah terpakai
        $cek_uname = mysqli_query($conn, "SELECT id_user FROM users WHERE username = '$username'");
        if (mysqli_num_rows($cek_uname) > 0) {
            $error = "Gagal! Username '$username' sudah terpakai.";
        } else {
            $query_tambah = "INSERT INTO users (nama_lengkap, username, password_hash, role, status) VALUES ('$nama', '$username', '$password', '$role', 'Aktif')";
            if (mysqli_query($conn, $query_tambah)) {
                $success = "Akun $role baru berhasil ditambahkan!";
            } else {
                $error = "Gagal menambahkan akun: " . mysqli_error($conn);
            }
        }
    }

    // 2. Ubah Status (Aktif / Nonaktif)
    if (isset($_POST['toggle_status'])) {
        $id_target = intval($_POST['id_user']);
        $status_baru = mysqli_real_escape_string($conn, $_POST['status_baru']);

        // Pemilik tidak boleh menonaktifkan dirinya sendiri
        if ($id_target == $_SESSION['id_user']) {
            $error = "Akses ditolak! Anda tidak dapat menonaktifkan akun Anda sendiri.";
        } else {
            mysqli_query($conn, "UPDATE users SET status = '$status_baru' WHERE id_user = $id_target");
            $success = "Status akun berhasil diubah menjadi $status_baru!";
        }
    }

    // 3. Reset Password
    if (isset($_POST['reset_password'])) {
        $id_target = intval($_POST['id_user']);
        $pass_baru = password_hash($_POST['password_baru'], PASSWORD_BCRYPT);

        mysqli_query($conn, "UPDATE users SET password_hash = '$pass_baru' WHERE id_user = $id_target");
        $success = "Password akun berhasil di-reset!";
    }
}

// --- AMBIL DATA KARYAWAN ---
$query_users = mysqli_query($conn, "SELECT * FROM users ORDER BY role DESC, nama_lengkap ASC");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Akun - TB. Pesona Rumah Kita</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
            <a href="laporan_stok.php" class="flex items-center p-3 hover:bg-indigo-800 rounded-lg transition-colors">📋 Laporan Stok</a>
            <a href="pengadaan.php" class="flex items-center p-3 hover:bg-indigo-800 rounded-lg transition-colors">📝 Pengadaan (PO)</a>
            <a href="manajemen_user.php" class="flex items-center p-3 bg-indigo-800 rounded-lg font-semibold transition-colors">👥 Manajemen Akun</a>
        </nav>
        <div class="p-4 border-t border-indigo-800">
            <a href="../../auth/login.php" class="flex items-center p-3 text-red-300 hover:text-white hover:bg-red-600 rounded-lg transition-colors">🚪 Logout</a>
        </div>
    </aside>

    <main class="flex-1 p-8 overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Manajemen Pengguna & Hak Akses</h2>
                <p class="text-gray-500 text-sm mt-1">Kelola data karyawan yang dapat mengakses sistem.</p>
            </div>
            <button onclick="document.getElementById('modalTambah').classList.remove('hidden')" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded shadow transition-colors">
                + Tambah Akun Baru
            </button>
        </div>

        <?php if ($error != ''): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 shadow-sm" role="alert">
                <p><?= $error; ?></p>
            </div>
        <?php endif; ?>
        <?php if ($success != ''): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 shadow-sm" role="alert">
                <p><?= $success; ?></p>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-indigo-50 text-indigo-900 text-sm uppercase">
                        <th class="py-4 px-6 border-b">Nama Lengkap</th>
                        <th class="py-4 px-6 border-b">Username</th>
                        <th class="py-4 px-6 border-b text-center">Hak Akses</th>
                        <th class="py-4 px-6 border-b text-center">Status</th>
                        <th class="py-4 px-6 border-b text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 text-sm">
                    <?php while ($row = mysqli_fetch_assoc($query_users)): ?>
                        <?php
                        // Pastikan jika kolom status null, default ke Aktif untuk visualisasi
                        $status_tampil = isset($row['status']) ? $row['status'] : 'Aktif';
                        ?>
                        <tr class="hover:bg-gray-50 border-b <?= $status_tampil == 'Nonaktif' ? 'opacity-60 bg-gray-50' : ''; ?>">
                            <td class="py-4 px-6 font-semibold text-gray-800">
                                <?= htmlspecialchars($row['nama_lengkap']); ?>
                                <?= $row['id_user'] == $_SESSION['id_user'] ? '<span class="ml-2 text-xs bg-indigo-100 text-indigo-800 px-2 py-0.5 rounded-full">Anda</span>' : ''; ?>
                            </td>
                            <td class="py-4 px-6"><?= htmlspecialchars($row['username']); ?></td>
                            <td class="py-4 px-6 text-center">
                                <span class="px-3 py-1 rounded-full text-xs font-bold <?= $row['role'] == 'Pemilik' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'; ?>">
                                    <?= $row['role']; ?>
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="px-3 py-1 rounded-full text-xs font-bold <?= $status_tampil == 'Aktif' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                    <?= $status_tampil; ?>
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center flex justify-center space-x-2">
                                <button onclick="bukaModalReset(<?= $row['id_user']; ?>, '<?= addslashes($row['username']); ?>')" class="bg-yellow-500 hover:bg-yellow-600 text-white py-1 px-3 rounded text-xs font-bold transition-colors shadow-sm" title="Reset Password">
                                    🔑 Reset
                                </button>

                                <?php if ($row['id_user'] != $_SESSION['id_user']): ?>
                                    <form action="" method="POST" class="inline">
                                        <input type="hidden" name="id_user" value="<?= $row['id_user']; ?>">
                                        <?php if ($status_tampil == 'Aktif'): ?>
                                            <input type="hidden" name="status_baru" value="Nonaktif">
                                            <button type="submit" name="toggle_status" onclick="return confirm('Yakin ingin memblokir/menonaktifkan akun ini?');" class="bg-red-500 hover:bg-red-600 text-white py-1 px-3 rounded text-xs font-bold transition-colors shadow-sm">
                                                🚫 Blokir
                                            </button>
                                        <?php else: ?>
                                            <input type="hidden" name="status_baru" value="Aktif">
                                            <button type="submit" name="toggle_status" class="bg-green-500 hover:bg-green-600 text-white py-1 px-3 rounded text-xs font-bold transition-colors shadow-sm">
                                                ✅ Aktifkan
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div id="modalTambah" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg w-1/3 p-6 shadow-xl">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">Tambah Akun Baru</h3>
                <button onclick="document.getElementById('modalTambah').classList.add('hidden')" class="text-gray-500 hover:text-red-500 text-2xl font-bold">&times;</button>
            </div>
            <form action="" method="POST">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Nama Lengkap Karyawan</label>
                    <input type="text" name="nama_lengkap" required class="w-full border rounded px-3 py-2 focus:outline-none focus:border-indigo-500">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Username (Untuk Login)</label>
                    <input type="text" name="username" required class="w-full border rounded px-3 py-2 focus:outline-none focus:border-indigo-500">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                    <input type="password" name="password" required class="w-full border rounded px-3 py-2 focus:outline-none focus:border-indigo-500">
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Hak Akses (Role)</label>
                    <select name="role" required class="w-full border rounded px-3 py-2 focus:outline-none focus:border-indigo-500">
                        <option value="Kasir">Kasir</option>
                        <option value="Staf Gudang">Staf Gudang</option>
                        <option value="Pemilik">Pemilik / Admin</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="document.getElementById('modalTambah').classList.add('hidden')" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">Batal</button>
                    <button type="submit" name="tambah_user" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">Simpan Akun</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalReset" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg w-1/3 p-6 shadow-xl">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">Reset Password</h3>
                <button onclick="document.getElementById('modalReset').classList.add('hidden')" class="text-gray-500 hover:text-red-500 text-2xl font-bold">&times;</button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="id_user" id="reset_id_user">
                <p class="mb-4 text-gray-600 text-sm">Masukkan password baru untuk akun: <strong id="reset_username" class="text-indigo-600"></strong></p>

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Password Baru</label>
                    <input type="password" name="password_baru" required class="w-full border rounded px-3 py-2 focus:outline-none focus:border-yellow-500">
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="document.getElementById('modalReset').classList.add('hidden')" class="bg-gray-400 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">Batal</button>
                    <button type="submit" name="reset_password" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded">Reset Sekarang</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function bukaModalReset(id, username) {
            document.getElementById('reset_id_user').value = id;
            document.getElementById('reset_username').innerText = username;
            document.getElementById('modalReset').classList.remove('hidden');
        }
    </script>
</body>

</html>