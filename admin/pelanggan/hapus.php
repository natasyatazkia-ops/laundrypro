<?php
require '../../includes/auth.php';
cek_login('admin');
require_once __DIR__ . '/../../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$id = (int)($_POST['id'] ?? 0);

$stmt = mysqli_prepare($koneksi, "SELECT id_user, nama FROM pelanggan WHERE id_pelanggan = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$p = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$p) {
    set_pesan('error', "Data pelanggan tidak ditemukan.");
    header("Location: index.php");
    exit;
}

// Pelanggan yang sudah punya tagihan tidak boleh dihapus, supaya riwayat tagihan tidak hilang
$stmt = mysqli_prepare($koneksi, "SELECT COUNT(*) AS jml FROM tagihan WHERE id_pelanggan = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$jml = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['jml'];

if ($jml > 0) {
    set_pesan('error', "Pelanggan {$p['nama']} tidak bisa dihapus karena sudah punya tagihan. Ubah statusnya menjadi Nonaktif saja.");
    header("Location: index.php");
    exit;
}

// Hapus akun user-nya, data pelanggan ikut terhapus (ON DELETE CASCADE)
$stmt = mysqli_prepare($koneksi, "DELETE FROM users WHERE id_user = ?");
mysqli_stmt_bind_param($stmt, "i", $p['id_user']);
mysqli_stmt_execute($stmt);

set_pesan('sukses', "Pelanggan {$p['nama']} berhasil dihapus.");
header("Location: index.php");
exit;
