<?php $aktif = $aktif ?? ''; ?>
<aside class="sidebar">
    <div class="logo">LaundryPro</div>
    <p class="menu-judul">MENU</p>
    <a href="/laundrypro/user/dashboard.php" class="<?= $aktif == 'dashboard' ? 'aktif' : '' ?>">Dashboard</a>
    <a href="/laundrypro/user/tagihan/index.php" class="<?= $aktif == 'tagihan' ? 'aktif' : '' ?>">Tagihan Saya</a>
    <a href="/laundrypro/user/pembayaran/index.php" class="<?= $aktif == 'bayar' ? 'aktif' : '' ?>">Bayar Tagihan</a>
    <a href="/laundrypro/user/pembayaran/riwayat.php" class="<?= $aktif == 'riwayat' ? 'aktif' : '' ?>">Riwayat Pembayaran</a>
    <a href="/laundrypro/ganti_password.php" class="<?= $aktif == 'password' ? 'aktif' : '' ?> menu-bawah">Ganti Password</a>
    <a href="/laundrypro/logout.php" class="logout-link">Logout</a>
</aside>
