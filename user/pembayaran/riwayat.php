<?php
require '../../includes/auth.php';
cek_login('pelanggan');
require_once __DIR__ . '/../../config/koneksi.php';
$aktif = 'riwayat';

$stmt = mysqli_prepare($koneksi, "
    SELECT b.*, t.total FROM pembayaran b
    JOIN tagihan t ON t.id_tagihan = b.id_tagihan
    WHERE t.id_pelanggan = ? ORDER BY b.id_pembayaran DESC");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['id_pelanggan']);
mysqli_stmt_execute($stmt);
$data = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pembayaran - LaundryPro</title>
    <link rel="stylesheet" href="/laundrypro/assets/style.css">
</head>
<body>
<?php include '../sidebar.php'; ?>
<main class="konten">
    <div class="topbar">
        <h2>Riwayat Pembayaran</h2>
        <span><?= e($_SESSION['nama']) ?></span>
    </div>

    <?php tampil_pesan(); ?>

    <div class="kartu">
        <div class="tabel-wrap">
        <table class="tabel">
            <thead><tr><th>Tanggal</th><th>Tagihan</th><th>Total</th><th>Metode</th><th>Bukti</th><th>Status</th><th>Keterangan</th></tr></thead>
            <tbody>
            <?php if (mysqli_num_rows($data) == 0): ?>
                <tr><td colspan="7" class="kosong">Belum ada riwayat pembayaran.</td></tr>
            <?php endif; ?>
            <?php while ($b = mysqli_fetch_assoc($data)): ?>
                <tr>
                    <td><?= tgl($b['tanggal_bayar']) ?></td>
                    <td><?= kode_tagihan($b['id_tagihan']) ?></td>
                    <td><?= rupiah($b['total']) ?></td>
                    <td><?= $b['metode'] == 'qris' ? 'QRIS' : 'Transfer Bank' ?></td>
                    <td><a href="/laundrypro/uploads/bukti_bayar/<?= e($b['bukti']) ?>" target="_blank">Lihat</a></td>
                    <td><?= label_status_bayar($b['status']) ?></td>
                    <td><?= e($b['catatan_admin'] ?? '-') ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>
</main>
</body>
</html>
