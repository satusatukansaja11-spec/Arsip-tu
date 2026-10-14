<?php
$statusChart = [
    'labels' => ['PNS', 'PPPK', 'Honorer'],
    'values' => [$d['byStatus']['PNS'], $d['byStatus']['PPPK'], $d['byStatus']['HONORER']],
];
$catChart = [
    'labels' => array_map(static fn($r) => $r['nama'], $d['byCategory']),
    'values' => array_map(static fn($r) => (int)$r['n'], $d['byCategory']),
];
$cards = [
    ['Total Pegawai',        $d['total'],               'bi-people-fill',        'primary'],
    ['Pegawai PNS',          $d['byStatus']['PNS'],     'bi-person-badge',       'primary'],
    ['Pegawai PPPK',         $d['byStatus']['PPPK'],    'bi-person-check',       'info'],
    ['Pegawai Honorer',      $d['byStatus']['HONORER'], 'bi-person',             'secondary'],
    ['Total Dokumen Arsip',  $d['docTotal'],            'bi-files',              'primary'],
    ['Arsip Pegawai Lengkap',$d['lengkap'],             'bi-check-circle-fill',  'success'],
    ['Arsip Belum Lengkap',  $d['belumLengkap'],        'bi-exclamation-circle-fill', 'warning'],
    ['Dokumen Baru (7 hari)',$d['docBaru'],             'bi-cloud-upload',       'info'],
];
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <div>
    <h1 class="h4 mb-0">Dashboard</h1>
    <small class="text-muted">Selamat datang, <?= e(Auth::user()['nama_lengkap'] ?? '') ?> (<?= e(Auth::user()['role_nama'] ?? '') ?>)</small>
  </div>
  <div class="ms-auto d-flex gap-2">
    <?php if (Auth::can('employee.create')): ?>
      <a class="btn btn-primary" href="<?= e(url('pegawai/tambah')) ?>"><i class="bi bi-person-plus"></i> Tambah Pegawai</a>
    <?php endif; ?>
    <?php if (Auth::can('document.upload')): ?>
      <button class="btn btn-outline-primary" type="button" disabled title="Tersedia pada tahap modul arsip"><i class="bi bi-cloud-upload"></i> Unggah Arsip</button>
    <?php endif; ?>
  </div>
</div>

<div class="row row-cols-2 row-cols-lg-4 g-3 mb-4">
  <?php foreach ($cards as [$label, $value, $icon, $color]): ?>
    <div class="col">
      <div class="card stat-card border-0 shadow-sm h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="stat-icon bg-<?= e($color) ?>-subtle text-<?= e($color) ?>-emphasis"><i class="bi <?= e($icon) ?>"></i></div>
          <div>
            <div class="stat-value"><?= number_format((int)$value, 0, ',', '.') ?></div>
            <div class="text-muted small"><?= e($label) ?></div>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-5">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white fw-semibold">Pegawai Berdasarkan Status</div>
      <div class="card-body">
        <?php if ($d['total'] > 0): ?>
          <div class="chart-box"><canvas id="chartStatus" data-chart="<?= e(json_encode($statusChart, JSON_UNESCAPED_UNICODE)) ?>"></canvas></div>
        <?php else: ?>
          <div class="empty-state"><i class="bi bi-pie-chart"></i><p>Belum ada data pegawai.</p></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white fw-semibold">Dokumen Berdasarkan Kategori</div>
      <div class="card-body">
        <?php if ($d['docTotal'] > 0): ?>
          <div class="chart-box"><canvas id="chartKategori" data-chart="<?= e(json_encode($catChart, JSON_UNESCAPED_UNICODE)) ?>"></canvas></div>
        <?php else: ?>
          <div class="empty-state"><i class="bi bi-bar-chart"></i><p>Belum ada dokumen yang diunggah.</p></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <?php if (Auth::can('document.view')): ?>
  <div class="col-lg-7">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white fw-semibold">Dokumen Terbaru</div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light"><tr><th>Pegawai</th><th>Kategori</th><th>Nomor</th><th>Diunggah</th></tr></thead>
          <tbody>
          <?php foreach ($d['recentDocs'] as $r): ?>
            <tr>
              <td><a href="<?= e(url('pegawai/detail?id=' . (int)$r['employee_id'])) ?>"><?= e($r['nama_lengkap']) ?></a></td>
              <td><?= e($r['kategori']) ?></td>
              <td><?= e($r['nomor_dokumen'] ?: '-') ?></td>
              <td><?= e(fmt_datetime($r['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$d['recentDocs']): ?>
            <tr><td colspan="4"><div class="empty-state py-4"><i class="bi bi-folder2-open"></i><p class="mb-0">Belum ada dokumen.</p></div></td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if (Auth::can('log.view.all') || Auth::can('log.view.own')): ?>
  <div class="col-lg-5">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white fw-semibold">Aktivitas Terbaru</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($d['activities'] as $a): ?>
          <li class="list-group-item">
            <div><?= e(activity_label($a['aksi'])) ?></div>
            <small class="text-muted"><?= e($a['pengguna'] ?? 'Tidak dikenal') ?> &middot; <?= e(fmt_datetime($a['created_at'])) ?></small>
          </li>
        <?php endforeach; ?>
        <?php if (!$d['activities']): ?>
          <li class="list-group-item"><div class="empty-state py-4"><i class="bi bi-clock-history"></i><p class="mb-0">Belum ada aktivitas.</p></div></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
  <?php endif; ?>
</div>
