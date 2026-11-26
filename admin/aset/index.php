<?php
require '../../includes/auth.php';
cek_login('admin');
require_once __DIR__ . '/../../config/koneksi.php';
$aktif = 'aset';

$ringkas = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT COUNT(*) AS jumlah, COALESCE(SUM(nilai_perolehan), 0) AS nilai,
           SUM(kondisi = 'baik') AS baik, SUM(kondisi != 'baik') AS rusak
    FROM aset"));

$cari    = trim($_GET['cari'] ?? '');
$kondisi = $_GET['kondisi'] ?? '';

$sql = "SELECT * FROM aset WHERE (nama_aset LIKE ? OR kategori LIKE ?)";
$param = ["%$cari%", "%$cari%"];
$tipe = "ss";
if (in_array($kondisi, ['baik', 'rusak_ringan', 'rusak_berat'])) {
    $sql .= " AND kondisi = ?";
    $param[] = $kondisi;
    $tipe .= "s";
}
$sql .= " ORDER BY kategori, nama_aset";
$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, $tipe, ...$param);
mysqli_stmt_execute($stmt);
$data = mysqli_stmt_get_result($stmt);

$label_kondisi = ['baik' => ['Baik', 'label-hijau'], 'rusak_ringan' => ['Rusak Ringan', 'label-kuning'], 'rusak_berat' => ['Rusak Berat', 'label-merah']];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aset Tetap - LaundryPro</title>
    <link rel="stylesheet" href="/laundrypro/assets/style.css">
</head>
<body>
<?php include '../sidebar.php'; ?>
<main class="konten">
    <div class="topbar">
        <h2>Aset Tetap</h2>
        <span><?= e($_SESSION['nama']) ?></span>
    </div>

    <h1 class="judul">Data Aset Tetap</h1>
    <p class="keterangan">Catat peralatan usaha laundry beserta nilai dan kondisinya.</p>

    <?php tampil_pesan(); ?>

    <div class="baris-statistik">
        <div class="kartu stat"><span>Jumlah Aset</span><b><?= (int)$ringkas['jumlah'] ?></b></div>
        <div class="kartu stat"><span>Total Nilai Perolehan</span><b><?= rupiah($ringkas['nilai']) ?></b></div>
        <div class="kartu stat"><span>Kondisi Baik</span><b><?= (int)$ringkas['baik'] ?></b></div>
        <div class="kartu stat"><span>Rusak</span><b class="teks-merah"><?= (int)$ringkas['rusak'] ?></b></div>
    </div>

    <div class="kartu">
        <div class="kepala-kartu">
            <h3>Daftar Aset</h3>
            <a href="tambah.php" class="btn btn-utama">+ Tambah Aset</a>
        </div>

        <form method="GET" class="form-cari">
            <input type="text" name="cari" placeholder="Cari nama atau kategori aset..." value="<?= e($cari) ?>">
            <select name="kondisi">
                <option value="">Semua Kondisi</option>
                <?php foreach ($label_kondisi as $k => $v): ?>
                    <option value="<?= $k ?>" <?= $kondisi == $k ? 'selected' : '' ?>><?= $v[0] ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-utama">Cari</button>
        </form>

        <div class="tabel-wrap">
        <table class="tabel">
            <thead><tr><th>Nama Aset</th><th>Kategori</th><th>Tgl Perolehan</th><th>Nilai</th><th>Kondisi</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if (mysqli_num_rows($data) == 0): ?>
                <tr><td colspan="7" class="kosong">Belum ada data aset.</td></tr>
            <?php endif; ?>
            <?php while ($a = mysqli_fetch_assoc($data)): ?>
                <tr>
                    <td><b><?= e($a['nama_aset']) ?></b></td>
                    <td><?= e($a['kategori']) ?></td>
                    <td><?= date('d/m/Y', strtotime($a['tanggal_perolehan'])) ?></td>
                    <td><?= rupiah($a['nilai_perolehan']) ?></td>
                    <td><span class="label <?= $label_kondisi[$a['kondisi']][1] ?>"><?= $label_kondisi[$a['kondisi']][0] ?></span></td>
                    <td><?= $a['status'] == 'dipakai' ? 'Dipakai' : 'Tidak Dipakai' ?></td>
                    <td class="aksi">
                        <a href="edit.php?id=<?= $a['id_aset'] ?>" class="btn btn-kuning btn-kecil">Ubah</a>
                        <form action="hapus.php" method="POST" onsubmit="return confirm(<?= e(json_encode('Yakin hapus aset ' . $a['nama_aset'] . '?')) ?>);">
                            <input type="hidden" name="id" value="<?= $a['id_aset'] ?>">
                            <button type="submit" class="btn btn-merah btn-kecil">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>
</main>
</body>
</html>
