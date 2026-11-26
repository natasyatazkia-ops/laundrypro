<?php
require '../../includes/auth.php';
cek_login('admin');
require_once __DIR__ . '/../../config/koneksi.php';
$aktif = 'pelanggan';

// Ringkasan jumlah pelanggan
$ringkas = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT COUNT(*) AS total,
           SUM(status = 'aktif') AS aktif,
           SUM(status = 'nonaktif') AS nonaktif
    FROM pelanggan"));

// Pencarian dan filter status
$cari   = trim($_GET['cari'] ?? '');
$status = $_GET['status'] ?? '';

$sql = "SELECT p.*, u.email, l.nama_layanan, l.tarif_per_kg, l.durasi_jam
        FROM pelanggan p
        JOIN users u ON u.id_user = p.id_user
        JOIN layanan l ON l.id_layanan = p.id_layanan
        WHERE (p.nama LIKE ? OR u.email LIKE ? OR p.no_hp LIKE ?)";
$param = ["%$cari%", "%$cari%", "%$cari%"];
$tipe  = "sss";

if ($status === 'aktif' || $status === 'nonaktif') {
    $sql .= " AND p.status = ?";
    $param[] = $status;
    $tipe   .= "s";
}
$sql .= " ORDER BY p.nama";

$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, $tipe, ...$param);
mysqli_stmt_execute($stmt);
$data = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pelanggan - LaundryPro</title>
    <link rel="stylesheet" href="/laundrypro/assets/style.css">
</head>
<body>
<?php include '../sidebar.php'; ?>
<main class="konten">
    <div class="topbar">
        <h2>Pelanggan</h2>
        <span><?= e($_SESSION['nama']) ?></span>
    </div>

    <h1 class="judul">Data Pelanggan</h1>
    <p class="keterangan">Kelola data pelanggan tetap LaundryPro.</p>

    <?php tampil_pesan(); ?>

    <div class="baris-statistik">
        <div class="kartu stat"><span>Total Pelanggan</span><b><?= (int)$ringkas['total'] ?></b></div>
        <div class="kartu stat"><span>Pelanggan Aktif</span><b><?= (int)$ringkas['aktif'] ?></b></div>
        <div class="kartu stat"><span>Pelanggan Nonaktif</span><b><?= (int)$ringkas['nonaktif'] ?></b></div>
    </div>

    <div class="kartu">
        <div class="kepala-kartu">
            <h3>Daftar Pelanggan</h3>
            <a href="tambah.php" class="btn btn-utama">+ Tambah Pelanggan</a>
        </div>

        <form method="GET" class="form-cari">
            <input type="text" name="cari" placeholder="Cari nama, email, no. HP..." value="<?= e($cari) ?>">
            <select name="status">
                <option value="">Semua Status</option>
                <option value="aktif" <?= $status == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                <option value="nonaktif" <?= $status == 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
            </select>
            <button type="submit" class="btn btn-utama">Cari</button>
        </form>

        <div class="tabel-wrap">
        <table class="tabel">
            <thead>
                <tr>
                    <th>Nama</th><th>No. HP</th><th>Email</th><th>Layanan</th>
                    <th>Tarif</th><th>Durasi</th><th>Status</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($data) == 0): ?>
                <tr><td colspan="8" class="kosong">Data pelanggan tidak ditemukan.</td></tr>
            <?php endif; ?>
            <?php while ($p = mysqli_fetch_assoc($data)): ?>
                <tr>
                    <td><b><?= e($p['nama']) ?></b></td>
                    <td><?= e($p['no_hp']) ?></td>
                    <td><?= e($p['email']) ?></td>
                    <td><span class="label label-biru"><?= e($p['nama_layanan']) ?></span></td>
                    <td><?= rupiah($p['tarif_per_kg']) ?>/kg</td>
                    <td><?= (int)$p['durasi_jam'] ?> jam</td>
                    <td><span class="label <?= $p['status'] == 'aktif' ? 'label-hijau' : 'label-abu' ?>"><?= ucfirst($p['status']) ?></span></td>
                    <td class="aksi">
                        <a href="edit.php?id=<?= $p['id_pelanggan'] ?>" class="btn btn-kuning btn-kecil">Ubah</a>
                        <form action="hapus.php" method="POST" onsubmit="return confirm(<?= e(json_encode('Yakin hapus pelanggan ' . $p['nama'] . '?')) ?>);">
                            <input type="hidden" name="id" value="<?= $p['id_pelanggan'] ?>">
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
