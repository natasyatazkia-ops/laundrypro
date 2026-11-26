<?php
require '../../includes/auth.php';
cek_login('admin');
require_once __DIR__ . '/../../config/koneksi.php';
$aktif = 'laporan';

// Periode default: bulan ini
$dari   = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-t');
$error  = '';

$cek = fn($t) => ($d = DateTime::createFromFormat('Y-m-d', $t)) && $d->format('Y-m-d') === $t;
if (!$cek($dari) || !$cek($sampai)) {
    $error = "Format tanggal tidak valid.";
    $dari = date('Y-m-01'); $sampai = date('Y-m-t');
} elseif ($dari > $sampai) {
    $error = "Tanggal 'dari' tidak boleh lebih besar dari tanggal 'sampai'.";
    [$dari, $sampai] = [$sampai, $dari];
}
$awal  = "$dari 00:00:00";
$akhir = "$sampai 23:59:59";

// Ringkasan tagihan di periode ini (berdasarkan waktu mulai layanan)
$stmt = mysqli_prepare($koneksi, "
    SELECT COUNT(*) AS jumlah,
           COALESCE(SUM(total), 0) AS nilai,
           COALESCE(SUM(CASE WHEN status = 'lunas' THEN total END), 0) AS lunas,
           COALESCE(SUM(CASE WHEN status != 'lunas' THEN total END), 0) AS belum,
           COALESCE(SUM(berat_kg), 0) AS berat
    FROM tagihan WHERE waktu_mulai BETWEEN ? AND ?");
mysqli_stmt_bind_param($stmt, "ss", $awal, $akhir);
mysqli_stmt_execute($stmt);
$r = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Daftar tagihan
$stmt = mysqli_prepare($koneksi, "
    SELECT t.*, p.nama, l.nama_layanan FROM tagihan t
    JOIN pelanggan p ON p.id_pelanggan = t.id_pelanggan
    JOIN layanan l ON l.id_layanan = t.id_layanan
    WHERE t.waktu_mulai BETWEEN ? AND ? ORDER BY t.waktu_mulai");
mysqli_stmt_bind_param($stmt, "ss", $awal, $akhir);
mysqli_stmt_execute($stmt);
$tagihan = mysqli_stmt_get_result($stmt);

// Pembayaran yang diterima di periode ini (berdasarkan tanggal divalidasi)
$stmt = mysqli_prepare($koneksi, "
    SELECT b.*, t.total, p.nama FROM pembayaran b
    JOIN tagihan t ON t.id_tagihan = b.id_tagihan
    JOIN pelanggan p ON p.id_pelanggan = t.id_pelanggan
    WHERE b.status = 'diterima' AND b.divalidasi_pada BETWEEN ? AND ?
    ORDER BY b.divalidasi_pada");
mysqli_stmt_bind_param($stmt, "ss", $awal, $akhir);
mysqli_stmt_execute($stmt);
$bayar = mysqli_stmt_get_result($stmt);
$total_masuk = 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - LaundryPro</title>
    <link rel="stylesheet" href="/laundrypro/assets/style.css">
</head>
<body>
<?php include '../sidebar.php'; ?>
<main class="konten">
    <div class="topbar">
        <h2>Laporan</h2>
        <span><?= e($_SESSION['nama']) ?></span>
    </div>

    <h1 class="judul">Laporan Tagihan & Pembayaran</h1>
    <p class="keterangan">Periode <?= date('d/m/Y', strtotime($dari)) ?> s.d. <?= date('d/m/Y', strtotime($sampai)) ?></p>

    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <div class="kartu no-print">
        <form method="GET" class="form-cari">
            <label>Dari <input type="date" name="dari" value="<?= e($dari) ?>"></label>
            <label>Sampai <input type="date" name="sampai" value="<?= e($sampai) ?>"></label>
            <button type="submit" class="btn btn-utama">Tampilkan</button>
            <button type="button" class="btn btn-abu" onclick="window.print()">Cetak</button>
        </form>
    </div>

    <div class="baris-statistik">
        <div class="kartu stat"><span>Jumlah Tagihan</span><b><?= (int)$r['jumlah'] ?></b></div>
        <div class="kartu stat"><span>Total Berat</span><b><?= rtrim(rtrim(number_format($r['berat'], 2, ',', '.'), '0'), ',') ?> kg</b></div>
        <div class="kartu stat"><span>Nilai Tagihan</span><b><?= rupiah($r['nilai']) ?></b></div>
        <div class="kartu stat"><span>Sudah Lunas</span><b><?= rupiah($r['lunas']) ?></b></div>
        <div class="kartu stat"><span>Belum Lunas</span><b class="teks-merah"><?= rupiah($r['belum']) ?></b></div>
    </div>

    <div class="kartu">
        <h3>Daftar Tagihan</h3><br>
        <div class="tabel-wrap">
        <table class="tabel">
            <thead><tr><th>Kode</th><th>Tanggal</th><th>Pelanggan</th><th>Layanan</th><th>Berat</th><th>Total</th><th>Status</th></tr></thead>
            <tbody>
            <?php if (mysqli_num_rows($tagihan) == 0): ?>
                <tr><td colspan="7" class="kosong">Tidak ada tagihan di periode ini.</td></tr>
            <?php endif; ?>
            <?php while ($t = mysqli_fetch_assoc($tagihan)): ?>
                <tr>
                    <td><?= kode_tagihan($t['id_tagihan']) ?></td>
                    <td><?= tgl($t['waktu_mulai']) ?></td>
                    <td><?= e($t['nama']) ?></td>
                    <td><?= e($t['nama_layanan']) ?></td>
                    <td><?= rtrim(rtrim(number_format($t['berat_kg'], 2, ',', '.'), '0'), ',') ?> kg</td>
                    <td><?= rupiah($t['total']) ?></td>
                    <td><?= label_status_tagihan($t['status']) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>

    <div class="kartu">
        <h3>Pembayaran Diterima</h3><br>
        <div class="tabel-wrap">
        <table class="tabel">
            <thead><tr><th>Tanggal Validasi</th><th>Tagihan</th><th>Pelanggan</th><th>Metode</th><th>Jumlah</th></tr></thead>
            <tbody>
            <?php if (mysqli_num_rows($bayar) == 0): ?>
                <tr><td colspan="5" class="kosong">Tidak ada pembayaran diterima di periode ini.</td></tr>
            <?php endif; ?>
            <?php while ($b = mysqli_fetch_assoc($bayar)): $total_masuk += $b['total']; ?>
                <tr>
                    <td><?= tgl($b['divalidasi_pada']) ?></td>
                    <td><?= kode_tagihan($b['id_tagihan']) ?></td>
                    <td><?= e($b['nama']) ?></td>
                    <td><?= $b['metode'] == 'qris' ? 'QRIS' : 'Transfer Bank' ?></td>
                    <td><?= rupiah($b['total']) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
            <tfoot><tr><th colspan="4">Total Uang Masuk</th><th><?= rupiah($total_masuk) ?></th></tr></tfoot>
        </table>
        </div>
    </div>
</main>
</body>
</html>
