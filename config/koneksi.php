<?php
// Koneksi ke database MySQL (setting default XAMPP)
$host = "localhost";
$user = "root";
$pass = "";
$db   = "laundrypro";

try {
    $koneksi = mysqli_connect($host, $user, $pass, $db);
} catch (mysqli_sql_exception $e) {
    die("Koneksi database gagal. Pastikan MySQL di XAMPP sudah Start dan database 'laundrypro' sudah diimport.");
}

date_default_timezone_set("Asia/Jakarta");
mysqli_query($koneksi, "SET time_zone = '+07:00'");   // samakan jam MySQL dengan PHP (WIB)
