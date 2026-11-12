<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

$statuses = ['DIAJUKAN', 'DIVERIFIKASI', 'DITITIPKAN', 'MENUNGGU PENGAMBILAN', 'SUDAH DIAMBIL', 'DITOLAK'];
$filter = trim((string) ($_GET['status'] ?? ''));
$rows = [];
$loadError = null;

try {
    $pdo = db();
    if ($filter !== '' && in_array($filter, $statuses, true)) {
        $query = $pdo->prepare(
            'SELECT p.id, p.kode_penitipan, p.status, p.model_helm, p.merk_helm,
                    p.warna_helm, p.tanggal_titip, p.jam_titip, p.tanggal_ambil,
                    u.nama AS nama_pemilik, u.nisn, s.nomor_slot
             FROM penitipan p JOIN users u ON u.id = p.user_id JOIN slots s ON s.id = p.slot_id
             WHERE p.status = ? ORDER BY p.created_at DESC'
        );
        $query->execute([$filter]);
    } else {
        $query = $pdo->query(
            'SELECT p.id, p.kode_penitipan, p.status, p.model_helm, p.merk_helm,
                    p.warna_helm, p.tanggal_titip, p.jam_titip, p.tanggal_ambil,
                    u.nama AS nama_pemilik, u.nisn, s.nomor_slot
             FROM penitipan p JOIN users u ON u.id = p.user_id JOIN slots s ON s.id = p.slot_id
             ORDER BY p.created_at DESC'
        );
    }
    $rows = $query->fetchAll();
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    $loadError = 'Database belum dapat dihubungi. Pastikan MySQL Laragon aktif.';
}

$flash = take_flash();
?>
<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Riwayat Penitipan Helm</title><link rel="stylesheet" href="style.css"></head>
<body>
<header class="topbar"><a class="brand" href="index.php"><span class="brand-mark">PH</span><span>Penitipan Helm<small>SMKN 1 Probolinggo</small></span></a><nav><a href="index.php">Beranda</a><a class="active" href="pageview.php">Riwayat penitipan</a></nav></header>
<main class="container">
  <div class="page-title"><div><p class="eyebrow">ARSIP TRANSAKSI</p><h1>Riwayat penitipan</h1><p>Semua transaksi tetap tercatat, termasuk helm yang sudah diambil.</p></div><a class="back-link" href="index.php">← Kembali ke beranda</a></div>
  <?php if ($loadError): ?><div class="notice error"><?= e($loadError) ?></div><?php endif; ?>
  <?php if ($flash): ?><div class="notice <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
  <section class="panel table-panel">
    <div class="filter-row"><strong><?= count($rows) ?> transaksi</strong><form class="filter-form" method="get"><select name="status"><option value="">Semua status</option><?php foreach ($statuses as $status): ?><option value="<?= e($status) ?>" <?= $filter === $status ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select><button class="button" type="submit">Terapkan</button></form></div>
    <?php if (!$rows): ?><div class="empty-state"><span>⌑</span><strong>Belum ada transaksi</strong><p>Data penitipan yang tercatat akan muncul di sini.</p></div><?php else: ?>
    <div class="table-wrap"><table><thead><tr><th>Kode</th><th>Pemilik</th><th>Helm</th><th>Slot</th><th>Waktu titip</th><th>Waktu ambil</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $row): ?><tr>
      <td class="mono"><?= e($row['kode_penitipan']) ?></td><td><?= e($row['nama_pemilik']) ?><small class="cell-subtitle"><?= e($row['nisn']) ?></small></td>
      <td><?= e($row['merk_helm'] ?: $row['model_helm']) ?><small class="cell-subtitle"><?= e($row['warna_helm']) ?></small></td>
      <td><?= e($row['nomor_slot']) ?></td><td><?= e($row['tanggal_titip']) ?><small class="cell-subtitle"><?= e($row['jam_titip']) ?></small></td>
      <td><?= e($row['tanggal_ambil'] ?: '—') ?></td><td><span class="badge <?= e(strtolower(str_replace(' ', '-', $row['status']))) ?>"><?= e($row['status']) ?></span></td>
      <td><a class="table-link" href="update.php?id=<?= e($row['id']) ?>">Detail</a></td>
    </tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
  </section>
  <footer>Penitipan Helm <span>•</span> SMKN 1 Probolinggo</footer>
</main>
</body></html>
