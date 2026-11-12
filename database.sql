CREATE DATABASE IF NOT EXISTS db_penitipan_helm
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE db_penitipan_helm;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama VARCHAR(100) NOT NULL,
  nisn CHAR(10) NULL,
  kelas VARCHAR(30) NULL,
  no_hp VARCHAR(20) NULL,
  username VARCHAR(50) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('ADMIN', 'SISWA') NOT NULL DEFAULT 'SISWA',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_nisn (nisn),
  UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS slots (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nomor_slot VARCHAR(20) NOT NULL,
  status ENUM('TERSEDIA', 'TERISI', 'TIDAK AKTIF') NOT NULL DEFAULT 'TERSEDIA',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_slots_nomor_slot (nomor_slot)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS penitipan (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode_penitipan VARCHAR(40) NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  slot_id INT UNSIGNED NOT NULL,
  model_helm VARCHAR(60) NOT NULL,
  merk_helm VARCHAR(60) NULL,
  warna_helm VARCHAR(40) NOT NULL,
  kondisi_helm VARCHAR(100) NULL,
  ciri_khusus VARCHAR(255) NULL,
  tanggal_titip DATE NOT NULL,
  jam_titip TIME NOT NULL,
  tanggal_ambil DATE NULL,
  jam_ambil TIME NULL,
  status ENUM('DIAJUKAN', 'DIVERIFIKASI', 'DITITIPKAN', 'MENUNGGU PENGAMBILAN', 'SUDAH DIAMBIL', 'DITOLAK') NOT NULL DEFAULT 'DIAJUKAN',
  catatan_admin TEXT NULL,
  -- Slot hanya dikunci oleh transaksi aktif; kode NULL bebas berulang di MySQL.
  slot_aktif_id INT UNSIGNED GENERATED ALWAYS AS (
    CASE WHEN status IN ('DIAJUKAN', 'DIVERIFIKASI', 'DITITIPKAN', 'MENUNGGU PENGAMBILAN') THEN slot_id ELSE NULL END
  ) STORED,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_penitipan_kode (kode_penitipan),
  UNIQUE KEY uq_penitipan_slot_aktif (slot_aktif_id),
  KEY idx_penitipan_user (user_id),
  KEY idx_penitipan_slot (slot_id),
  KEY idx_penitipan_status (status),
  CONSTRAINT fk_penitipan_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_slt FOREIGN KEY (slot_id) REFERENCES slots (id)
) ENGINE=InnoDB;
