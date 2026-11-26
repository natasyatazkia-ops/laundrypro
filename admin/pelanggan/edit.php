<?php
require '../../includes/auth.php';
cek_login('admin');
require_once __DIR__ . '/../../config/koneksi.php';
$aktif = 'pelanggan';

$id = (int)($_GET['id'] ?? 0);

$stmt = mysqli_prepare($koneksi, "SELECT p.*, u.email FROM pelanggan p JOIN users u ON u.id_user = p.id_user WHERE p.id_pelanggan = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$isi = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$isi) {
    set_pesan('error', "Data pelanggan tidak ditemukan.");
    header("Location: index.php");
    exit;
}

$layanan = mysqli_query($koneksi, "SELECT * FROM layanan ORDER BY nama_layanan");
$error = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['nama', 'no_hp', 'alamat', 'email', 'id_layanan', 'status'] as $k) {
        $isi[$k] = trim($_POST[$k] ?? '');
    }
    $password = $_POST['password'] ?? '';

    if ($isi['nama'] === '')  $error[] = "Nama wajib diisi.";
    if (!preg_match('/^[0-9]{10,15}$/', $isi['no_hp'])) $error[] = "No. HP harus angka, 10-15 digit.";
    if (!filter_var($isi['email'], FILTER_VALIDATE_EMAIL)) $error[] = "Format email tidak valid.";
    if ($password !== '' && strlen($password) < 6) $error[] = "Password baru minimal 6 karakter.";
    if (!in_array($isi['status'], ['aktif', 'nonaktif'])) $error[] = "Status tidak valid.";
    if ($isi['id_layanan'] === '') $error[] = "Layanan wajib dipilih.";

    // Email tidak boleh sama dengan akun lain
    if (!$error) {
        $cek = mysqli_prepare($koneksi, "SELECT id_user FROM users WHERE email = ? AND id_user != ?");
        mysqli_stmt_bind_param($cek, "si", $isi['email'], $isi['id_user']);
        mysqli_stmt_execute($cek);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($cek))) {
            $error[] = "Email sudah dipakai akun lain.";
        }
    }

    if (!$error) {
        mysqli_begin_transaction($koneksi);
        try {
            $stmt = mysqli_prepare($koneksi, "UPDATE pelanggan SET nama=?, no_hp=?, alamat=?, id_layanan=?, status=? WHERE id_pelanggan=?");
            mysqli_stmt_bind_param($stmt, "sssisi", $isi['nama'], $isi['no_hp'], $isi['alamat'], $isi['id_layanan'], $isi['status'], $id);
            mysqli_stmt_execute($stmt);

            // Password hanya diganti kalau diisi
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($koneksi, "UPDATE users SET email=?, password=? WHERE id_user=?");
                mysqli_stmt_bind_param($stmt, "ssi", $isi['email'], $hash, $isi['id_user']);
            } else {
                $stmt = mysqli_prepare($koneksi, "UPDATE users SET email=? WHERE id_user=?");
                mysqli_stmt_bind_param($stmt, "si", $isi['email'], $isi['id_user']);
            }
            mysqli_stmt_execute($stmt);

            mysqli_commit($koneksi);
            set_pesan('sukses', "Data pelanggan {$isi['nama']} berhasil diubah.");
            header("Location: index.php");
            exit;
        } catch (mysqli_sql_exception $ex) {
            mysqli_rollback($koneksi);
            $error[] = "Gagal menyimpan perubahan. Coba lagi.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Pelanggan - LaundryPro</title>
    <link rel="stylesheet" href="/laundrypro/assets/style.css">
</head>
<body>
<?php include '../sidebar.php'; ?>
<main class="konten">
    <div class="topbar">
        <h2>Ubah Pelanggan</h2>
        <span><?= e($_SESSION['nama']) ?></span>
    </div>

    <div class="kartu form-kartu">
        <?php foreach ($error as $er): ?>
            <div class="alert alert-error"><?= e($er) ?></div>
        <?php endforeach; ?>

        <form method="POST">
            <h3>Data Pelanggan</h3>
            <label>Nama</label>
            <input type="text" name="nama" value="<?= e($isi['nama']) ?>" required>

            <label>No. HP</label>
            <input type="text" name="no_hp" value="<?= e($isi['no_hp']) ?>" required>

            <label>Alamat</label>
            <textarea name="alamat" rows="2"><?= e($isi['alamat']) ?></textarea>

            <label>Jenis Layanan</label>
            <select name="id_layanan" required>
                <?php while ($l = mysqli_fetch_assoc($layanan)): ?>
                    <option value="<?= $l['id_layanan'] ?>" <?= $isi['id_layanan'] == $l['id_layanan'] ? 'selected' : '' ?>>
                        <?= e($l['nama_layanan']) ?> (<?= rupiah($l['tarif_per_kg']) ?>/kg, <?= $l['durasi_jam'] ?> jam)
                    </option>
                <?php endwhile; ?>
            </select>

            <label>Status</label>
            <select name="status">
                <option value="aktif" <?= $isi['status'] == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                <option value="nonaktif" <?= $isi['status'] == 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
            </select>
            <small>Pelanggan nonaktif tidak bisa login.</small>

            <h3>Akun Login</h3>
            <label>Email</label>
            <input type="email" name="email" value="<?= e($isi['email']) ?>" required>

            <label>Password Baru</label>
            <input type="password" name="password" minlength="6">
            <small>Kosongkan kalau tidak ingin mengganti password.</small>

            <div class="tombol-form">
                <a href="index.php" class="btn btn-abu">Batal</a>
                <button type="submit" class="btn btn-utama">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</main>
</body>
</html>
