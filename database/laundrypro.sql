-- Database LaundryPro
-- Import lewat phpMyAdmin: buat database "laundrypro" dulu, lalu tab Import > pilih file ini.

CREATE DATABASE IF NOT EXISTS laundrypro;
USE laundrypro;

-- 1. Akun login (admin dan pelanggan)
CREATE TABLE users (
    id_user     INT AUTO_INCREMENT PRIMARY KEY,
    email       VARCHAR(100) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,           -- disimpan pakai password_hash()
    role        ENUM('admin','pelanggan') NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 2. Jenis layanan (Reguler, Express, dll)
CREATE TABLE layanan (
    id_layanan    INT AUTO_INCREMENT PRIMARY KEY,
    nama_layanan  VARCHAR(50) NOT NULL,
    tarif_per_kg  INT NOT NULL,                  -- dalam rupiah
    durasi_jam    INT NOT NULL                   -- lama pengerjaan, dipakai untuk hitung jatuh tempo
);

-- 3. Data pelanggan tetap
CREATE TABLE pelanggan (
    id_pelanggan  INT AUTO_INCREMENT PRIMARY KEY,
    id_user       INT NOT NULL UNIQUE,
    id_layanan    INT NOT NULL,
    nama          VARCHAR(100) NOT NULL,
    no_hp         VARCHAR(20) NOT NULL,
    alamat        TEXT,
    status        ENUM('aktif','nonaktif') DEFAULT 'aktif',
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE,
    FOREIGN KEY (id_layanan) REFERENCES layanan(id_layanan)
);

-- 4. Tagihan (dibuat admin tiap kali pelanggan nyuci)
CREATE TABLE tagihan (
    id_tagihan    INT AUTO_INCREMENT PRIMARY KEY,
    id_pelanggan  INT NOT NULL,
    id_layanan    INT NOT NULL,
    berat_kg      DECIMAL(5,2) NOT NULL,
    tarif_per_kg  INT NOT NULL,                  -- disalin dari layanan saat tagihan dibuat
    total         INT NOT NULL,                  -- berat_kg x tarif_per_kg
    waktu_mulai   DATETIME NOT NULL,
    jatuh_tempo   DATETIME NOT NULL,             -- waktu_mulai + durasi_jam
    status        ENUM('belum_bayar','menunggu_validasi','lunas','ditolak') DEFAULT 'belum_bayar',
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pelanggan) REFERENCES pelanggan(id_pelanggan),
    FOREIGN KEY (id_layanan) REFERENCES layanan(id_layanan)
);

-- 5. Pembayaran (bukti bayar yang diupload pelanggan)
CREATE TABLE pembayaran (
    id_pembayaran   INT AUTO_INCREMENT PRIMARY KEY,
    id_tagihan      INT NOT NULL,
    metode          ENUM('transfer','qris') NOT NULL,
    bukti           VARCHAR(255) NOT NULL,       -- nama file di uploads/bukti_bayar/
    tanggal_bayar   DATETIME DEFAULT CURRENT_TIMESTAMP,
    status          ENUM('menunggu','diterima','ditolak') DEFAULT 'menunggu',
    catatan_admin   VARCHAR(255),                -- alasan kalau ditolak
    divalidasi_pada DATETIME,
    FOREIGN KEY (id_tagihan) REFERENCES tagihan(id_tagihan)
);

-- 6. Aset tetap
CREATE TABLE aset (
    id_aset            INT AUTO_INCREMENT PRIMARY KEY,
    nama_aset          VARCHAR(100) NOT NULL,
    kategori           VARCHAR(50) NOT NULL,
    tanggal_perolehan  DATE NOT NULL,
    nilai_perolehan    INT NOT NULL,
    kondisi            ENUM('baik','rusak_ringan','rusak_berat') DEFAULT 'baik',
    status             ENUM('dipakai','tidak_dipakai') DEFAULT 'dipakai',
    created_at         DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ===== Data awal =====

-- Akun admin: admin@laundrypro.com / admin123
INSERT INTO users (email, password, role) VALUES
('admin@laundrypro.com', '$2y$10$t25p/gT9jPgQyB5zC3TzA.x8XU4i5ereFsbzg7Jn1U4aBBc4820bS', 'admin');

-- Tarif dan durasi Express masih contoh, sesuaikan
INSERT INTO layanan (nama_layanan, tarif_per_kg, durasi_jam) VALUES
('Reguler', 3000, 72),
('Express', 5000, 24);

-- Contoh pelanggan untuk testing: beny@gmail.com / pelanggan123
INSERT INTO users (email, password, role) VALUES
('beny@gmail.com', '$2y$10$0tcJPm.ndMUxWmDxiwJrX.lEXGqaC2ErwL8VCObd0B2OT4eKCMMuO', 'pelanggan');
INSERT INTO pelanggan (id_user, id_layanan, nama, no_hp, alamat) VALUES
(2, 1, 'Beny', '0812345677891', 'Padang');
