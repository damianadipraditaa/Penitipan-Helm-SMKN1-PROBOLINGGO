<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

try {
    $pdo = db();
    $students = $pdo->query("SELECT id, nama, nisn, kelas FROM users WHERE role = 'SISWA' ORDER BY nama")->fetchAll();
    $availableSlots = $pdo->query("SELECT id, nomor_slot FROM slots WHERE status = 'TERSEDIA' ORDER BY nomor_slot")->fetchAll();
    $stats = $pdo->query(
        "SELECT
          (SELECT COUNT(*) FROM slots WHERE status = 'TERSEDIA') AS slot_tersedia,
          (SELECT COUNT(*) FROM slots WHERE status = 'TERISI') AS slot_terisi,
          (SELECT COUNT(*) FROM penitipan WHERE status IN ('DIAJUKAN','DIVERIFIKASI','DITITIPKAN','MENUNGGU PENGAMBILAN')) AS penitipan_aktif"
    )->fetch();
    $recent = $pdo->query(
        'SELECT p.id, p.kode_penitipan, p.status, p.model_helm, p.merk_helm, p.warna_helm,
                p.tanggal_titip, u.nama AS nama_pemilik, s.nomor_slot
         FROM penitipan p
         JOIN users u ON u.id = p.user_id
         JOIN slots s ON s.id = p.slot_id
         ORDER BY p.created_at DESC LIMIT 8'
    )->fetchAll();
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    $dbError = 'Database belum dapat dihubungi. Pastikan MySQL Laragon aktif dan database db_penitipan_helm sudah dibuat.';
    $students = $availableSlots = $recent = [];
    $stats = ['slot_tersedia' => 0, 'slot_terisi' => 0, 'penitipan_aktif' => 0];
}

$flash = take_flash();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Penitipan Helm SMKN 1 Probolinggo</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="topbar">
  <a class="brand" href="index.php"><span class="brand-mark">PH</span><span>Penitipan Helm<small>SMKN 1 Probolinggo</small></span></a>
  <nav><a class="active" href="index.php">Beranda</a><a href="pageview.php">Riwayat penitipan</a></nav>
</header>

<main class="container">
  <section class="hero">
    <div><p class="eyebrow">SISTEM PENCATATAN</p><h1>Penitipan Helm</h1><p>Catat helm, pemilik, dan slot penitipan dalam satu tempat.</p></div>
    <div class="hero-icon" aria-hidden="true">⌑</div>
  </section>

  <?php if (isset($dbError)): ?><div class="notice error"><?= e($dbError) ?></div><?php endif; ?>
  <?php if ($flash): ?><div class="notice <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>

  <section class="stats" aria-label="Ringkasan penitipan">
    <article class="stat-card"><span>Slot tersedia</span><strong><?= e($stats['slot_tersedia']) ?></strong></article>
    <article class="stat-card"><span>Slot terisi</span><strong><?= e($stats['slot_terisi']) ?></strong></article>
    <article class="stat-card"><span>Penitipan aktif</span><strong><?= e($stats['penitipan_aktif']) ?></strong></article>
  </section>

  <section class="forms-grid">
    <article class="panel">
      <div class="panel-heading"><div><p class="eyebrow">AKUN PENGGUNA</p><h2>Tambah pengguna siswa</h2></div><span class="step">01</span></div>
      <p class="inline-help">Setiap siswa mendapat username dan kata sandi sendiri. Akunnya disimpan sebagai pengguna dengan role SISWA.</p>
      <form action="prosesinput.php" method="post" class="form-grid" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="add_user">
        <label>Nama lengkap<input name="nama" maxlength="100" required></label>
        <label>NISN<input name="nisn" maxlength="10" minlength="10" inputmode="numeric" pattern="[0-9]{10}" placeholder="10 digit NISN" required></label>
        <label>Kelas<input name="kelas" maxlength="30" placeholder="Contoh: XII RPL 1" required></label>
        <label>No. HP <span class="muted">(opsional)</span><input name="no_hp" maxlength="20" inputmode="tel"></label>
        <label>Username<input name="username" maxlength="50" autocomplete="new-username" placeholder="Contoh: nama atau NISN" required></label>
        <label>Kata sandi<input type="password" name="password" minlength="8" autocomplete="new-password" required></label>
        <button class="button button-secondary full" type="submit">Simpan data siswa</button>
      </form>
    </article>

    <article class="panel">
      <div class="panel-heading"><div><p class="eyebrow">TRANSAKSI BARU</p><h2>Catat penitipan</h2></div><span class="step">02</span></div>
      <?php if (!$students): ?><p class="inline-help">Tambahkan data siswa terlebih dahulu agar bisa mencatat penitipan.</p><?php endif; ?>
      <form action="prosesinput.php" method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="add_penitipan">
        <label class="full">Nama siswa<select name="user_id" required <?= !$students ? 'disabled' : '' ?>><option value="">Pilih siswa</option><?php foreach ($students as $student): ?><option value="<?= e($student['id']) ?>"><?= e($student['nama']) ?> — <?= e($student['nisn']) ?> (<?= e($student['kelas']) ?>)</option><?php endforeach; ?></select></label>
        <label>Slot<select name="slot_id" required <?= !$availableSlots ? 'disabled' : '' ?>><option value="">Pilih slot</option><?php foreach ($availableSlots as $slot): ?><option value="<?= e($slot['id']) ?>"><?= e($slot['nomor_slot']) ?></option><?php endforeach; ?></select></label>
        <label>Model helm<input name="model_helm" maxlength="60" placeholder="Contoh: Full face" required></label>
        <label>Merk <span class="muted">(opsional)</span><input name="merk_helm" maxlength="60"></label>
        <label>Warna<input name="warna_helm" maxlength="40" required></label>
        <label class="full">Kondisi helm <span class="muted">(opsional)</span><input name="kondisi_helm" maxlength="100" placeholder="Contoh: baik, ada goresan kecil"></label>
        <label class="full">Ciri khusus <span class="muted">(opsional)</span><input name="ciri_khusus" maxlength="255"></label>
        <button class="button full" type="submit" <?= (!$students || !$availableSlots) ? 'disabled' : '' ?>>Simpan penitipan</button>
      </form>
    </article>
  </section>

  <section class="panel table-panel">
    <div class="panel-heading"><div><p class="eyebrow">PEMANTAUAN</p><h2>Penitipan terbaru</h2></div><a class="text-link" href="pageview.php">Lihat semua riwayat <span aria-hidden="true">→</span></a></div>
    <?php if (!$recent): ?><div class="empty-state"><span>⌑</span><strong>Belum ada transaksi</strong><p>Transaksi baru akan tampil di tabel ini.</p></div><?php else: ?>
    <div class="table-wrap"><table><thead><tr><th>Kode</th><th>Pemilik</th><th>Helm</th><th>Slot</th><th>Tanggal titip</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($recent as $row): ?><tr>
      <td class="mono"><?= e($row['kode_penitipan']) ?></td><td><?= e($row['nama_pemilik']) ?></td>
      <td><?= e($row['merk_helm'] ?: $row['model_helm']) ?><small class="cell-subtitle"><?= e($row['warna_helm']) ?></small></td>
      <td><?= e($row['nomor_slot']) ?></td><td><?= e($row['tanggal_titip']) ?></td>
      <td><span class="badge <?= e(strtolower(str_replace(' ', '-', $row['status']))) ?>"><?= e($row['status']) ?></span></td>
      <td><a class="table-link" href="update.php?id=<?= e($row['id']) ?>">Detail</a></td>
    </tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
  </section>
  <footer>Penitipan Helm <span>•</span> SMKN 1 Probolinggo</footer>
</main>
</body>
</html>
