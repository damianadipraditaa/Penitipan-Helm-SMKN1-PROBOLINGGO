USE db_penitipan_helm;

-- Kata sandi awal: Helm1234
-- Nilai di bawah adalah hasil password_hash(), bukan kata sandi polos.
INSERT IGNORE INTO users (nama, username, password, role)
VALUES (
  'Admin Sistem',
  'admin',
  '$2y$12$Bi4.1Z/NQR0aBajSM//TueqAwoSoL3WX535jCBns.dvsFSwTr1cZW',
  'ADMIN'
)
ON DUPLICATE KEY UPDATE password = VALUES(password), role = VALUES(role);

INSERT IGNORE INTO slots (nomor_slot)
VALUES ('S-01'), ('S-02'), ('S-03'), ('S-04'), ('S-05'),
       ('S-06'), ('S-07'), ('S-08'), ('S-09'), ('S-10');
