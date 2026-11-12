<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode tidak diizinkan.');
}
verify_csrf();

$action = (string) ($_POST['action'] ?? '');

try {
    $pdo = db();

    if ($action === 'add_user') {
        $nama = trim((string) ($_POST['nama'] ?? ''));
        $nisn = trim((string) ($_POST['nisn'] ?? ''));
        $kelas = trim((string) ($_POST['kelas'] ?? ''));
        $noHp = trim((string) ($_POST['no_hp'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($nama === '' || !preg_match('/^\d{10}$/', $nisn) || $kelas === '' || $username === '' || strlen($password) < 8) {
            set_flash('Lengkapi data siswa. Kata sandi minimal 8 karakter.', 'error');
            redirect();
        }

        $insert = $pdo->prepare(
            'INSERT INTO users (nama, nisn, kelas, no_hp, username, password, role)
             VALUES (?, ?, ?, ?, ?, ?, \'SISWA\')'
        );
        $insert->execute([$nama, $nisn, $kelas, $noHp ?: null, $username, password_hash($password, PASSWORD_DEFAULT)]);
        set_flash('Data siswa berhasil ditambahkan.');
        redirect();
    }

    if ($action !== 'add_penitipan') {
        set_flash('Jenis proses tidak dikenal.', 'error');
        redirect();
    }

    $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
    $slotId = filter_var($_POST['slot_id'] ?? null, FILTER_VALIDATE_INT);
    $model = trim((string) ($_POST['model_helm'] ?? ''));
    $warna = trim((string) ($_POST['warna_helm'] ?? ''));

    if (!$userId || !$slotId || $model === '' || $warna === '') {
        set_flash('Pilih siswa dan slot, lalu lengkapi model serta warna helm.', 'error');
        redirect();
    }

    $pdo->beginTransaction();
    $slotQuery = $pdo->prepare('SELECT status FROM slots WHERE id = ? FOR UPDATE');
    $slotQuery->execute([$slotId]);
    $slot = $slotQuery->fetch();
    if (!$slot || $slot['status'] !== 'TERSEDIA') {
        $pdo->rollBack();
        set_flash('Slot tersebut sudah tidak tersedia. Pilih slot lain.', 'error');
        redirect();
    }

    $kode = 'PH-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
    $insert = $pdo->prepare(
        'INSERT INTO penitipan
         (kode_penitipan, user_id, slot_id, model_helm, merk_helm, warna_helm,
          kondisi_helm, ciri_khusus, tanggal_titip, jam_titip, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), CURTIME(), \'DITITIPKAN\')'
    );
    $insert->execute([
        $kode,
        $userId,
        $slotId,
        $model,
        trim((string) ($_POST['merk_helm'] ?? '')) ?: null,
        $warna,
        trim((string) ($_POST['kondisi_helm'] ?? '')) ?: null,
        trim((string) ($_POST['ciri_khusus'] ?? '')) ?: null,
    ]);
    $pdo->prepare("UPDATE slots SET status = 'TERISI' WHERE id = ?")->execute([$slotId]);
    $pdo->commit();

    set_flash("Penitipan berhasil dicatat. Kode transaksi: {$kode}");
    redirect();
} catch (PDOException $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    database_error($exception);
}
