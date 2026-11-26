<?php
// Bagian form yang dipakai bersama oleh tambah.php dan edit.php
$kategori_umum = ['Mesin Cuci', 'Mesin Pengering', 'Setrika', 'Timbangan', 'Rak / Lemari', 'Lainnya'];
?>
<label>Nama Aset</label>
<input type="text" name="nama_aset" value="<?= e($isi['nama_aset']) ?>" placeholder="contoh: Mesin Cuci LG 10kg" required>

<label>Kategori</label>
<input type="text" name="kategori" list="daftar-kategori" value="<?= e($isi['kategori']) ?>" required>
<datalist id="daftar-kategori">
    <?php foreach ($kategori_umum as $k): ?><option value="<?= $k ?>"><?php endforeach; ?>
</datalist>

<label>Tanggal Perolehan</label>
<input type="date" name="tanggal_perolehan" value="<?= e($isi['tanggal_perolehan']) ?>" max="<?= date('Y-m-d') ?>" required>

<label>Nilai Perolehan (Rp)</label>
<input type="number" name="nilai_perolehan" value="<?= e($isi['nilai_perolehan']) ?>" min="1" step="1" required>

<label>Kondisi</label>
<select name="kondisi">
    <?php foreach (['baik' => 'Baik', 'rusak_ringan' => 'Rusak Ringan', 'rusak_berat' => 'Rusak Berat'] as $k => $v): ?>
        <option value="<?= $k ?>" <?= $isi['kondisi'] == $k ? 'selected' : '' ?>><?= $v ?></option>
    <?php endforeach; ?>
</select>

<label>Status</label>
<select name="status">
    <?php foreach (['dipakai' => 'Dipakai', 'tidak_dipakai' => 'Tidak Dipakai'] as $k => $v): ?>
        <option value="<?= $k ?>" <?= $isi['status'] == $k ? 'selected' : '' ?>><?= $v ?></option>
    <?php endforeach; ?>
</select>
