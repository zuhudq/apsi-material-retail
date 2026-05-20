<?php
session_start();
// Memanggil koneksi database
require_once '../config/koneksi.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Menangkap dan membersihkan inputan
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $konfirmasi_password = $_POST['konfirmasi_password'];
    $role = mysqli_real_escape_string($conn, $_POST['role']);

    // Validasi kecocokan password
    if ($password !== $konfirmasi_password) {
        $error = 'Konfirmasi password tidak cocok!';
    } else {
        // Pengecekan apakah username atau email sudah digunakan
        $cek_query = "SELECT * FROM users WHERE email = '$email' OR username = '$username'";
        $cek_result = mysqli_query($conn, $cek_query);

        if (mysqli_num_rows($cek_result) > 0) {
            $error = 'Email atau Username sudah terdaftar! Silakan gunakan yang lain.';
        } else {
            // Enkripsi password menggunakan BCRYPT
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Proses insert data ke tabel users
            $insert_query = "INSERT INTO users (nama_lengkap, username, email, password_hash, role, status_aktif) 
                             VALUES ('$nama_lengkap', '$username', '$email', '$password_hash', '$role', 'Aktif')";

            if (mysqli_query($conn, $insert_query)) {
                $success = 'Registrasi berhasil! Silakan menuju halaman login.';
            } else {
                $error = 'Terjadi kesalahan sistem: ' . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Sistem Ritel Material</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen py-8">

    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-lg">
        <h2 class="text-2xl font-bold text-center text-blue-700 mb-6">Daftar Akun Baru</h2>

        <?php if ($error != ''): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success != ''): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $success; ?></span>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="nama_lengkap">
                    Nama Lengkap
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500"
                    id="nama_lengkap" name="nama_lengkap" type="text" required placeholder="Contoh: Budi Santoso">
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="username">
                        Username
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500"
                        id="username" name="username" type="text" required placeholder="budisantoso">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                        Email
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500"
                        id="email" name="email" type="email" required placeholder="budi@toko.com">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="role">
                    Peran (Role)
                </label>
                <div class="relative">
                    <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500" id="role" name="role" required>
                        <option value="" disabled selected>Pilih Peran Karyawan</option>
                        <option value="Kasir">Kasir</option>
                        <option value="Staf Gudang">Staf Gudang</option>
                        <option value="Pemilik">Pemilik (Admin)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                        Password
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500"
                        id="password" name="password" type="password" required placeholder="********">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="konfirmasi_password">
                        Konfirmasi Password
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500"
                        id="konfirmasi_password" name="konfirmasi_password" type="password" required placeholder="********">
                </div>
            </div>

            <div class="flex items-center justify-between mb-4">
                <button class="bg-green-600 hover:bg-green-800 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full" type="submit">
                    Daftar Akun
                </button>
            </div>

            <div class="text-center text-sm text-gray-600">
                Sudah punya akun? <a href="login.php" class="text-blue-600 hover:text-blue-800 font-bold">Login di sini</a>
            </div>
        </form>
    </div>

</body>

</html>