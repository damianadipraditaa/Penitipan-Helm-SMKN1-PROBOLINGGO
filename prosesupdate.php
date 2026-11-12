<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode tidak diizinkan.');
}
verify_csrf();

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$status = trim((string) ($_POST['status'] ?? ''));
$note = trim((string) ($_POST['catatan_admin'] ?? ''));
$allowedStatuses = ['DIAJUKAN', 'DIVERIFIKASI', 'DITITIPKAN', 'MENUNGGU PENGAMBILAN', 'SUDAH DIAMBIL', 'DITOLAK'];

if (!$id || !in_array($status, $allowedStatuses, true) || strlen($note) > 6000) {
    set_flash('Data perubahan tidak valid.', 'error');
    redirect();
}

try {
    $pdo = db();
    $pdo->beginTransaction();
    $query = $pdo->prepare('SELECT slot_id, status FROM penitipan WHERE id = ? FOR UPDATE');
    $query->execute([$id]);
    $transaction = $query->fetch();

    if (!$transaction) {
        $pdo->rollBack();
        set_flash('Data penitipan tidak ditemukan.', 'error');
        redirect('pageview.php');
    }

    $slotQuery = $pdo->prepare('SELECT status FROM slots WHERE id = ? FOR UPDATE');
    $slotQuery->execute([$transaction['slot_id']]);
    $slot = $slotQuery->fetch();

    if (!$slot) {
        $pdo->rollBack();
        set_flash('Slot yang terhubung tidak ditemukan.', 'error');
        redirect("update.php?id={$id}");
    }

    $active = ['DIAJUKAN', 'DIVERIFIKASI', 'DITITIPKAN', 'MENUNGGU PENGAMBILAN'];
    $terminal = ['SUDAH DIAMBIL', 'DITOLAK'];
    if (in_array($transaction['status'], $terminal, true) && $status !== $transaction['status']) {
        $pdo->rollBack();
        set_flash('Status akhir transaksi tidak dapat diubah lagi.', 'error');
        redirect("update.php?id={$id}");
    }
    if (in_array($status, $active, true) && $slot['status'] === 'TIDAK AKTIF') {
        $pdo->rollBack();
        set_flash('Slot tidak aktif, status penitipan tidak dapat dibuat aktif.', 'error');
        redirect("update.php?id={$id}");
    }

    if ($status === 'SUDAH DIAMBIL' && $transaction['status'] !== 'SUDAH DIAMBIL') {
        $update = $pdo->prepare(
            'UPDATE penitipan SET status = ?, catatan_admin = ?, tanggal_ambil = CURDATE(), jam_ambil = CURTIME() WHERE id = ?'
        );
    } else {
        $update = $pdo->prepare('UPDATE penitipan SET status = ?, catatan_admin = ? WHERE id = ?');
    }
    $update->execute([$status, $note ?: null, $id]);

    if (in_array($status, $active, true)) {
        $pdo->prepare("UPDATE slots SET status = 'TERISI' WHERE id = ?")->execute([$transaction['slot_id']]);
    } else {
        $pdo->prepare("UPDATE slots SET status = 'TERSEDIA' WHERE id = ? AND status = 'TERISI'")
            ->execute([$transaction['slot_id']]);
    }

    $pdo->commit();
    set_flash('Perubahan transaksi berhasil disimpan.');
    redirect("update.php?id={$id}");
} catch (PDOException $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    database_error($exception);
}
