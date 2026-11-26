<?php
// UNIT TEST LaundryPro
// Menguji fungsi-fungsi di includes/fungsi.php dan admin/aset/validasi.php satu per satu (tanpa database).
// Cara menjalankan: buka browser ke localhost/laundrypro/tests/unit_test.php
//                   atau lewat terminal: php tests/unit_test.php

date_default_timezone_set('Asia/Jakarta');
require __DIR__ . '/../includes/fungsi.php';
require __DIR__ . '/../admin/aset/validasi.php';

$hasil = [];
function uji($id, $fungsi, $deskripsi, $input, $harapan, $aktual)
{
    global $hasil;
    $hasil[] = [$id, $fungsi, $deskripsi, $input, var_export($harapan, true), var_export($aktual, true), $harapan === $aktual];
}

// ---------- hitung_tagihan() ----------
$h = hitung_tagihan(4.5, 3000, '2026-12-01 10:00:00', 72);
uji('UT-01', 'hitung_tagihan', 'Total layanan Reguler', '4,5 kg x Rp3.000', 13500, $h['total']);
uji('UT-02', 'hitung_tagihan', 'Jatuh tempo Reguler (72 jam)', 'mulai 01/12 10:00', '2026-12-04 10:00:00', $h['jatuh_tempo']);
$h = hitung_tagihan(2.3, 5000, '2026-12-01 10:00:00', 24);
uji('UT-03', 'hitung_tagihan', 'Total layanan Express', '2,3 kg x Rp5.000', 11500, $h['total']);
uji('UT-04', 'hitung_tagihan', 'Jatuh tempo Express (24 jam)', 'mulai 01/12 10:00', '2026-12-02 10:00:00', $h['jatuh_tempo']);
$h = hitung_tagihan(3, 3000, '2026-12-30 20:00:00', 72);
uji('UT-05', 'hitung_tagihan', 'Jatuh tempo melewati pergantian tahun', 'mulai 30/12 20:00, 72 jam', '2027-01-02 20:00:00', $h['jatuh_tempo']);
$h = hitung_tagihan(2.345, 3000, '2026-12-01 10:00:00', 72);
uji('UT-06', 'hitung_tagihan', 'Berat 3 desimal dibulatkan dulu ke 2 desimal', '2,345 kg x Rp3.000', 7050, $h['total']);
$h = hitung_tagihan(0.5, 3000, '2026-12-01 10:00:00', 72);
uji('UT-07', 'hitung_tagihan', 'Berat di bawah 1 kg', '0,5 kg x Rp3.000', 1500, $h['total']);

// ---------- cek_pengingat() ----------
$now = strtotime('2026-12-01 10:00:00');
uji('UT-08', 'cek_pengingat', 'Belum bayar, sudah lewat 1 menit', 'tempo 01/12 09:59', 'lewat', cek_pengingat('2026-12-01 09:59:00', 'belum_bayar', $now));
uji('UT-09', 'cek_pengingat', 'Belum bayar, jatuh tempo tepat 24 jam lagi', 'tempo 02/12 10:00', 'segera', cek_pengingat('2026-12-02 10:00:00', 'belum_bayar', $now));
uji('UT-10', 'cek_pengingat', 'Belum bayar, jatuh tempo lebih dari 24 jam', 'tempo 02/12 10:01', null, cek_pengingat('2026-12-02 10:01:00', 'belum_bayar', $now));
uji('UT-11', 'cek_pengingat', 'Ditolak dan hampir jatuh tempo', 'tempo 01/12 20:00', 'segera', cek_pengingat('2026-12-01 20:00:00', 'ditolak', $now));
uji('UT-12', 'cek_pengingat', 'Sudah lunas tidak diingatkan', 'tempo 30/11, lunas', null, cek_pengingat('2026-11-30 10:00:00', 'lunas', $now));
uji('UT-13', 'cek_pengingat', 'Menunggu validasi tidak diingatkan', 'tempo 30/11, menunggu', null, cek_pengingat('2026-11-30 10:00:00', 'menunggu_validasi', $now));

// ---------- fungsi bantu ----------
uji('UT-14', 'rupiah', 'Format angka ke rupiah', '13500', 'Rp 13.500', rupiah(13500));
uji('UT-15', 'kode_tagihan', 'Format kode tagihan', '7', 'TG-0007', kode_tagihan(7));
uji('UT-16', 'e', 'Mencegah script (XSS) di nama', '<script>', '&lt;script&gt;', e('<script>'));

// ---------- validasi_aset() ----------
$aset_benar = ['nama_aset' => 'Mesin Cuci', 'kategori' => 'Mesin Cuci', 'tanggal_perolehan' => '2025-06-10',
               'nilai_perolehan' => '3500000', 'kondisi' => 'baik', 'status' => 'dipakai'];
uji('UT-17', 'validasi_aset', 'Data aset benar', 'data lengkap', [], validasi_aset($aset_benar));
uji('UT-18', 'validasi_aset', 'Tanggal di masa depan ditolak', '2099-01-01', ['Tanggal perolehan tidak boleh di masa depan.'],
    validasi_aset(['tanggal_perolehan' => '2099-01-01'] + $aset_benar));
uji('UT-19', 'validasi_aset', 'Tanggal tidak ada (31 Feb) ditolak', '2026-02-31', ['Tanggal perolehan tidak valid.'],
    validasi_aset(['tanggal_perolehan' => '2026-02-31'] + $aset_benar));
uji('UT-20', 'validasi_aset', 'Nilai minus ditolak', '-5', ['Nilai perolehan harus angka bulat lebih dari 0.'],
    validasi_aset(['nilai_perolehan' => '-5'] + $aset_benar));
uji('UT-21', 'validasi_aset', 'Nilai terlalu besar ditolak', '3000000000', ['Nilai perolehan maksimal Rp 2.000.000.000.'],
    validasi_aset(['nilai_perolehan' => '3000000000'] + $aset_benar));

// ---------- tampilkan hasil ----------
$lulus = count(array_filter($hasil, fn($r) => $r[6]));
$total = count($hasil);

if (PHP_SAPI === 'cli') {
    foreach ($hasil as $r) {
        printf("%-6s %-15s %-48s %s\n", $r[0], $r[1], $r[2], $r[6] ? 'LULUS' : "GAGAL (harapan {$r[4]}, hasil {$r[5]})");
    }
    echo "\nTotal: $lulus dari $total lulus (" . round($lulus / $total * 100) . "%)\n";
    exit($lulus === $total ? 0 : 1);
}
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Unit Test LaundryPro</title>
<style>body{font-family:Arial;padding:20px}table{border-collapse:collapse;font-size:13px}td,th{border:1px solid #999;padding:6px}th{background:#eee}.l{color:green;font-weight:bold}.g{color:red;font-weight:bold}</style></head><body>
<h2>Unit Test LaundryPro</h2>
<p>Hasil: <b><?= $lulus ?> dari <?= $total ?> lulus (<?= round($lulus / $total * 100) ?>%)</b> &middot; dijalankan <?= date('d/m/Y H:i') ?></p>
<table><tr><th>ID</th><th>Fungsi</th><th>Deskripsi</th><th>Input</th><th>Harapan</th><th>Hasil</th><th>Status</th></tr>
<?php foreach ($hasil as $r): ?>
<tr><td><?= $r[0] ?></td><td><?= $r[1] ?></td><td><?= e($r[2]) ?></td><td><?= e($r[3]) ?></td><td><?= e($r[4]) ?></td><td><?= e($r[5]) ?></td>
<td class="<?= $r[6] ? 'l' : 'g' ?>"><?= $r[6] ? 'LULUS' : 'GAGAL' ?></td></tr>
<?php endforeach; ?></table></body></html>
