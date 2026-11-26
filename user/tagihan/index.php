<?php
require '../../includes/auth.php';
cek_login('pelanggan');
require_once __DIR__ . '/../../config/koneksi.php';
$aktif = 'tagihan';

$stmt = mysqli_prepare($koneksi, "
    SELECT t.*, l.nama_layanan FROM tagihan t
    JOIN layanan l ON l.id_layanan = t.id_layanan
    WHERE t.id_pelanggan = ? ORDER BY t.id_tagihan DESC");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['id_pelanggan']);
mysqli_stmt_execute($stmt);
$data = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tagihan Saya - LaundryPro</title>
    <link rel="stylesheet" href="/laundrypro/assets/style.css">
</head>
<body>
<?php include '../sidebar.php'; ?>
<main class="konten">
    <div class="topbar">
        <h2>Tagihan Saya</h2>
        <span><?= e($_SESSION['nama']) ?></span>
    </div>

    <?php tampil_pesan(); ?>

    <div class="kartu">
        <div class="tabel-wrap">
        <table class="tabel">
            <thead>
                <tr><th>Kode</th><th>Layanan</th><th>Total</th><th>Jatuh Tempo</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($data) == 0): ?>
                <tr><td colspan="6" class="kosong">Belum ada tagihan.</td></tr>
            <?php endif; ?>
            <?php while ($t = mysqli_fetch_assoc($data)): ?>
                <tr>
                    <td><?= kode_tagihan($t['id_tagihan']) ?></td>
                    <td><?= e($t['nama_layanan']) ?></td>
                    <td><?= rupiah($t['total']) ?></td>
                    <td>
                        <?= tgl($t['jatuh_tempo']) ?>
                        <?php if (lewat_jatuh_tempo($t['jatuh_tempo'], $t['status'])): ?>
                            <br><span class="label label-merah">Lewat jatuh tempo</span>
                        <?php endif; ?>
                    </td>
                    <td><?= label_status_tagihan($t['status']) ?></td>
                    <td><a href="detail.php?id=<?= $t['id_tagihan'] ?>" class="btn btn-utama btn-kecil">Detail</a></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>
</main>
</body>
</html>
