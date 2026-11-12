USE db_penitipan_helm;

-- 1. Melihat semua pengguna
SELECT id, nama, nisn, kelas, username, role, created_at
FROM users
ORDER BY nama ASC;

-- 2. Mencari slot yang tersedia
SELECT id, nomor_slot, status
FROM slots
WHERE status = 'TERSEDIA'
ORDER BY nomor_slot ASC;

-- 3. Menambahkan pengguna.
-- Ganti nilai password dengan hasil password_hash() dari PHP sebelum dijalankan.
-- Jangan masukkan kata sandi asli ke kolom password.
-- INSERT INTO users (nama, nisn, kelas, no_hp, username, password, role)
-- VALUES ('Nama Siswa', '1234567890', 'X RPL 1', '081234567890', 'username_siswa',
--         '$2y$12$GANTI_DENGAN_HASH_PASSWORD', 'SISWA');

-- 4. Menambahkan slot baru
-- INSERT INTO slots (nomor_slot) VALUES ('S-11');

-- 5. Melihat transaksi beserta nama pemilik dan nomor slot
SELECT p.kode_penitipan, u.nama AS nama_pemilik, u.nisn,
       s.nomor_slot, p.model_helm, p.merk_helm, p.warna_helm,
       p.tanggal_titip, p.jam_titip, p.status
FROM penitipan AS p
JOIN users AS u ON u.id = p.user_id
JOIN slots AS s ON s.id = p.slot_id
ORDER BY p.created_at DESC;

-- 6. Menghitung slot berdasarkan status
SELECT status, COUNT(*) AS jumlah_slot
FROM slots
GROUP BY status
ORDER BY status;

-- 7. Menghitung jumlah penitipan per pengguna
SELECT u.id, u.nama, COUNT(p.id) AS jumlah_penitipan
FROM users AS u
LEFT JOIN penitipan AS p ON p.user_id = u.id
GROUP BY u.id, u.nama
ORDER BY jumlah_penitipan DESC, u.nama ASC;

-- 8. Mengubah status transaksi dan slot dalam satu transaksi database.
-- Isi ID yang benar setelah melihat data. Jalankan keduanya bersama-sama.
-- START TRANSACTION;
-- SELECT id, status FROM penitipan WHERE id = 1 FOR UPDATE;
-- UPDATE penitipan SET status = 'DITITIPKAN' WHERE id = 1;
-- UPDATE slots SET status = 'TERISI' WHERE id = 1;
-- COMMIT;
-- Jika ada kesalahan sebelum COMMIT, jalankan ROLLBACK;

-- 9. Pengambilan helm: perbarui transaksi dan bebaskan slot bersama-sama.
-- START TRANSACTION;
-- UPDATE penitipan
-- SET status = 'SUDAH DIAMBIL', tanggal_ambil = CURDATE(), jam_ambil = CURTIME()
-- WHERE id = 1 AND status IN ('DITITIPKAN', 'MENUNGGU PENGAMBILAN');
-- UPDATE slots SET status = 'TERSEDIA' WHERE id = 1;
-- COMMIT;

-- Transaksi penitipan tidak dihapus agar riwayat tetap tersimpan.
