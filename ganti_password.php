<?php
require 'includes/auth.php';
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}
require_once __DIR__ . '/config/koneksi.php';

$peran = $_SESSION['role'];
$aktif = 'password';
$error = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lama  = $_POST['lama'] ?? '';
    $baru  = $_POST['baru'] ?? '';
    $ulang = $_POST['ulang'] ?? '';

    $stmt = mysqli_prepare($koneksi, "SELECT password FROM users WHERE id_user = ?");
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['id_user']);
    mysqli_stmt_execute($stmt);
    $u = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!password_verify($lama, $u['password'])) $error[] = "Password lama salah.";
    if (strlen($baru) < 6) $error[] = "Password baru minimal 6 karakter.";
    if ($baru !== $ulang) $error[] = "Konfirmasi password baru tidak sama.";
    if (!$error && $baru === $lama) $error[] = "Password baru tidak boleh sama dengan password lama.";

    if (!$error) {
        $hash = password_hash($baru, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($koneksi, "UPDATE users SET password = ? WHERE id_user = ?");
        mysqli_stmt_bind_param($stmt, "si", $hash, $_SESSION['id_user']);
        mysqli_stmt_execute($stmt);
        set_pesan('sukses', "Password berhasil diganti.");
        header("Location: ganti_password.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password - LaundryPro</title>
    <link rel="stylesheet" href="/laundrypro/assets/style.css">
</head>
<body>
<?php include ($peran === 'admin' ? 'admin' : 'user') . '/sidebar.php'; ?>
<main class="konten">
    <div class="topbar"><h2>Ganti Password</h2><span><?= e($_SESSION['nama']) ?></span></div>
    <div class="kartu form-kartu">
        <?php tampil_pesan(); ?>
        <?php foreach ($error as $er): ?><div class="alert alert-error"><?= e($er) ?></div><?php endforeach; ?>
        <form method="POST">
            <label>Password Lama</label>
            <input type="password" name="lama" required>
            <label>Password Baru</label>
            <input type="password" name="baru" minlength="6" required>
            <label>Ulangi Password Baru</label>
            <input type="password" name="ulang" minlength="6" required>
            <div class="tombol-form">
                <button type="submit" class="btn btn-utama">Simpan</button>
            </div>
        </form>
    </div>
</main>
</body>
</html>
