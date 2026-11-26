<?php $aktif = $aktif ?? ''; ?>
<aside class="sidebar">
    <div class="logo">LaundryPro</div>
    <p class="menu-judul">MENU UTAMA</p>
    <a href="/laundrypro/admin/dashboard.php" class="<?= $aktif == 'dashboard' ? 'aktif' : '' ?>">Dashboard</a>
    <a href="/laundrypro/admin/pelanggan/index.php" class="<?= $aktif == 'pelanggan' ? 'aktif' : '' ?>">Pelanggan</a>
    <a href="/laundrypro/admin/tagihan/index.php" class="<?= $aktif == 'tagihan' ? 'aktif' : '' ?>">Tagihan</a>
    <a href="/laundrypro/admin/pembayaran/index.php" class="<?= $aktif == 'pembayaran' ? 'aktif' : '' ?>">Pembayaran</a>
    <a href="/laundrypro/admin/aset/index.php" class="<?= $aktif == 'aset' ? 'aktif' : '' ?>">Aset Tetap</a>
    <a href="/laundrypro/admin/laporan/index.php" class="<?= $aktif == 'laporan' ? 'aktif' : '' ?>">Laporan</a>
    <a href="/laundrypro/ganti_password.php" class="<?= $aktif == 'password' ? 'aktif' : '' ?> menu-bawah">Ganti Password</a>
    <a href="/laundrypro/logout.php" class="logout-link">Logout</a>
</aside>
