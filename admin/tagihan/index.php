<?php
require '../../includes/auth.php';
cek_login('admin');
require_once __DIR__ . '/../../config/koneksi.php';
$aktif = 'tagihan';

$ringkas = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT COUNT(*) AS total,
           SUM(status IN ('belum_bayar','ditolak')) AS belum,
           SUM(status = 'menunggu_validasi') AS menunggu,
           SUM(status = 'lunas') AS lunas,
           SUM(status != 'lunas' AND jatuh_tempo < NOW()) AS telat
    FROM tagihan"));

$cari   = trim($_GET['cari'] ?? '');
$status = $_GET['status'] ?? '';

$sql = "SELECT t.*, p.nama, l.nama_layanan
        FROM tagihan t
        JOIN pelanggan p ON p.id_pelanggan = t.id_pelanggan
        JOIN layanan l ON l.id_layanan = t.id_layanan
        WHERE p.nama LIKE ?";
$param = ["%$cari%"];
$tipe = "s";

if ($status === 'telat') {
    $sql .= " AND t.status != 'lunas' AND t.jatuh_tempo < NOW()";
} elseif (in_array($status, ['belum_bayar', 'menunggu_validasi', 'lunas', 'ditolak'])) {
    $sql .= " AND t.status = ?";
    $param[] = $status;
    $tipe .= "s";
}
$sql .= " ORDER BY t.id_tagihan DESC";

$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, $tipe, ...$param);
mysqli_stmt_execute($stmt);
$data = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tagihan - LaundryPro</title>
    <link rel="stylesheet" href="/laundrypro/assets/style.css">
</head>
<body>
<?php include '../sidebar.php'; ?>
<main class="konten">
    <div class="topbar">
        <h2>Tagihan</h2>
        <span><?= e($_SESSION['nama']) ?></span>
    </div>

    <h1 class="judul">Data Tagihan</h1>
    <p class="keterangan">Buat dan pantau tagihan pelanggan tetap.</p>

    <?php tampil_pesan(); ?>

    <div class="baris-statistik">
        <div class="kartu stat"><span>Total Tagihan</span><b><?= (int)$ringkas['total'] ?></b></div>
        <div class="kartu stat"><span>Belum Dibayar</span><b><?= (int)$ringkas['belum'] ?></b></div>
        <div class="kartu stat"><span>Menunggu Validasi</span><b><?= (int)$ringkas['menunggu'] ?></b></div>
        <div class="kartu stat"><span>Lunas</span><b><?= (int)$ringkas['lunas'] ?></b></div>
        <div class="kartu stat"><span>Lewat Jatuh Tempo</span><b class="teks-merah"><?= (int)$ringkas['telat'] ?></b></div>
    </div>

    <div class="kartu">
        <div class="kepala-kartu">
            <h3>Daftar Tagihan</h3>
            <a href="tambah.php" class="btn btn-utama">+ Buat Tagihan</a>
        </div>

        <form method="GET" class="form-cari">
            <input type="text" name="cari" placeholder="Cari nama pelanggan..." value="<?= e($cari) ?>">
            <select name="status">
                <option value="">Semua Status</option>
                <option value="belum_bayar" <?= $status == 'belum_bayar' ? 'selected' : '' ?>>Belum Bayar</option>
                <option value="menunggu_validasi" <?= $status == 'menunggu_validasi' ? 'selected' : '' ?>>Menunggu Validasi</option>
                <option value="lunas" <?= $status == 'lunas' ? 'selected' : '' ?>>Lunas</option>
                <option value="ditolak" <?= $status == 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                <option value="telat" <?= $status == 'telat' ? 'selected' : '' ?>>Lewat Jatuh Tempo</option>
            </select>
            <button type="submit" class="btn btn-utama">Cari</button>
        </form>

        <div class="tabel-wrap">
        <table class="tabel">
            <thead>
                <tr>
                    <th>Kode</th><th>Pelanggan</th><th>Layanan</th><th>Berat</th>
                    <th>Total</th><th>Mulai</th><th>Jatuh Tempo</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($data) == 0): ?>
                <tr><td colspan="8" class="kosong">Belum ada tagihan.</td></tr>
            <?php endif; ?>
            <?php while ($t = mysqli_fetch_assoc($data)): ?>
                <tr>
                    <td><?= kode_tagihan($t['id_tagihan']) ?></td>
                    <td><b><?= e($t['nama']) ?></b></td>
                    <td><?= e($t['nama_layanan']) ?></td>
                    <td><?= rtrim(rtrim(number_format($t['berat_kg'], 2, ',', '.'), '0'), ',') ?> kg</td>
                    <td><?= rupiah($t['total']) ?></td>
                    <td><?= tgl($t['waktu_mulai']) ?></td>
                    <td>
                        <?= tgl($t['jatuh_tempo']) ?>
                        <?php if (lewat_jatuh_tempo($t['jatuh_tempo'], $t['status'])): ?>
                            <br><span class="label label-merah">Lewat jatuh tempo</span>
                        <?php endif; ?>
                    </td>
                    <td><?= label_status_tagihan($t['status']) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>
</main>
</body>
</html>
