-- Pengujian memakai transaksi dan diakhiri ROLLBACK, jadi tidak menyimpan data uji.
USE db_penitipan_helm;
START TRANSACTION;

INSERT INTO users (nama, nisn, kelas, username, password, role)
VALUES ('Siswa Uji', '0000000001', 'X RPL 1', 'siswa_uji',
        '$2y$12$QjwE4GryynptY9BZuXqmeeyTO6u7WKxpEF2pBFKTlLpKnCMAW96sK', 'SISWA');
SET @user_uji = LAST_INSERT_ID();

INSERT INTO slots (nomor_slot) VALUES ('UJI-01');
SET @slot_uji = LAST_INSERT_ID();

INSERT INTO penitipan
  (kode_penitipan, user_id, slot_id, model_helm, warna_helm, tanggal_titip, jam_titip, status)
VALUES
  ('UJI-KODE-001', @user_uji, @slot_uji, 'Full face', 'Hitam', CURDATE(), CURTIME(), 'DITITIPKAN');
SET @transaksi_uji = LAST_INSERT_ID();
UPDATE slots SET status = 'TERISI' WHERE id = @slot_uji;

SELECT 'DITITIPKAN' AS langkah, p.status AS status_transaksi, s.status AS status_slot
FROM penitipan p JOIN slots s ON s.id = p.slot_id WHERE p.id = @transaksi_uji;

UPDATE penitipan
SET status = 'SUDAH DIAMBIL', tanggal_ambil = CURDATE(), jam_ambil = CURTIME()
WHERE id = @transaksi_uji;
UPDATE slots SET status = 'TERSEDIA' WHERE id = @slot_uji;

-- Riwayat transaksi tetap ada setelah pengambilan dan slot kembali tersedia.
SELECT 'SUDAH DIAMBIL' AS langkah, p.status AS status_transaksi,
       p.tanggal_ambil, p.jam_ambil, s.status AS status_slot
FROM penitipan p JOIN slots s ON s.id = p.slot_id WHERE p.id = @transaksi_uji;

-- Kedua indeks unik ini mencegah kode ganda dan pemakaian slot aktif ganda.
SELECT INDEX_NAME, NON_UNIQUE
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'penitipan'
  AND INDEX_NAME IN ('uq_penitipan_kode', 'uq_penitipan_slot_aktif');

ROLLBACK;
