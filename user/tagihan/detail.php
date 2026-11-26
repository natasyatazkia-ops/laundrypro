<?php
require '../../includes/auth.php';
cek_login('pelanggan');
require_once __DIR__ . '/../../config/koneksi.php';
$aktif = 'tagihan';

$id = (int)($_GET['id'] ?? 0);

// Pelanggan hanya boleh melihat tagihan miliknya sendiri
$stmt = mysqli_prepare($koneksi, "
    SELECT t.*, l.nama_layanan, l.durasi_jam FROM tagihan t
    JOIN layanan l ON l.id_layanan = t.id_layanan
    WHERE t.id_tagihan = ? AND t.id_pelanggan = ?");
mysqli_stmt_bind_param($stmt, "ii", $id, $_SESSION['id_pelanggan']);
mysqli_stmt_execute($stmt);
$t = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$t) {
    set_pesan('error', "Tagihan tidak ditemukan.");
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Tagihan - LaundryPro</title>
    <link rel="stylesheet" href="/laundrypro/assets/style.css">
</head>
<body>
<?php include '../sidebar.php'; ?>
<main class="konten">
    <div class="topbar">
        <h2>Detail Tagihan</h2>
        <span><?= e($_SESSION['nama']) ?></span>
    </div>

    <div class="kartu form-kartu">
        <h3><?= kode_tagihan($t['id_tagihan']) ?></h3>
        <table class="tabel-detail">
            <tr><td>Layanan</td><td><?= e($t['nama_layanan']) ?> (<?= $t['durasi_jam'] ?> jam)</td></tr>
            <tr><td>Berat</td><td><?= rtrim(rtrim(number_format($t['berat_kg'], 2, ',', '.'), '0'), ',') ?> kg</td></tr>
            <tr><td>Tarif</td><td><?= rupiah($t['tarif_per_kg']) ?>/kg</td></tr>
            <tr><td>Total</td><td><b><?= rupiah($t['total']) ?></b></td></tr>
            <tr><td>Waktu Mulai</td><td><?= tgl($t['waktu_mulai']) ?></td></tr>
            <tr><td>Jatuh Tempo</td><td><?= tgl($t['jatuh_tempo']) ?>
                <?php if (lewat_jatuh_tempo($t['jatuh_tempo'], $t['status'])): ?>
                    <span class="label label-merah">Lewat jatuh tempo</span>
                <?php endif; ?></td></tr>
            <tr><td>Status</td><td><?= label_status_tagihan($t['status']) ?></td></tr>
        </table>

        <div class="tombol-form">
            <a href="index.php" class="btn btn-abu">Kembali</a>
            <?php if (in_array($t['status'], ['belum_bayar', 'ditolak'])): ?>
                <a href="../pembayaran/bayar.php?id=<?= $t['id_tagihan'] ?>" class="btn btn-hijau">Bayar Sekarang</a>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>
