<?php
require '../../includes/auth.php';
cek_login('admin');
require_once __DIR__ . '/../../config/koneksi.php';
$aktif = 'pelanggan';

$layanan = mysqli_query($koneksi, "SELECT * FROM layanan ORDER BY nama_layanan");
$error = [];
$isi = ['nama' => '', 'no_hp' => '', 'alamat' => '', 'email' => '', 'id_layanan' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($isi as $k => $v) {
        $isi[$k] = trim($_POST[$k] ?? '');
    }
    $password = $_POST['password'] ?? '';

    // Validasi
    if ($isi['nama'] === '')  $error[] = "Nama wajib diisi.";
    if (!preg_match('/^[0-9]{10,15}$/', $isi['no_hp'])) $error[] = "No. HP harus angka, 10-15 digit.";
    if (!filter_var($isi['email'], FILTER_VALIDATE_EMAIL)) $error[] = "Format email tidak valid.";
    if (strlen($password) < 6) $error[] = "Password minimal 6 karakter.";
    if ($isi['id_layanan'] === '') $error[] = "Layanan wajib dipilih.";

    // Email tidak boleh dipakai akun lain
    if (!$error) {
        $cek = mysqli_prepare($koneksi, "SELECT id_user FROM users WHERE email = ?");
        mysqli_stmt_bind_param($cek, "s", $isi['email']);
        mysqli_stmt_execute($cek);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($cek))) {
            $error[] = "Email sudah terdaftar, gunakan email lain.";
        }
    }

    if (!$error) {
        // Simpan akun + data pelanggan sekaligus. Kalau salah satu gagal, dua-duanya dibatalkan.
        mysqli_begin_transaction($koneksi);
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($koneksi, "INSERT INTO users (email, password, role) VALUES (?, ?, 'pelanggan')");
            mysqli_stmt_bind_param($stmt, "ss", $isi['email'], $hash);
            mysqli_stmt_execute($stmt);
            $id_user = mysqli_insert_id($koneksi);

            $stmt = mysqli_prepare($koneksi, "INSERT INTO pelanggan (id_user, id_layanan, nama, no_hp, alamat) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "iisss", $id_user, $isi['id_layanan'], $isi['nama'], $isi['no_hp'], $isi['alamat']);
            mysqli_stmt_execute($stmt);

            mysqli_commit($koneksi);
            set_pesan('sukses', "Pelanggan {$isi['nama']} berhasil ditambahkan.");
            header("Location: index.php");
            exit;
        } catch (mysqli_sql_exception $ex) {
            mysqli_rollback($koneksi);
            $error[] = "Gagal menyimpan data. Coba lagi.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Pelanggan - LaundryPro</title>
    <link rel="stylesheet" href="/laundrypro/assets/style.css">
</head>
<body>
<?php include '../sidebar.php'; ?>
<main class="konten">
    <div class="topbar">
        <h2>Tambah Pelanggan</h2>
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
            <input type="text" name="no_hp" value="<?= e($isi['no_hp']) ?>" placeholder="08xxxxxxxxxx" required>

            <label>Alamat</label>
            <textarea name="alamat" rows="2"><?= e($isi['alamat']) ?></textarea>

            <label>Jenis Layanan</label>
            <select name="id_layanan" required>
                <option value="">-- Pilih layanan --</option>
                <?php while ($l = mysqli_fetch_assoc($layanan)): ?>
                    <option value="<?= $l['id_layanan'] ?>" <?= $isi['id_layanan'] == $l['id_layanan'] ? 'selected' : '' ?>>
                        <?= e($l['nama_layanan']) ?> (<?= rupiah($l['tarif_per_kg']) ?>/kg, <?= $l['durasi_jam'] ?> jam)
                    </option>
                <?php endwhile; ?>
            </select>

            <h3>Akun Login</h3>
            <label>Email</label>
            <input type="email" name="email" value="<?= e($isi['email']) ?>" required>

            <label>Password</label>
            <input type="password" name="password" minlength="6" required>
            <small>Minimal 6 karakter. Berikan ke pelanggan untuk login.</small>

            <div class="tombol-form">
                <a href="index.php" class="btn btn-abu">Batal</a>
                <button type="submit" class="btn btn-utama">Simpan</button>
            </div>
        </form>
    </div>
</main>
</body>
</html>
