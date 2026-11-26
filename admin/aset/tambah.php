<?php
require '../../includes/auth.php';
cek_login('admin');
require_once __DIR__ . '/../../config/koneksi.php';
require 'validasi.php';
$aktif = 'aset';

$isi = ['nama_aset' => '', 'kategori' => '', 'tanggal_perolehan' => date('Y-m-d'),
        'nilai_perolehan' => '', 'kondisi' => 'baik', 'status' => 'dipakai'];
$error = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isi = ambil_form_aset();
    $error = validasi_aset($isi);

    if (!$error) {
        $stmt = mysqli_prepare($koneksi, "INSERT INTO aset (nama_aset, kategori, tanggal_perolehan, nilai_perolehan, kondisi, status) VALUES (?, ?, ?, ?, ?, ?)");
        $nilai = (int)$isi['nilai_perolehan'];
        mysqli_stmt_bind_param($stmt, "sssiss", $isi['nama_aset'], $isi['kategori'], $isi['tanggal_perolehan'], $nilai, $isi['kondisi'], $isi['status']);
        mysqli_stmt_execute($stmt);
        set_pesan('sukses', "Aset {$isi['nama_aset']} berhasil ditambahkan.");
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
    <title>Tambah Aset - LaundryPro</title>
    <link rel="stylesheet" href="/laundrypro/assets/style.css">
</head>
<body>
<?php include '../sidebar.php'; ?>
<main class="konten">
    <div class="topbar"><h2>Tambah Aset</h2><span><?= e($_SESSION['nama']) ?></span></div>
    <div class="kartu form-kartu">
        <?php foreach ($error as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
        <form method="POST">
            <?php include 'form.php'; ?>
            <div class="tombol-form">
                <a href="index.php" class="btn btn-abu">Batal</a>
                <button type="submit" class="btn btn-utama">Simpan</button>
            </div>
        </form>
    </div>
</main>
</body>
</html>
