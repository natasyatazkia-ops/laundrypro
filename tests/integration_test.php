<?php
// INTEGRATION TEST LaundryPro
// Menguji hubungan antar modul (login, pelanggan, tagihan, pembayaran, laporan, pengingat)
// dengan cara menjalankan alur lengkap lewat HTTP seperti pengguna sungguhan.
//
// Syarat: XAMPP (Apache + MySQL) jalan dan akun admin@laundrypro.com / admin123 masih ada.
// Cara menjalankan (terminal di folder laundrypro):  php tests/integration_test.php
// Data uji dibuat sementara dan dihapus lagi di akhir.

date_default_timezone_set('Asia/Jakarta');
$BASE = $argv[1] ?? 'http://localhost/laundrypro';
require __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/fungsi.php';

$tmp = sys_get_temp_dir();
$kode = substr(md5(uniqid()), 0, 6);
$emailA = "uji_a_$kode@test.com";
$emailB = "uji_b_$kode@test.com";
$hasil = [];

// ---------- alat bantu ----------
function jar($nama) { global $tmp, $kode; return "$tmp/lp_{$nama}_$kode.txt"; }
function req($jar, $url, $post = null)
{
    global $BASE;
    $ch = curl_init($BASE . $url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => jar($jar),
        CURLOPT_COOKIEFILE => jar($jar), CURLOPT_FOLLOWLOCATION => true]);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }
    $html = curl_exec($ch);
    $akhir = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    return ['html' => (string)$html, 'url' => $akhir];
}
function q($sql) { global $koneksi; return mysqli_fetch_assoc(mysqli_query($koneksi, $sql)); }
function uji($id, $modul, $deskripsi, $lulus, $catatan = '')
{
    global $hasil;
    $hasil[] = [$id, $modul, $deskripsi, (bool)$lulus, $catatan];
}

// gambar PNG kecil untuk bukti bayar
$png = "$tmp/lp_bukti_$kode.png";
file_put_contents($png, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADElEQVR4nGNgYPgPAAEDAQAIicLsAAAAAElFTkSuQmCC'));

try {
    // IT-01
    $r = req('admin', '/proses_login.php', ['email' => 'admin@laundrypro.com', 'password' => 'admin123']);
    uji('IT-01', 'Login → Auth → Dashboard admin', 'Admin login lalu diarahkan ke dashboard admin',
        str_contains($r['url'], '/admin/dashboard.php'));

    // IT-02 tambah 2 pelanggan
    $r1 = req('admin', '/admin/pelanggan/tambah.php', ['nama' => "Uji A $kode", 'no_hp' => '081200000001', 'alamat' => '-',
        'email' => $emailA, 'password' => 'ujiA123', 'id_layanan' => '1']);
    req('admin', '/admin/pelanggan/tambah.php', ['nama' => "Uji B $kode", 'no_hp' => '081200000002', 'alamat' => '-',
        'email' => $emailB, 'password' => 'ujiB123', 'id_layanan' => '2']);
    $pA = q("SELECT p.id_pelanggan FROM pelanggan p JOIN users u USING(id_user) WHERE u.email = '$emailA'");
    $pB = q("SELECT p.id_pelanggan FROM pelanggan p JOIN users u USING(id_user) WHERE u.email = '$emailB'");
    $r = req('A', '/proses_login.php', ['email' => $emailA, 'password' => 'ujiA123']);
    uji('IT-02', 'Pelanggan → Users → Login', 'Pelanggan yang dibuat admin bisa langsung login',
        $pA && str_contains($r['url'], '/user/dashboard.php'));

    // IT-03
    $r = req('A', '/admin/dashboard.php');
    uji('IT-03', 'Auth antar peran', 'Pelanggan membuka halaman admin dikembalikan ke dashboard pelanggan',
        str_contains($r['url'], '/user/dashboard.php'));

    // IT-04 & IT-05 buat tagihan
    req('admin', '/admin/tagihan/tambah.php', ['id_pelanggan' => $pA['id_pelanggan'], 'berat' => '4,5', 'waktu_mulai' => '2026-12-01T10:00']);
    $t = q("SELECT * FROM tagihan WHERE id_pelanggan = {$pA['id_pelanggan']} ORDER BY id_tagihan DESC LIMIT 1");
    $r = req('A', '/user/tagihan/index.php');
    uji('IT-04', 'Tagihan (admin) → Tagihan (pelanggan)', 'Tagihan yang dibuat admin muncul di halaman Tagihan Saya',
        $t && str_contains($r['html'], kode_tagihan($t['id_tagihan'])));
    uji('IT-05', 'Tagihan → Layanan', 'Total & jatuh tempo memakai tarif dan durasi layanan pelanggan (Reguler)',
        $t && $t['total'] == 13500 && $t['jatuh_tempo'] == '2026-12-04 10:00:00' && $t['status'] == 'belum_bayar',
        $t ? "total {$t['total']}, jatuh tempo {$t['jatuh_tempo']}" : '');
    $idT = $t['id_tagihan'];

    // IT-06 bayar
    req('A', "/user/pembayaran/bayar.php?id=$idT", ['metode' => 'transfer', 'bukti' => new CURLFile($png, 'image/png', 'bukti.png')]);
    $st = q("SELECT status FROM tagihan WHERE id_tagihan = $idT")['status'];
    $r = req('admin', '/admin/pembayaran/index.php');
    uji('IT-06', 'Pembayaran → Tagihan → Admin', 'Setelah upload bukti, tagihan jadi Menunggu Validasi dan muncul di menu Pembayaran admin',
        $st == 'menunggu_validasi' && str_contains($r['html'], kode_tagihan($idT)), "status: $st");

    // IT-07 tolak
    $b = q("SELECT id_pembayaran FROM pembayaran WHERE id_tagihan = $idT ORDER BY id_pembayaran DESC LIMIT 1");
    req('admin', "/admin/pembayaran/verifikasi.php?id={$b['id_pembayaran']}", ['id' => $b['id_pembayaran'], 'aksi' => 'tolak', 'catatan' => 'Nominal kurang']);
    $st = q("SELECT status FROM tagihan WHERE id_tagihan = $idT")['status'];
    $r = req('A', '/user/pembayaran/riwayat.php');
    uji('IT-07', 'Verifikasi → Tagihan → Riwayat', 'Pembayaran ditolak: tagihan jadi Ditolak dan alasannya tampil di riwayat pelanggan',
        $st == 'ditolak' && str_contains($r['html'], 'Nominal kurang'), "status: $st");

    // IT-08 upload ulang + terima
    req('A', "/user/pembayaran/bayar.php?id=$idT", ['metode' => 'qris', 'bukti' => new CURLFile($png, 'image/png', 'bukti.png')]);
    $b = q("SELECT id_pembayaran FROM pembayaran WHERE id_tagihan = $idT AND status = 'menunggu'");
    req('admin', "/admin/pembayaran/verifikasi.php?id={$b['id_pembayaran']}", ['id' => $b['id_pembayaran'], 'aksi' => 'terima']);
    $st = q("SELECT status FROM tagihan WHERE id_tagihan = $idT")['status'];
    uji('IT-08', 'Upload ulang → Verifikasi → Tagihan', 'Pelanggan upload ulang, admin terima, tagihan jadi Lunas', $st == 'lunas', "status: $st");

    // IT-09 laporan
    $r = req('admin', '/admin/laporan/index.php?dari=' . date('Y-m-01') . '&sampai=' . date('Y-m-t'));
    uji('IT-09', 'Pembayaran → Laporan', 'Pembayaran yang diterima masuk ke tabel Pembayaran Diterima di laporan',
        str_contains($r['html'], kode_tagihan($idT)));

    // IT-10 pengingat
    req('admin', '/admin/tagihan/tambah.php', ['id_pelanggan' => $pA['id_pelanggan'], 'berat' => '2', 'waktu_mulai' => '2026-01-01T08:00']);
    $t2 = q("SELECT id_tagihan FROM tagihan WHERE id_pelanggan = {$pA['id_pelanggan']} ORDER BY id_tagihan DESC LIMIT 1");
    $r = req('A', '/user/dashboard.php');
    uji('IT-10', 'Tagihan → Dashboard (pengingat)', 'Tagihan yang lewat jatuh tempo memunculkan pengingat di dashboard pelanggan',
        str_contains($r['html'], kode_tagihan($t2['id_tagihan']) . ' sudah lewat jatuh tempo'));

    // IT-11 akses tagihan pelanggan lain
    req('B', '/proses_login.php', ['email' => $emailB, 'password' => 'ujiB123']);
    $r = req('B', "/user/tagihan/detail.php?id=$idT");
    $r2 = req('B', "/user/pembayaran/bayar.php?id={$t2['id_tagihan']}");
    uji('IT-11', 'Auth → Tagihan', 'Pelanggan B tidak bisa membuka detail atau membayar tagihan milik pelanggan A',
        str_contains($r['html'], 'Tagihan tidak ditemukan') && !str_contains($r2['html'], 'Kirim Bukti'));

    // IT-12 hapus pelanggan yang punya tagihan
    $r = req('admin', '/admin/pelanggan/hapus.php', ['id' => $pA['id_pelanggan']]);
    $masih = q("SELECT id_pelanggan FROM pelanggan WHERE id_pelanggan = {$pA['id_pelanggan']}");
    uji('IT-12', 'Pelanggan → Tagihan', 'Pelanggan yang sudah punya tagihan tidak bisa dihapus', $masih && str_contains($r['html'], 'tidak bisa dihapus'));

    // IT-13 & IT-14 nonaktifkan pelanggan B
    mysqli_query($koneksi, "UPDATE pelanggan SET status = 'nonaktif' WHERE id_pelanggan = {$pB['id_pelanggan']}");
    $r = req('B', '/user/dashboard.php');
    uji('IT-13', 'Pelanggan → Auth (sesi)', 'Pelanggan yang dinonaktifkan saat masih login langsung dikeluarkan',
        str_contains($r['url'], '/login.php'));
    $r = req('admin', '/admin/tagihan/tambah.php');
    uji('IT-14', 'Pelanggan → Form Tagihan', 'Pelanggan nonaktif tidak muncul di pilihan form Buat Tagihan',
        !str_contains($r['html'], "Uji B $kode"));

} finally {
    // ---------- bersihkan data uji ----------
    $ids = [];
    $q = mysqli_query($koneksi, "SELECT p.id_pelanggan, u.id_user FROM pelanggan p JOIN users u USING(id_user) WHERE u.email IN ('$emailA','$emailB')");
    while ($row = mysqli_fetch_assoc($q)) $ids[] = $row;
    foreach ($ids as $row) {
        $f = mysqli_query($koneksi, "SELECT b.bukti FROM pembayaran b JOIN tagihan t USING(id_tagihan) WHERE t.id_pelanggan = {$row['id_pelanggan']}");
        while ($x = mysqli_fetch_assoc($f)) @unlink(__DIR__ . '/../uploads/bukti_bayar/' . $x['bukti']);
        mysqli_query($koneksi, "DELETE b FROM pembayaran b JOIN tagihan t USING(id_tagihan) WHERE t.id_pelanggan = {$row['id_pelanggan']}");
        mysqli_query($koneksi, "DELETE FROM tagihan WHERE id_pelanggan = {$row['id_pelanggan']}");
        mysqli_query($koneksi, "DELETE FROM users WHERE id_user = {$row['id_user']}");
    }
    foreach (['admin', 'A', 'B'] as $j) @unlink(jar($j));
    @unlink($png);
}

$lulus = count(array_filter($hasil, fn($r) => $r[3]));
$total = count($hasil);
foreach ($hasil as $r) {
    printf("%-6s %-38s %-6s %s\n", $r[0], $r[1], $r[3] ? 'LULUS' : 'GAGAL', $r[2] . ($r[4] ? " ({$r[4]})" : ''));
}
echo "\nTotal: $lulus dari $total lulus (" . ($total ? round($lulus / $total * 100) : 0) . "%)\n";
