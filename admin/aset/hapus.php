<?php
require '../../includes/auth.php';
cek_login('admin');
require_once __DIR__ . '/../../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$stmt = mysqli_prepare($koneksi, "SELECT nama_aset FROM aset WHERE id_aset = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$a = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$a) {
    set_pesan('error', "Data aset tidak ditemukan.");
} else {
    $stmt = mysqli_prepare($koneksi, "DELETE FROM aset WHERE id_aset = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    set_pesan('sukses', "Aset {$a['nama_aset']} berhasil dihapus.");
}
header("Location: index.php");
exit;
