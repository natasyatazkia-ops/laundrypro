<?php
require '../includes/auth.php';
cek_login('admin');
require_once __DIR__ . '/../config/koneksi.php';
$aktif = 'dashboard';

$angka = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT
      (SELECT COUNT(*) FROM pelanggan WHERE status = 'aktif') AS pelanggan,
      (SELECT COUNT(*) FROM tagihan WHERE status IN ('belum_bayar','ditolak')) AS belum,
      (SELECT COUNT(*) FROM pembayaran WHERE status = 'menunggu') AS menunggu,
      (SELECT COALESCE(SUM(t.total), 0) FROM pembayaran b JOIN tagihan t ON t.id_tagihan = b.id_tagihan
        WHERE b.status = 'diterima' AND DATE_FORMAT(b.divalidasi_pada, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')) AS masuk"));

$telat = mysqli_query($koneksi, "
    SELECT t.id_tagihan, t.total, t.jatuh_tempo, p.nama FROM tagihan t
    JOIN pelanggan p ON p.id_pelanggan = t.id_pelanggan
    WHERE t.status IN ('belum_bayar','ditolak') AND t.jatuh_tempo < NOW()
    ORDER BY t.jatuh_tempo LIMIT 5");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - LaundryPro</title>
    <link rel="stylesheet" href="/laundrypro/assets/style.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
<main class="konten">
    <div class="topbar"><h2>Dashboard</h2><span><?= e($_SESSION['nama']) ?></span></div>

    <div class="baris-statistik">
        <div class="kartu stat"><span>Pelanggan Aktif</span><b><?= (int)$angka['pelanggan'] ?></b></div>
        <div class="kartu stat"><span>Tagihan Belum Dibayar</span><b><?= (int)$angka['belum'] ?></b></div>
        <div class="kartu stat"><span>Menunggu Validasi</span><b><?= (int)$angka['menunggu'] ?></b></div>
        <div class="kartu stat"><span>Uang Masuk Bulan Ini</span><b><?= rupiah($angka['masuk']) ?></b></div>
    </div>

    <?php if ($angka['menunggu'] > 0): ?>
        <div class="alert alert-peringatan">Ada <?= (int)$angka['menunggu'] ?> pembayaran yang menunggu dicek.
            <a href="pembayaran/index.php">Periksa sekarang</a></div>
    <?php endif; ?>

    <div class="kartu">
        <h3>Tagihan Lewat Jatuh Tempo</h3><br>
        <table class="tabel">
            <thead><tr><th>Kode</th><th>Pelanggan</th><th>Total</th><th>Jatuh Tempo</th></tr></thead>
            <tbody>
            <?php if (mysqli_num_rows($telat) == 0): ?>
                <tr><td colspan="4" class="kosong">Tidak ada tagihan yang lewat jatuh tempo.</td></tr>
            <?php endif; ?>
            <?php while ($t = mysqli_fetch_assoc($telat)): ?>
                <tr>
                    <td><?= kode_tagihan($t['id_tagihan']) ?></td>
                    <td><?= e($t['nama']) ?></td>
                    <td><?= rupiah($t['total']) ?></td>
                    <td class="teks-merah"><?= tgl($t['jatuh_tempo']) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>
