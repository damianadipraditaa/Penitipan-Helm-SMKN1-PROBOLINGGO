<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metode tidak diizinkan.');
}
verify_csrf();
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    set_flash('ID transaksi tidak valid.', 'error');
    redirect('pageview.php');
}

try {
    $pdo = db();
    $pdo->beginTransaction();
    $query = $pdo->prepare('SELECT slot_id, status, catatan_admin FROM penitipan WHERE id = ? FOR UPDATE');
    $query->execute([$id]);
    $transaction = $query->fetch();
    if (!$transaction) {
        $pdo->rollBack();
        set_flash('Data penitipan tidak ditemukan.', 'error');
        redirect('pageview.php');
    }
    if (in_array($transaction['status'], ['SUDAH DIAMBIL', 'DITOLAK'], true)) {
        $pdo->rollBack();
        set_flash('Transaksi ini sudah selesai.', 'error');
        redirect("update.php?id={$id}");
    }

    $slotQuery = $pdo->prepare('SELECT status FROM slots WHERE id = ? FOR UPDATE');
    $slotQuery->execute([$transaction['slot_id']]);
    $slot = $slotQuery->fetch();
    if (!$slot) {
        $pdo->rollBack();
        set_flash('Slot yang terhubung tidak ditemukan.', 'error');
        redirect("update.php?id={$id}");
    }

    $pdo->prepare("UPDATE penitipan SET status = 'DITOLAK' WHERE id = ?")->execute([$id]);
    $pdo->prepare("UPDATE slots SET status = 'TERSEDIA' WHERE id = ? AND status = 'TERISI'")
        ->execute([$transaction['slot_id']]);
    $pdo->commit();

    set_flash('Penitipan dibatalkan. Riwayat tetap tersimpan dengan status DITOLAK.');
    redirect("update.php?id={$id}");
} catch (PDOException $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    database_error($exception);
}
