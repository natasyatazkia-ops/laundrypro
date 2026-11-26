<?php
require '../../includes/auth.php';
cek_login('admin');
require_once __DIR__ . '/../../config/koneksi.php';
$aktif = 'tagihan';

// Hanya pelanggan aktif yang bisa dibuatkan tagihan
$pelanggan = mysqli_query($koneksi, "
    SELECT p.id_pelanggan, p.nama, l.id_layanan, l.nama_layanan, l.tarif_per_kg, l.durasi_jam
    FROM pelanggan p JOIN layanan l ON l.id_layanan = p.id_layanan
    WHERE p.status = 'aktif' ORDER BY p.nama");

$error = [];
$isi = [
    'id_pelanggan' => $_POST['id_pelanggan'] ?? '',
    'berat'        => $_POST['berat'] ?? '',
    'waktu_mulai'  => $_POST['waktu_mulai'] ?? date('Y-m-d\TH:i'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $berat = str_replace(',', '.', trim($isi['berat']));   // terima koma atau titik
    $waktu = strtotime($isi['waktu_mulai']);

    if (!is_numeric($berat) || $berat <= 0) {
        $error[] = "Berat harus berupa angka lebih dari 0.";
    } elseif ($berat > 100) {
        $error[] = "Berat maksimal 100 kg per tagihan.";
    }
    if (!$waktu) $error[] = "Waktu mulai layanan tidak valid.";

    // Ambil data pelanggan + layanannya
    $stmt = mysqli_prepare($koneksi, "
        SELECT p.id_pelanggan, p.nama, l.id_layanan, l.tarif_per_kg, l.durasi_jam
        FROM pelanggan p JOIN layanan l ON l.id_layanan = p.id_layanan
        WHERE p.id_pelanggan = ? AND p.status = 'aktif'");
    $idp = (int)$isi['id_pelanggan'];
    mysqli_stmt_bind_param($stmt, "i", $idp);
    mysqli_stmt_execute($stmt);
    $p = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$p) $error[] = "Pilih pelanggan yang aktif.";

    if (!$error) {
        $mulai = date('Y-m-d H:i:s', $waktu);
        $hasil = hitung_tagihan((float)$berat, $p['tarif_per_kg'], $mulai, $p['durasi_jam']);

        $stmt = mysqli_prepare($koneksi, "
            INSERT INTO tagihan (id_pelanggan, id_layanan, berat_kg, tarif_per_kg, total, waktu_mulai, jatuh_tempo)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $berat_f = round((float)$berat, 2);
        mysqli_stmt_bind_param($stmt, "iidiiss", $p['id_pelanggan'], $p['id_layanan'], $berat_f,
            $p['tarif_per_kg'], $hasil['total'], $mulai, $hasil['jatuh_tempo']);
        mysqli_stmt_execute($stmt);

        set_pesan('sukses', "Tagihan untuk {$p['nama']} berhasil dibuat: " . rupiah($hasil['total']) .
            ", jatuh tempo " . tgl($hasil['jatuh_tempo']) . ".");
        header("Location: index.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Tagihan - LaundryPro</title>
    <link rel="stylesheet" href="/laundrypro/assets/style.css">
</head>
<body>
<?php include '../sidebar.php'; ?>
<main class="konten">
    <div class="topbar">
        <h2>Buat Tagihan</h2>
        <span><?= e($_SESSION['nama']) ?></span>
    </div>

    <div class="kartu form-kartu">
        <?php foreach ($error as $er): ?>
            <div class="alert alert-error"><?= e($er) ?></div>
        <?php endforeach; ?>

        <form method="POST">
            <label>Pelanggan</label>
            <select name="id_pelanggan" id="pelanggan" required>
                <option value="">-- Pilih pelanggan --</option>
                <?php while ($p = mysqli_fetch_assoc($pelanggan)): ?>
                    <option value="<?= $p['id_pelanggan'] ?>"
                            data-tarif="<?= $p['tarif_per_kg'] ?>"
                            data-durasi="<?= $p['durasi_jam'] ?>"
                            <?= $isi['id_pelanggan'] == $p['id_pelanggan'] ? 'selected' : '' ?>>
                        <?= e($p['nama']) ?> (<?= e($p['nama_layanan']) ?>)
                    </option>
                <?php endwhile; ?>
            </select>

            <label>Berat Cucian (kg)</label>
            <input type="text" name="berat" id="berat" value="<?= e($isi['berat']) ?>" placeholder="contoh: 4,5" required>

            <label>Waktu Mulai Layanan</label>
            <input type="datetime-local" name="waktu_mulai" id="mulai" value="<?= e($isi['waktu_mulai']) ?>" required>

            <div class="kotak-hitung">
                <div><span>Tarif</span><b id="h-tarif">-</b></div>
                <div><span>Total Tagihan</span><b id="h-total">-</b></div>
                <div><span>Jatuh Tempo</span><b id="h-tempo">-</b></div>
            </div>
            <small>Total dan jatuh tempo dihitung otomatis oleh sistem.</small>

            <div class="tombol-form">
                <a href="index.php" class="btn btn-abu">Batal</a>
                <button type="submit" class="btn btn-utama">Simpan Tagihan</button>
            </div>
        </form>
    </div>
</main>

<script>
// Pratinjau perhitungan (perhitungan yang disimpan tetap dari server)
const pel = document.getElementById('pelanggan');
const berat = document.getElementById('berat');
const mulai = document.getElementById('mulai');
const rp = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');

function hitung() {
    const opt = pel.selectedOptions[0];
    const tarif = opt ? Number(opt.dataset.tarif) : 0;
    const durasi = opt ? Number(opt.dataset.durasi) : 0;
    const b = parseFloat(berat.value.replace(',', '.'));

    document.getElementById('h-tarif').textContent = tarif ? rp(tarif) + '/kg' : '-';
    document.getElementById('h-total').textContent = (tarif && b > 0) ? rp(b * tarif) : '-';

    if (durasi && mulai.value) {
        const t = new Date(new Date(mulai.value).getTime() + durasi * 3600 * 1000);
        const pad = x => String(x).padStart(2, '0');
        document.getElementById('h-tempo').textContent =
            `${pad(t.getDate())}/${pad(t.getMonth() + 1)}/${t.getFullYear()} ${pad(t.getHours())}:${pad(t.getMinutes())}`;
    } else {
        document.getElementById('h-tempo').textContent = '-';
    }
}
[pel, berat, mulai].forEach(el => el.addEventListener('input', hitung));
hitung();
</script>
</body>
</html>
