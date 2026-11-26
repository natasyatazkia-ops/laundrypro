<?php
require '../../includes/auth.php';
cek_login('admin');
require_once __DIR__ . '/../../config/koneksi.php';
$aktif = 'pembayaran';

$status = $_GET['status'] ?? 'menunggu';

$sql = "SELECT b.*, t.total, p.nama FROM pembayaran b
        JOIN tagihan t ON t.id_tagihan = b.id_tagihan
        JOIN pelanggan p ON p.id_pelanggan = t.id_pelanggan";
if (in_array($status, ['menunggu', 'diterima', 'ditolak'])) {
    $sql .= " WHERE b.status = '$status'";   // aman: nilai sudah dicek dari daftar
}
$sql .= " ORDER BY b.id_pembayaran DESC";
$data = mysqli_query($koneksi, $sql);

$jml_menunggu = mysqli_fetch_row(mysqli_query($koneksi, "SELECT COUNT(*) FROM pembayaran WHERE status = 'menunggu'"))[0];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - LaundryPro</title>
    <link rel="stylesheet" href="/laundrypro/assets/style.css">
</head>
<body>
<?php include '../sidebar.php'; ?>
<main class="konten">
    <div class="topbar">
        <h2>Pembayaran</h2>
        <span><?= e($_SESSION['nama']) ?></span>
    </div>

    <h1 class="judul">Validasi Pembayaran</h1>
    <p class="keterangan">Ada <b><?= $jml_menunggu ?></b> pembayaran yang menunggu dicek.</p>

    <?php tampil_pesan(); ?>

    <div class="kartu">
        <div class="tab-status">
            <?php foreach (['menunggu' => 'Menunggu', 'diterima' => 'Diterima', 'ditolak' => 'Ditolak', 'semua' => 'Semua'] as $k => $v): ?>
                <a href="?status=<?= $k ?>" class="<?= $status == $k ? 'aktif' : '' ?>"><?= $v ?></a>
            <?php endforeach; ?>
        </div>

        <div class="tabel-wrap">
        <table class="tabel">
            <thead><tr><th>Tanggal</th><th>Pelanggan</th><th>Tagihan</th><th>Total</th><th>Metode</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if (mysqli_num_rows($data) == 0): ?>
                <tr><td colspan="7" class="kosong">Tidak ada data pembayaran.</td></tr>
            <?php endif; ?>
            <?php while ($b = mysqli_fetch_assoc($data)): ?>
                <tr>
                    <td><?= tgl($b['tanggal_bayar']) ?></td>
                    <td><b><?= e($b['nama']) ?></b></td>
                    <td><?= kode_tagihan($b['id_tagihan']) ?></td>
                    <td><?= rupiah($b['total']) ?></td>
                    <td><?= $b['metode'] == 'qris' ? 'QRIS' : 'Transfer Bank' ?></td>
                    <td><?= label_status_bayar($b['status']) ?></td>
                    <td>
                        <a href="verifikasi.php?id=<?= $b['id_pembayaran'] ?>" class="btn btn-kecil <?= $b['status'] == 'menunggu' ? 'btn-utama' : 'btn-abu' ?>">
                            <?= $b['status'] == 'menunggu' ? 'Periksa' : 'Lihat' ?>
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>
</main>
</body>
</html>
