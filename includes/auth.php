<?php
// Dipanggil di awal setiap halaman yang butuh login.
// Contoh: cek_login('admin') atau cek_login('pelanggan')
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/fungsi.php";

function cek_login($role)
{
    if (!isset($_SESSION['id_user'])) {
        header("Location: /laundrypro/login.php");
        exit;
    }
    // Admin tidak boleh buka halaman pelanggan, begitu juga sebaliknya
    if ($_SESSION['role'] !== $role) {
        header("Location: /laundrypro/index.php");
        exit;
    }
    // Pelanggan yang dinonaktifkan/dihapus admin saat masih login langsung dikeluarkan
    if ($role === 'pelanggan') {
        global $koneksi;
        require_once __DIR__ . '/../config/koneksi.php';
        $stmt = mysqli_prepare($koneksi, "SELECT status FROM pelanggan WHERE id_pelanggan = ?");
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['id_pelanggan']);
        mysqli_stmt_execute($stmt);
        $p = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$p || $p['status'] !== 'aktif') {
            session_unset();
            $_SESSION['error_login'] = "Akun Anda tidak aktif. Hubungi admin.";
            header("Location: /laundrypro/login.php");
            exit;
        }
    }
}
