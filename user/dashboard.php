<?php
require '../includes/auth.php';
cek_login('pelanggan');
require_once __DIR__ . '/../config/koneksi.php';
$aktif = 'dashboard';

// Ambil tagihan yang belum dibayar untuk dicek pengingatnya
$stmt = mysqli_prepare($koneksi, "
    SELECT id_tagihan, total, jatuh_tempo, status FROM tagihan
    WHERE id_pelanggan = ? AND status IN ('belum_bayar','ditolak')
    ORDER BY jatuh_tempo");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['id_pelanggan']);
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);

$pengingat = [];
$jml_belum = 0;
$total_belum = 0;
while ($t = mysqli_fetch_assoc($hasil)) {
    $jml_belum++;
    $total_belum += $t['total'];
    $jenis = cek_pengingat($t['jatuh_tempo'], $t['status']);
    if ($jenis) {
        $pengingat[] = $t + ['jenis' => $jenis];
    }
}

$stmt = mysqli_prepare($koneksi, "SELECT COUNT(*) FROM tagihan WHERE id_pelanggan = ? AND status = 'menunggu_validasi'");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['id_pelanggan']);
mysqli_stmt_execute($stmt);
$jml_menunggu = mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - LaundryPro</title>
    <link rel="stylesheet" href="/laundrypro/assets/style.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
<main class="konten">
    <div class="topbar"><h2>Dashboard</h2><span><?= e($_SESSION['nama']) ?></span></div>

    <h1 class="judul">Halo, <?= e($_SESSION['nama']) ?>!</h1>
    <p class="keterangan">Berikut ringkasan tagihan laundry Anda.</p>

    <?php foreach ($pengingat as $p): ?>
        <div class="alert <?= $p['jenis'] == 'lewat' ? 'alert-error' : 'alert-peringatan' ?> pengingat">
            <?php if ($p['jenis'] == 'lewat'): ?>
                <b>Tagihan <?= kode_tagihan($p['id_tagihan']) ?> sudah lewat jatuh tempo</b> (<?= tgl($p['jatuh_tempo']) ?>).
            <?php else: ?>
                <b>Tagihan <?= kode_tagihan($p['id_tagihan']) ?> akan jatuh tempo</b> pada <?= tgl($p['jatuh_tempo']) ?>.
            <?php endif; ?>
            Total <?= rupiah($p['total']) ?>.
            <a href="pembayaran/bayar.php?id=<?= $p['id_tagihan'] ?>">Bayar sekarang</a>
        </div>
    <?php endforeach; ?>

    <div class="baris-statistik">
        <div class="kartu stat"><span>Tagihan Belum Dibayar</span><b><?= $jml_belum ?></b></div>
        <div class="kartu stat"><span>Total Belum Dibayar</span><b><?= rupiah($total_belum) ?></b></div>
        <div class="kartu stat"><span>Menunggu Validasi</span><b><?= (int)$jml_menunggu ?></b></div>
    </div>

    <div class="kartu">
        <a href="tagihan/index.php" class="btn btn-utama">Lihat Semua Tagihan</a>
        <a href="pembayaran/riwayat.php" class="btn btn-abu">Riwayat Pembayaran</a>
    </div>
</main>
</body>
</html>
