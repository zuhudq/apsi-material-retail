<?php
session_start();
// Memanggil koneksi database (menyesuaikan letak folder)
require_once '../config/koneksi.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Menangkap inputan pengguna (bisa email atau username)
    $identitas = mysqli_real_escape_string($conn, $_POST['identitas']);
    $password = $_POST['password'];

    // Query untuk mencari user berdasarkan email ATAU username
    $query = "SELECT * FROM users WHERE (email = '$identitas' OR username = '$identitas') AND status_aktif = 'Aktif'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);

        // Verifikasi kecocokan hash password
        if (password_verify($password, $user['password_hash'])) {
            // Set session jika password benar
            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];

            // Routing berdasarkan Role
            if ($user['role'] == 'Pemilik') {
                header("Location: ../views/pemilik/dashboard.php");
            } elseif ($user['role'] == 'Kasir') {
                header("Location: ../views/kasir/pos.php");
            } elseif ($user['role'] == 'Staf Gudang') {
                header("Location: ../views/gudang/inbound.php");
            }
            exit;
        } else {
            $error = 'Password yang Anda masukkan salah!';
        }
    } else {
        $error = 'Email/Username tidak ditemukan atau akun nonaktif!';
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Ritel Material</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex items-center justify-center h-screen">

    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold text-center text-blue-700 mb-6">TB. Pesona Rumah Kita</h2>

        <?php if ($error != ''): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="identitas">
                    Email atau Username
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500"
                    id="identitas" name="identitas" type="text" required placeholder="Masukkan Email / Username">
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                    Password
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline focus:border-blue-500"
                    id="password" name="password" type="password" required placeholder="******************">
            </div>

            <div class="flex items-center justify-between mb-4">
                <button class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full" type="submit">
                    Masuk
                </button>
            </div>

            <div class="text-center text-sm text-gray-600">
                Belum punya akun? <a href="register.php" class="text-blue-600 hover:text-blue-800 font-bold">Daftar di sini</a>
            </div>
        </form>
    </div>

</body>

</html>