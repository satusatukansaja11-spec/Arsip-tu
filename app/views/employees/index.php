<?php
/** @var array $f @var array $result @var array $jabatans @var array $units */
$base = $f;
unset($base['page']);
$statusBadge = ['PNS' => 'primary', 'PPPK' => 'info', 'HONORER' => 'secondary'];
$statusLabel = ['PNS' => 'PNS', 'PPPK' => 'PPPK', 'HONORER' => 'Honorer'];

$sortHead = static function (string $key, string $label) use ($f): string {
    $dir = ($f['sort'] === $key && $f['dir'] === 'asc') ? 'desc' : 'asc';
    $q = $f;
    unset($q['page']);
    $href = query_url('pegawai', $q, ['sort' => $key, 'dir' => $dir]);
    $icon = $f['sort'] === $key ? ($f['dir'] === 'asc' ? ' <i class="bi bi-caret-up-fill"></i>' : ' <i class="bi bi-caret-down-fill"></i>') : '';
    return '<a href="' . e($href) . '">' . e($label) . $icon . '</a>';
};
$hasFilter = $f['q'] !== '' || $f['status'] !== '' || $f['jabatan'] !== '' || $f['unit'] !== '' || $f['aktif'] !== '';
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <div>
    <h1 class="h4 mb-0">Data Pegawai</h1>
    <small class="text-muted"><?= number_format($result['total'], 0, ',', '.') ?> pegawai ditemukan</small>
  </div>
  <div class="ms-auto d-flex flex-wrap gap-2 no-print">
    <?php if (Auth::can('report.view')): ?>
      <a class="btn btn-outline-secondary" href="<?= e(query_url('pegawai/ekspor', $base)) ?>"><i class="bi bi-filetype-csv"></i> Ekspor CSV</a>
    <?php endif; ?>
    <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Cetak</button>
    <?php if (Auth::can('employee.create')): ?>
      <a class="btn btn-primary" href="<?= e(url('pegawai/tambah')) ?>"><i class="bi bi-person-plus"></i> Tambah Pegawai</a>
    <?php endif; ?>
  </div>
</div>

<form method="get" action="<?= e(url('pegawai')) ?>" class="card border-0 shadow-sm mb-3 no-print">
  <div class="card-body">
    <div class="row g-2">
      <div class="col-md-4">
        <input type="search" name="q" class="form-control" placeholder="Cari nama atau NIP" maxlength="100" value="<?= e($f['q']) ?>">
      </div>
      <div class="col-6 col-md-2">
        <select name="status" class="form-select" aria-label="Status kepegawaian">
          <option value="">Semua status</option>
          <?php foreach (Employee::STATUSES as $s): ?>
            <option value="<?= e($s) ?>" <?= $f['status'] === $s ? 'selected' : '' ?>><?= e($statusLabel[$s]) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <select name="jabatan" class="form-select" aria-label="Jabatan">
          <option value="">Semua jabatan</option>
          <?php foreach ($jabatans as $j): ?>
            <option value="<?= e($j) ?>" <?= $f['jabatan'] === $j ? 'selected' : '' ?>><?= e($j) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <select name="unit" class="form-select" aria-label="Unit kerja">
          <option value="">Semua unit</option>
          <?php foreach ($units as $u): ?>
            <option value="<?= e($u) ?>" <?= $f['unit'] === $u ? 'selected' : '' ?>><?= e($u) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <select name="aktif" class="form-select" aria-label="Status aktif">
          <option value="">Aktif &amp; tidak aktif</option>
          <option value="1" <?= $f['aktif'] === '1' ? 'selected' : '' ?>>Aktif</option>
          <option value="0" <?= $f['aktif'] === '0' ? 'selected' : '' ?>>Tidak aktif</option>
        </select>
      </div>
    </div>
    <input type="hidden" name="sort" value="<?= e($f['sort']) ?>">
    <input type="hidden" name="dir" value="<?= e($f['dir']) ?>">
    <div class="mt-2 d-flex gap-2">
      <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Cari</button>
      <?php if ($hasFilter): ?>
        <a class="btn btn-outline-secondary" href="<?= e(url('pegawai')) ?>">Reset</a>
      <?php endif; ?>
    </div>
  </div>
</form>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th style="width:60px">No</th>
          <th><?= $sortHead('nama', 'Nama') ?></th>
          <th><?= $sortHead('nip', 'NIP') ?></th>
          <th><?= $sortHead('jabatan', 'Jabatan') ?></th>
          <th><?= $sortHead('unit', 'Unit Kerja') ?></th>
          <th><?= $sortHead('status', 'Status') ?></th>
          <th>Aktif</th>
          <th class="no-print text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php $no = ($result['page'] - 1) * $result['perPage']; foreach ($result['rows'] as $r): ?>
          <tr>
            <td><?= ++$no ?></td>
            <td><a class="fw-semibold text-decoration-none" href="<?= e(url('pegawai/detail?id=' . (int)$r['id'])) ?>"><?= e($r['nama_lengkap']) ?></a></td>
            <td><?= e($r['nip'] ?: '-') ?></td>
            <td><?= e($r['jabatan'] ?: '-') ?></td>
            <td><?= e($r['unit_kerja'] ?: '-') ?></td>
            <td><span class="badge text-bg-<?= e($statusBadge[$r['status_kepegawaian']] ?? 'secondary') ?>"><?= e($statusLabel[$r['status_kepegawaian']] ?? $r['status_kepegawaian']) ?></span></td>
            <td><?= $r['status_aktif'] ? '<span class="badge text-bg-success">Aktif</span>' : '<span class="badge text-bg-light border">Tidak aktif</span>' ?></td>
            <td class="no-print text-end text-nowrap">
              <a class="btn btn-sm btn-outline-primary" href="<?= e(url('pegawai/detail?id=' . (int)$r['id'])) ?>" title="Detail"><i class="bi bi-eye"></i></a>
              <?php if (Auth::can('employee.update')): ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('pegawai/ubah?id=' . (int)$r['id'])) ?>" title="Ubah"><i class="bi bi-pencil"></i></a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$result['rows']): ?>
          <tr><td colspan="8">
            <div class="empty-state">
              <i class="bi bi-people"></i>
              <p class="mb-2"><?= $hasFilter ? 'Tidak ada pegawai yang cocok dengan pencarian.' : 'Belum ada data pegawai.' ?></p>
              <?php if (!$hasFilter && Auth::can('employee.create')): ?>
                <a class="btn btn-primary btn-sm" href="<?= e(url('pegawai/tambah')) ?>">Tambah pegawai pertama</a>
              <?php endif; ?>
            </div>
          </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($result['pages'] > 1): ?>
    <div class="card-footer bg-white no-print">
      <nav aria-label="Halaman">
        <ul class="pagination pagination-sm mb-0 justify-content-center flex-wrap">
          <?php
            $cur = $result['page'];
            $pages = $result['pages'];
            $link = static fn(int $p): string => query_url('pegawai', $base, ['page' => $p]);
            $start = max(1, $cur - 2);
            $end = min($pages, $cur + 2);
          ?>
          <li class="page-item <?= $cur <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= e($link(max(1, $cur - 1))) ?>">&laquo;</a></li>
          <?php if ($start > 1): ?><li class="page-item"><a class="page-link" href="<?= e($link(1)) ?>">1</a></li><?php endif; ?>
          <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">&hellip;</span></li><?php endif; ?>
          <?php for ($p = $start; $p <= $end; $p++): ?>
            <li class="page-item <?= $p === $cur ? 'active' : '' ?>"><a class="page-link" href="<?= e($link($p)) ?>"><?= $p ?></a></li>
          <?php endfor; ?>
          <?php if ($end < $pages - 1): ?><li class="page-item disabled"><span class="page-link">&hellip;</span></li><?php endif; ?>
          <?php if ($end < $pages): ?><li class="page-item"><a class="page-link" href="<?= e($link($pages)) ?>"><?= $pages ?></a></li><?php endif; ?>
          <li class="page-item <?= $cur >= $pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= e($link(min($pages, $cur + 1))) ?>">&raquo;</a></li>
        </ul>
      </nav>
    </div>
  <?php endif; ?>
</div>
