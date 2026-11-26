<?php
// Fungsi-fungsi kecil yang dipakai di banyak halaman

// Simpan pesan untuk ditampilkan sekali di halaman berikutnya
function set_pesan($tipe, $isi)
{
    $_SESSION['pesan'] = ['tipe' => $tipe, 'isi' => $isi];
}

function tampil_pesan()
{
    if (!empty($_SESSION['pesan'])) {
        $p = $_SESSION['pesan'];
        echo '<div class="alert alert-' . $p['tipe'] . '">' . htmlspecialchars($p['isi']) . '</div>';
        unset($_SESSION['pesan']);
    }
}

function rupiah($angka)
{
    return "Rp " . number_format($angka, 0, ',', '.');
}

function e($teks)
{
    return htmlspecialchars($teks ?? '', ENT_QUOTES);
}

// Hitung total tagihan dan jatuh tempo.
// total       = berat x tarif per kg (dibulatkan ke rupiah terdekat)
// jatuh tempo = waktu mulai + durasi layanan (jam)
function hitung_tagihan($berat, $tarif, $waktu_mulai, $durasi_jam)
{
    $berat = round($berat, 2);   // samakan dengan kolom berat_kg (2 angka di belakang koma)
    $total = (int) round($berat * $tarif);
    $jatuh_tempo = date('Y-m-d H:i:s', strtotime($waktu_mulai) + $durasi_jam * 3600);
    return ['total' => $total, 'jatuh_tempo' => $jatuh_tempo];
}

// Tampilan status tagihan
function label_status_tagihan($status)
{
    $daftar = [
        'belum_bayar'       => ['Belum Bayar', 'label-kuning'],
        'menunggu_validasi' => ['Menunggu Validasi', 'label-biru'],
        'lunas'             => ['Lunas', 'label-hijau'],
        'ditolak'           => ['Ditolak', 'label-merah'],
    ];
    [$teks, $kelas] = $daftar[$status] ?? [$status, 'label-abu'];
    return "<span class=\"label $kelas\">$teks</span>";
}

// Tagihan dianggap lewat jatuh tempo kalau belum lunas dan waktunya sudah lewat
function lewat_jatuh_tempo($jatuh_tempo, $status)
{
    return $status !== 'lunas' && strtotime($jatuh_tempo) < time();
}

function tgl($datetime)
{
    return date('d/m/Y H:i', strtotime($datetime));
}

function kode_tagihan($id)
{
    return 'TG-' . str_pad($id, 4, '0', STR_PAD_LEFT);
}

function label_status_bayar($status)
{
    $daftar = [
        'menunggu' => ['Menunggu', 'label-biru'],
        'diterima' => ['Diterima', 'label-hijau'],
        'ditolak'  => ['Ditolak', 'label-merah'],
    ];
    [$teks, $kelas] = $daftar[$status] ?? [$status, 'label-abu'];
    return "<span class=\"label $kelas\">$teks</span>";
}

// Cek apakah tagihan perlu diingatkan ke pelanggan.
// Hasil: 'lewat' (sudah lewat jatuh tempo), 'segera' (jatuh tempo dalam 1 hari ke depan), atau null.
// Tagihan yang sudah Lunas atau Menunggu Validasi tidak diingatkan.
function cek_pengingat($jatuh_tempo, $status, $sekarang = null)
{
    if (!in_array($status, ['belum_bayar', 'ditolak'])) {
        return null;
    }
    $sekarang = $sekarang ?? time();
    $tempo = strtotime($jatuh_tempo);

    if ($tempo < $sekarang) {
        return 'lewat';
    }
    if ($tempo - $sekarang <= 24 * 3600) {
        return 'segera';
    }
    return null;
}
