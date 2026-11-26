<?php
session_start();

// Kalau sudah login, langsung ke dashboard
if (isset($_SESSION['id_user'])) {
    header("Location: index.php");
    exit;
}

$error = $_SESSION['error_login'] ?? '';
unset($_SESSION['error_login']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LaundryPro</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="halaman-login">
    <div class="kotak-login">
        <h1>LaundryPro</h1>
        <p class="sub">Silakan login untuk melanjutkan</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="proses_login.php" method="POST">
            <label>Email</label>
            <input type="email" name="email" required>

            <label>Password</label>
            <input type="password" name="password" required>

            <button type="submit" class="btn btn-utama">Login</button>
        </form>
    </div>
</body>
</html>
