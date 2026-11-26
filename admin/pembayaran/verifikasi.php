<?php
require '../../includes/auth.php';
cek_login('admin');
require_once __DIR__ . '/../../config/koneksi.php';
$aktif = 'pembayaran';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

function ambil_pembayaran($koneksi, $id)
{
    $stmt = mysqli_prepare($koneksi, "
        SELECT b.*, t.total, t.berat_kg, t.jatuh_tempo, t.status AS status_tagihan, p.nama, l.nama_layanan
        FROM pembayaran b
        JOIN tagihan t ON t.id_tagihan = b.id_tagihan
        JOIN pelanggan p ON p.id_pelanggan = t.id_pelanggan
        JOIN layanan l ON l.id_layanan = t.id_layanan
        WHERE b.id_pembayaran = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

$b = ambil_pembayaran($koneksi, $id);
if (!$b) {
    set_pesan('error', "Data pembayaran tidak ditemukan.");
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $catatan = trim($_POST['catatan'] ?? '');

    if ($b['status'] !== 'menunggu') {
        $error = "Pembayaran ini sudah divalidasi sebelumnya.";
    } elseif ($aksi === 'tolak' && $catatan === '') {
        $error = "Alasan penolakan wajib diisi.";
    } elseif (!in_array($aksi, ['terima', 'tolak'])) {
        $error = "Aksi tidak dikenali.";
    } else {
        $status_bayar   = $aksi === 'terima' ? 'diterima' : 'ditolak';
        $status_tagihan = $aksi === 'terima' ? 'lunas' : 'ditolak';
        $catatan = $aksi === 'terima' ? null : $catatan;

        mysqli_begin_transaction($koneksi);
        try {
            $s = mysqli_prepare($koneksi, "UPDATE pembayaran SET status = ?, catatan_admin = ?, divalidasi_pada = NOW() WHERE id_pembayaran = ? AND status = 'menunggu'");
            mysqli_stmt_bind_param($s, "ssi", $status_bayar, $catatan, $id);
            mysqli_stmt_execute($s);
            if (mysqli_stmt_affected_rows($s) !== 1) throw new Exception("sudah divalidasi");

            $s = mysqli_prepare($koneksi, "UPDATE tagihan SET status = ? WHERE id_tagihan = ?");
            mysqli_stmt_bind_param($s, "si", $status_tagihan, $b['id_tagihan']);
            mysqli_stmt_execute($s);

            mysqli_commit($koneksi);
            set_pesan('sukses', "Pembayaran " . kode_tagihan($b['id_tagihan']) . " dari {$b['nama']} " .
                ($aksi === 'terima' ? "diterima. Tagihan sekarang Lunas." : "ditolak."));
            header("Location: index.php");
            exit;
        } catch (Throwable $ex) {
            mysqli_rollback($koneksi);
            $error = "Gagal menyimpan. Mungkin pembayaran ini sudah divalidasi.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Periksa Pembayaran - LaundryPro</title>
    <link rel="stylesheet" href="/laundrypro/assets/style.css">
</head>
<body>
<?php include '../sidebar.php'; ?>
<main class="konten">
    <div class="topbar">
        <h2>Periksa Pembayaran</h2>
        <span><?= e($_SESSION['nama']) ?></span>
    </div>

    <div class="dua-kolom">
        <div class="kartu">
            <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

            <h3><?= kode_tagihan($b['id_tagihan']) ?> &middot; <?= e($b['nama']) ?></h3>
            <table class="tabel-detail">
                <tr><td>Layanan</td><td><?= e($b['nama_layanan']) ?></td></tr>
                <tr><td>Berat</td><td><?= rtrim(rtrim(number_format($b['berat_kg'], 2, ',', '.'), '0'), ',') ?> kg</td></tr>
                <tr><td>Total tagihan</td><td><b><?= rupiah($b['total']) ?></b></td></tr>
                <tr><td>Metode</td><td><?= $b['metode'] == 'qris' ? 'QRIS' : 'Transfer Bank' ?></td></tr>
                <tr><td>Tanggal kirim</td><td><?= tgl($b['tanggal_bayar']) ?></td></tr>
                <tr><td>Status</td><td><?= label_status_bayar($b['status']) ?></td></tr>
                <?php if ($b['catatan_admin']): ?>
                    <tr><td>Alasan ditolak</td><td><?= e($b['catatan_admin']) ?></td></tr>
                <?php endif; ?>
            </table>

            <?php if ($b['status'] === 'menunggu'): ?>
                <form method="POST" class="form-validasi">
                    <input type="hidden" name="id" value="<?= $b['id_pembayaran'] ?>">
                    <label>Alasan penolakan (wajib diisi kalau ditolak)</label>
                    <textarea name="catatan" rows="2" placeholder="contoh: nominal tidak sesuai, bukti tidak jelas"></textarea>
                    <div class="tombol-form">
                        <a href="index.php" class="btn btn-abu">Kembali</a>
                        <button type="submit" name="aksi" value="tolak" class="btn btn-merah">Tolak</button>
                        <button type="submit" name="aksi" value="terima" class="btn btn-hijau">Terima</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="tombol-form"><a href="index.php" class="btn btn-abu">Kembali</a></div>
            <?php endif; ?>
        </div>

        <div class="kartu">
            <h3>Bukti Pembayaran</h3>
            <a href="/laundrypro/uploads/bukti_bayar/<?= e($b['bukti']) ?>" target="_blank">
                <img src="/laundrypro/uploads/bukti_bayar/<?= e($b['bukti']) ?>" alt="Bukti pembayaran" class="gambar-bukti">
            </a>
            <small>Klik gambar untuk memperbesar.</small>
        </div>
    </div>
</main>
</body>
</html>
