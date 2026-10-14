<?php
/** @var array $f @var array $result @var array $categories @var ?array $filterEmployee */
$base = $f;
unset($base['page']);

$sortHead = static function (string $key, string $label) use ($f): string {
    $dir = ($f['sort'] === $key && $f['dir'] === 'asc') ? 'desc' : 'asc';
    $q = $f;
    unset($q['page']);
    $href = query_url('arsip', $q, ['sort' => $key, 'dir' => $dir]);
    $icon = $f['sort'] === $key ? ($f['dir'] === 'asc' ? ' <i class="bi bi-caret-up-fill"></i>' : ' <i class="bi bi-caret-down-fill"></i>') : '';
    return '<a href="' . e($href) . '">' . e($label) . $icon . '</a>';
};
$hasFilter = $f['q'] !== '' || $f['kategori'] !== '' || $f['pegawai'] !== '' || $f['tahun'] !== '' || $f['dari'] !== '' || $f['sampai'] !== '';
$canDownload = Auth::can('document.download');
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <div>
    <h1 class="h4 mb-0">Arsip Dokumen</h1>
    <small class="text-muted"><?= number_format($result['total'], 0, ',', '.') ?> dokumen ditemukan</small>
  </div>
  <div class="ms-auto d-flex flex-wrap gap-2 no-print">
    <?php if (Auth::can('document.upload')): ?>
      <a class="btn btn-primary" href="<?= e(url('arsip/unggah' . ($f['pegawai'] !== '' ? '?employee_id=' . (int)$f['pegawai'] : ''))) ?>"><i class="bi bi-cloud-upload"></i> Unggah Dokumen</a>
    <?php endif; ?>
  </div>
</div>

<?php if ($filterEmployee): ?>
  <div class="alert alert-info py-2 d-flex align-items-center no-print">
    <span>Menampilkan dokumen milik <strong><?= e($filterEmployee['nama_lengkap']) ?></strong>.</span>
    <a class="ms-auto btn btn-sm btn-outline-secondary" href="<?= e(query_url('arsip', $base, ['pegawai' => ''])) ?>">Tampilkan semua</a>
  </div>
<?php endif; ?>

<form method="get" action="<?= e(url('arsip')) ?>" class="card border-0 shadow-sm mb-3 no-print">
  <div class="card-body">
    <div class="row g-2">
      <div class="col-md-4">
        <input type="search" name="q" class="form-control" placeholder="Cari nama pegawai, NIP, atau nomor dokumen" maxlength="100" value="<?= e($f['q']) ?>">
      </div>
      <div class="col-md-3">
        <select name="kategori" class="form-select" aria-label="Kategori">
          <option value="">Semua kategori</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $f['kategori'] === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <input type="number" name="tahun" class="form-control" placeholder="Tahun dokumen" min="1950" max="2100" value="<?= e($f['tahun']) ?>" aria-label="Tahun dokumen">
      </div>
      <div class="col-6 col-md-3"></div>
      <div class="col-6 col-md-3">
        <label class="form-label small text-muted mb-0" for="dari">Diunggah dari</label>
        <input type="date" id="dari" name="dari" class="form-control" value="<?= e($f['dari']) ?>">
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label small text-muted mb-0" for="sampai">Sampai</label>
        <input type="date" id="sampai" name="sampai" class="form-control" value="<?= e($f['sampai']) ?>">
      </div>
    </div>
    <input type="hidden" name="pegawai" value="<?= e($f['pegawai']) ?>">
    <input type="hidden" name="sort" value="<?= e($f['sort']) ?>">
    <input type="hidden" name="dir" value="<?= e($f['dir']) ?>">
    <div class="mt-2 d-flex gap-2">
      <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Cari</button>
      <?php if ($hasFilter): ?>
        <a class="btn btn-outline-secondary" href="<?= e(url('arsip')) ?>">Reset</a>
      <?php endif; ?>
    </div>
  </div>
</form>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th><?= $sortHead('pegawai', 'Pegawai') ?></th>
          <th><?= $sortHead('kategori', 'Kategori') ?></th>
          <th><?= $sortHead('nomor', 'Nomor') ?></th>
          <th><?= $sortHead('tahun', 'Tahun') ?></th>
          <th>Berkas</th>
          <th><?= $sortHead('unggah', 'Diunggah') ?></th>
          <th class="no-print text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($result['rows'] as $r): ?>
          <?php $isPdf = $r['mime_type'] === 'application/pdf'; ?>
          <tr>
            <td>
              <a class="fw-semibold text-decoration-none" href="<?= e(url('pegawai/detail?id=' . (int)$r['employee_id'])) ?>"><?= e($r['nama_lengkap']) ?></a>
              <?php if ($r['nip']): ?><div class="small text-muted"><?= e($r['nip']) ?></div><?php endif; ?>
            </td>
            <td><?= e($r['kategori']) ?></td>
            <td><?= e($r['nomor_dokumen'] ?: '-') ?></td>
            <td><?= e($r['tahun'] ?: '-') ?></td>
            <td class="text-nowrap">
              <i class="bi <?= $isPdf ? 'bi-file-earmark-pdf text-danger' : 'bi-file-earmark-image text-primary' ?>"></i>
              <?= $isPdf ? 'PDF' : 'Gambar' ?> <span class="text-muted small">(<?= e(UploadService::humanSize((int)$r['ukuran_byte'])) ?>)</span>
            </td>
            <td class="text-nowrap"><?= e(fmt_datetime($r['created_at'])) ?></td>
            <td class="no-print text-end text-nowrap">
              <a class="btn btn-sm btn-outline-primary" href="<?= e(url('arsip/detail?id=' . (int)$r['id'])) ?>" title="Detail"><i class="bi bi-eye"></i></a>
              <?php if ($canDownload): ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('arsip/unduh?id=' . (int)$r['id'])) ?>" title="Unduh"><i class="bi bi-download"></i></a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$result['rows']): ?>
          <tr><td colspan="7">
            <div class="empty-state">
              <i class="bi bi-folder2-open"></i>
              <p class="mb-2"><?= $hasFilter ? 'Tidak ada dokumen yang cocok dengan pencarian.' : 'Belum ada dokumen yang diunggah.' ?></p>
              <?php if (!$hasFilter && Auth::can('document.upload')): ?>
                <a class="btn btn-primary btn-sm" href="<?= e(url('arsip/unggah')) ?>">Unggah dokumen pertama</a>
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
            $link = static fn(int $p): string => query_url('arsip', $base, ['page' => $p]);
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
