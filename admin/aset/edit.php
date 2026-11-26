<?php
require '../../includes/auth.php';
cek_login('admin');
require_once __DIR__ . '/../../config/koneksi.php';
require 'validasi.php';
$aktif = 'aset';

$id = (int)($_GET['id'] ?? 0);
$stmt = mysqli_prepare($koneksi, "SELECT * FROM aset WHERE id_aset = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$isi = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$isi) {
    set_pesan('error', "Data aset tidak ditemukan.");
    header("Location: index.php");
    exit;
}
$error = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isi = ambil_form_aset();
    $error = validasi_aset($isi);

    if (!$error) {
        $stmt = mysqli_prepare($koneksi, "UPDATE aset SET nama_aset=?, kategori=?, tanggal_perolehan=?, nilai_perolehan=?, kondisi=?, status=? WHERE id_aset=?");
        $nilai = (int)$isi['nilai_perolehan'];
        mysqli_stmt_bind_param($stmt, "sssissi", $isi['nama_aset'], $isi['kategori'], $isi['tanggal_perolehan'], $nilai, $isi['kondisi'], $isi['status'], $id);
        mysqli_stmt_execute($stmt);
        set_pesan('sukses', "Aset {$isi['nama_aset']} berhasil diubah.");
        header("Location: index.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Aset - LaundryPro</title>
    <link rel="stylesheet" href="/laundrypro/assets/style.css">
</head>
<body>
<?php include '../sidebar.php'; ?>
<main class="konten">
    <div class="topbar"><h2>Ubah Aset</h2><span><?= e($_SESSION['nama']) ?></span></div>
    <div class="kartu form-kartu">
        <?php foreach ($error as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
        <form method="POST">
            <?php include 'form.php'; ?>
            <div class="tombol-form">
                <a href="index.php" class="btn btn-abu">Batal</a>
                <button type="submit" class="btn btn-utama">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</main>
</body>
</html>
