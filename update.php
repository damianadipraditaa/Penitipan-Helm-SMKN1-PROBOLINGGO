<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1) {
    http_response_code(400);
    exit('ID transaksi tidak valid.');
}

try {
    $query = db()->prepare(
        'SELECT p.*, u.nama AS nama_pemilik, u.nisn, u.kelas, u.no_hp, s.nomor_slot
         FROM penitipan p JOIN users u ON u.id = p.user_id JOIN slots s ON s.id = p.slot_id
         WHERE p.id = ?'
    );
    $query->execute([$id]);
    $row = $query->fetch();
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    exit('Database belum dapat dihubungi. Pastikan MySQL Laragon aktif.');
}

if (!$row) {
    http_response_code(404);
    exit('Data penitipan tidak ditemukan.');
}

$flash = take_flash();
$statuses = ['DIAJUKAN', 'DIVERIFIKASI', 'DITITIPKAN', 'MENUNGGU PENGAMBILAN', 'SUDAH DIAMBIL', 'DITOLAK'];
?>
<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Detail Penitipan <?= e($row['kode_penitipan']) ?></title><link rel="stylesheet" href="style.css"></head>
<body>
<header class="topbar"><a class="brand" href="index.php"><span class="brand-mark">PH</span><span>Penitipan Helm<small>SMKN 1 Probolinggo</small></span></a><nav><a href="index.php">Beranda</a><a class="active" href="pageview.php">Riwayat penitipan</a></nav></header>
<main class="container">
  <div class="page-title"><div><p class="eyebrow">DETAIL TRANSAKSI</p><h1><?= e($row['kode_penitipan']) ?></h1><p>Informasi helm, pemilik, dan status penitipan.</p></div><a class="back-link" href="pageview.php">← Kembali ke riwayat</a></div>
  <?php if ($flash): ?><div class="notice <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
  <div class="detail-grid">
    <section class="panel"><div class="panel-heading"><div><p class="eyebrow">PEMILIK & HELM</p><h2>Informasi penitipan</h2></div><span class="badge <?= e(strtolower(str_replace(' ', '-', $row['status']))) ?>"><?= e($row['status']) ?></span></div>
      <div class="detail-list">
        <div class="detail-item"><span>Nama pemilik</span><strong><?= e($row['nama_pemilik']) ?></strong></div><div class="detail-item"><span>NISN / Kelas</span><strong><?= e($row['nisn']) ?> / <?= e($row['kelas']) ?></strong></div>
        <div class="detail-item"><span>Nomor slot</span><strong><?= e($row['nomor_slot']) ?></strong></div><div class="detail-item"><span>Nomor HP</span><strong><?= e($row['no_hp'] ?: '—') ?></strong></div>
        <div class="detail-item"><span>Model helm</span><strong><?= e($row['model_helm']) ?></strong></div><div class="detail-item"><span>Merk</span><strong><?= e($row['merk_helm'] ?: '—') ?></strong></div>
        <div class="detail-item"><span>Warna</span><strong><?= e($row['warna_helm']) ?></strong></div><div class="detail-item"><span>Kondisi</span><strong><?= e($row['kondisi_helm'] ?: '—') ?></strong></div>
        <div class="detail-item full"><span>Ciri khusus</span><strong><?= e($row['ciri_khusus'] ?: '—') ?></strong></div>
        <div class="detail-item"><span>Waktu titip</span><strong><?= e($row['tanggal_titip']) ?>, <?= e($row['jam_titip']) ?></strong></div>
        <div class="detail-item"><span>Waktu ambil</span><strong><?= e($row['tanggal_ambil'] ?: '—') ?><?= $row['jam_ambil'] ? ', ' . e($row['jam_ambil']) : '' ?></strong></div>
        <div class="detail-item full"><span>Catatan admin</span><strong><?= nl2br(e($row['catatan_admin'] ?: '—')) ?></strong></div>
      </div>
    </section>
    <section class="panel"><div class="panel-heading"><div><p class="eyebrow">TINDAKAN ADMIN</p><h2>Ubah status</h2></div></div>
      <?php if (in_array($row['status'], ['SUDAH DIAMBIL', 'DITOLAK'], true)): ?><p class="inline-help">Transaksi ini sudah selesai. Status akhirnya dikunci, tetapi catatan masih dapat diperbarui.</p><?php endif; ?>
      <form action="prosesupdate.php" method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= e($row['id']) ?>">
        <label class="full">Status<select name="status" required><?php foreach ($statuses as $status): ?><option value="<?= e($status) ?>" <?= $row['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></label>
        <label class="full">Catatan admin <span class="muted">(opsional)</span><textarea name="catatan_admin" rows="4" maxlength="2000"><?= e($row['catatan_admin']) ?></textarea></label>
        <button class="button full" type="submit">Simpan perubahan</button>
      </form>
      <?php if (!in_array($row['status'], ['SUDAH DIAMBIL', 'DITOLAK'], true)): ?>
      <form action="hapus.php" method="post" class="cancel-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= e($row['id']) ?>">
        <button class="button danger-button full" type="submit">Batalkan penitipan</button>
      </form>
      <?php endif; ?>
    </section>
  </div>
  <footer>Riwayat transaksi disimpan untuk keperluan pencatatan.</footer>
</main>
</body></html>
