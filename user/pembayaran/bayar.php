<?php
require '../../includes/auth.php';
cek_login('pelanggan');
require_once __DIR__ . '/../../config/koneksi.php';
require '../../config/rekening.php';
$aktif = 'bayar';

$id = (int)($_GET['id'] ?? 0);

// Tagihan harus milik pelanggan ini dan statusnya Belum Bayar / Ditolak
$stmt = mysqli_prepare($koneksi, "
    SELECT t.*, l.nama_layanan FROM tagihan t
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
if (!in_array($t['status'], ['belum_bayar', 'ditolak'])) {
    set_pesan('error', "Tagihan " . kode_tagihan($t['id_tagihan']) . " tidak bisa dibayar karena statusnya sudah " .
        ($t['status'] == 'lunas' ? 'Lunas' : 'Menunggu Validasi') . ".");
    header("Location: index.php");
    exit;
}

// Alasan penolakan terakhir (kalau pernah ditolak)
$tolak = null;
if ($t['status'] == 'ditolak') {
    $q = mysqli_prepare($koneksi, "SELECT catatan_admin FROM pembayaran WHERE id_tagihan = ? AND status = 'ditolak' ORDER BY id_pembayaran DESC LIMIT 1");
    mysqli_stmt_bind_param($q, "i", $id);
    mysqli_stmt_execute($q);
    $tolak = mysqli_fetch_assoc(mysqli_stmt_get_result($q));
}

$error = [];
$metode = $_POST['metode'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!in_array($metode, ['transfer', 'qris'])) $error[] = "Pilih metode pembayaran.";

    $file = $_FILES['bukti'] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $error[] = "Bukti pembayaran wajib diupload.";
    } elseif ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE || $file['size'] > 2 * 1024 * 1024) {
        $error[] = "Ukuran file maksimal 2 MB.";
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $error[] = "Upload gagal, coba lagi.";
    } else {
        // Cek isi file benar-benar gambar (bukan cuma dari nama file)
        $tipe_asli = mime_content_type($file['tmp_name']);
        $boleh = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (!isset($boleh[$tipe_asli])) {
            $error[] = "File harus berupa gambar JPG atau PNG.";
        }
    }

    if (!$error) {
        // Nama file acak supaya tidak bisa ditebak
        $nama_file = 'bukti_' . $id . '_' . bin2hex(random_bytes(8)) . '.' . $boleh[$tipe_asli];
        $tujuan = __DIR__ . '/../../uploads/bukti_bayar/' . $nama_file;

        if (!move_uploaded_file($file['tmp_name'], $tujuan)) {
            $error[] = "Gagal menyimpan file. Coba lagi.";
        } else {
            mysqli_begin_transaction($koneksi);
            try {
                $s = mysqli_prepare($koneksi, "INSERT INTO pembayaran (id_tagihan, metode, bukti) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($s, "iss", $id, $metode, $nama_file);
                mysqli_stmt_execute($s);

                // Status hanya berubah kalau masih Belum Bayar / Ditolak (cegah dobel kirim)
                $s = mysqli_prepare($koneksi, "UPDATE tagihan SET status = 'menunggu_validasi' WHERE id_tagihan = ? AND status IN ('belum_bayar','ditolak')");
                mysqli_stmt_bind_param($s, "i", $id);
                mysqli_stmt_execute($s);
                if (mysqli_stmt_affected_rows($s) !== 1) {
                    throw new Exception("status berubah");
                }

                mysqli_commit($koneksi);
                set_pesan('sukses', "Bukti pembayaran " . kode_tagihan($id) . " berhasil dikirim. Tunggu admin memvalidasi.");
                header("Location: riwayat.php");
                exit;
            } catch (Throwable $ex) {
                mysqli_rollback($koneksi);
                @unlink($tujuan);
                $error[] = "Gagal menyimpan pembayaran. Coba lagi.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bayar <?= kode_tagihan($t['id_tagihan']) ?> - LaundryPro</title>
    <link rel="stylesheet" href="/laundrypro/assets/style.css">
</head>
<body>
<?php include '../sidebar.php'; ?>
<main class="konten">
    <div class="topbar">
        <h2>Bayar Tagihan</h2>
        <span><?= e($_SESSION['nama']) ?></span>
    </div>

    <div class="kartu form-kartu">
        <?php foreach ($error as $er): ?>
            <div class="alert alert-error"><?= e($er) ?></div>
        <?php endforeach; ?>

        <?php if ($tolak): ?>
            <div class="alert alert-peringatan">Pembayaran sebelumnya ditolak: <?= e($tolak['catatan_admin']) ?>. Silakan upload bukti yang benar.</div>
        <?php endif; ?>

        <h3><?= kode_tagihan($t['id_tagihan']) ?></h3>
        <table class="tabel-detail">
            <tr><td>Layanan</td><td><?= e($t['nama_layanan']) ?></td></tr>
            <tr><td>Total yang harus dibayar</td><td><b><?= rupiah($t['total']) ?></b></td></tr>
            <tr><td>Jatuh Tempo</td><td><?= tgl($t['jatuh_tempo']) ?></td></tr>
        </table>

        <form method="POST" enctype="multipart/form-data">
            <h3>Metode Pembayaran</h3>
            <label class="pilihan">
                <input type="radio" name="metode" value="transfer" <?= $metode == 'transfer' ? 'checked' : '' ?> required>
                Transfer Bank: <b><?= e($info_bayar['bank']) ?> <?= e($info_bayar['no_rek']) ?></b> a.n. <?= e($info_bayar['atas_nama']) ?>
            </label>
            <label class="pilihan">
                <input type="radio" name="metode" value="qris" <?= $metode == 'qris' ? 'checked' : '' ?>>
                QRIS: scan QRIS yang ada di kasir LaundryPro
            </label>

            <h3>Upload Bukti Pembayaran</h3>
            <input type="file" name="bukti" accept="image/jpeg,image/png" required>
            <small>Format JPG atau PNG, maksimal 2 MB.</small>

            <div class="tombol-form">
                <a href="index.php" class="btn btn-abu">Batal</a>
                <button type="submit" class="btn btn-hijau">Kirim Bukti</button>
            </div>
        </form>
    </div>
</main>
</body>
</html>
