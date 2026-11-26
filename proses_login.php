<?php
session_start();
require_once __DIR__ . '/config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    $_SESSION['error_login'] = "Email dan password wajib diisi.";
    header("Location: login.php");
    exit;
}

// Cari user berdasarkan email (pakai prepared statement biar aman dari SQL injection)
$stmt = mysqli_prepare($koneksi, "SELECT id_user, email, password, role FROM users WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);
$user  = mysqli_fetch_assoc($hasil);

// Cek password yang sudah di-hash
if (!$user || !password_verify($password, $user['password'])) {
    $_SESSION['error_login'] = "Email atau password salah.";
    header("Location: login.php");
    exit;
}

// Kalau pelanggan, cek statusnya masih aktif atau tidak
if ($user['role'] === 'pelanggan') {
    $stmt = mysqli_prepare($koneksi, "SELECT id_pelanggan, nama, status FROM pelanggan WHERE id_user = ?");
    mysqli_stmt_bind_param($stmt, "i", $user['id_user']);
    mysqli_stmt_execute($stmt);
    $pel = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$pel || $pel['status'] !== 'aktif') {
        $_SESSION['error_login'] = "Akun Anda tidak aktif. Hubungi admin.";
        header("Location: login.php");
        exit;
    }
    $_SESSION['id_pelanggan'] = $pel['id_pelanggan'];
    $_SESSION['nama']         = $pel['nama'];
} else {
    $_SESSION['nama'] = "Administrator";
}

session_regenerate_id(true);
$_SESSION['id_user'] = $user['id_user'];
$_SESSION['email']   = $user['email'];
$_SESSION['role']    = $user['role'];

header("Location: index.php");
exit;
