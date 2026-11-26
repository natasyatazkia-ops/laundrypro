<?php
// Halaman awal: arahkan sesuai status login
session_start();

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
} elseif ($_SESSION['role'] === 'admin') {
    header("Location: admin/dashboard.php");
} else {
    header("Location: user/dashboard.php");
}
exit;
