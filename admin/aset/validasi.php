<?php
// Ambil isi form dan cek validasinya. Dipakai tambah.php dan edit.php
function ambil_form_aset()
{
    $isi = [];
    foreach (['nama_aset', 'kategori', 'tanggal_perolehan', 'nilai_perolehan', 'kondisi', 'status'] as $k) {
        $isi[$k] = trim($_POST[$k] ?? '');
    }
    return $isi;
}

function validasi_aset($isi)
{
    $error = [];
    if ($isi['nama_aset'] === '') $error[] = "Nama aset wajib diisi.";
    if ($isi['kategori'] === '')  $error[] = "Kategori wajib diisi.";

    $tgl = DateTime::createFromFormat('Y-m-d', $isi['tanggal_perolehan']);
    if (!$tgl || $tgl->format('Y-m-d') !== $isi['tanggal_perolehan']) {
        $error[] = "Tanggal perolehan tidak valid.";
    } elseif ($isi['tanggal_perolehan'] > date('Y-m-d')) {
        $error[] = "Tanggal perolehan tidak boleh di masa depan.";
    }

    if (!ctype_digit($isi['nilai_perolehan']) || (int)$isi['nilai_perolehan'] <= 0) {
        $error[] = "Nilai perolehan harus angka bulat lebih dari 0.";
    } elseif ((int)$isi['nilai_perolehan'] > 2000000000) {
        $error[] = "Nilai perolehan maksimal Rp 2.000.000.000.";
    }
    if (!in_array($isi['kondisi'], ['baik', 'rusak_ringan', 'rusak_berat'])) $error[] = "Kondisi tidak valid.";
    if (!in_array($isi['status'], ['dipakai', 'tidak_dipakai'])) $error[] = "Status tidak valid.";
    return $error;
}
