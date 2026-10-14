<?php
/** @var string $q @var string $kategori @var string $tahun @var string $kel @var bool $canEmp @var bool $canDoc
 *  @var ?array $employees @var ?array $documents @var array $categories @var bool $searched */
?>
<div class="mb-3">
  <h1 class="h4 mb-0">Pencarian</h1>
  <small class="text-muted">Cari pegawai (nama, NIP, status kelengkapan) dan dokumen (nomor, kategori, tahun).</small>
</div>

<form method="get" action="<?= e(url('cari')) ?>" class="card border-0 shadow-sm mb-3">
  <div class="card-body">
    <div class="row g-2">
      <div class="col-md-5">
        <label class="form-label small text-muted mb-0" for="s-q">Kata kunci</label>
        <input type="search" class="form-control" id="s-q" name="q" maxlength="100" value="<?= e($q) ?>" placeholder="Nama pegawai, NIP, atau nomor dokumen" autofocus>
      </div>
      <?php if ($canDoc): ?>
        <div class="col-md-3">
          <label class="form-label small text-muted mb-0" for="s-kat">Kategori dokumen</label>
          <select class="form-select" id="s-kat" name="kategori">
            <option value="">Semua kategori</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= $kategori === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['nama']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small text-muted mb-0" for="s-th">Tahun dokumen</label>
          <input type="number" class="form-control" id="s-th" name="tahun" min="1950" max="2100" value="<?= e($tahun) ?>">
        </div>
      <?php endif; ?>
      <?php if ($canEmp): ?>
        <div class="col-6 col-md-2">
          <label class="form-label small text-muted mb-0" for="s-kel">Kelengkapan arsip</label>
          <select class="form-select" id="s-kel" name="kelengkapan">
            <option value="">Semua</option>
            <option value="lengkap" <?= $kel === 'lengkap' ? 'selected' : '' ?>>Lengkap</option>
            <option value="belum" <?= $kel === 'belum' ? 'selected' : '' ?>>Belum lengkap</option>
          </select>
        </div>
      <?php endif; ?>
    </div>
    <div class="mt-2 d-flex gap-2">
      <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Cari</button>
      <?php if ($searched): ?><a class="btn btn-outline-secondary" href="<?= e(url('cari')) ?>">Reset</a><?php endif; ?>
    </div>
    <?php if ($kel !== ''): ?>
      <div class="form-text">Filter kelengkapan hanya berlaku untuk pegawai, sehingga hasil dokumen tidak ditampilkan.</div>
    <?php endif; ?>
  </div>
</form>

<?php if (!$searched): ?>
  <div class="empty-state"><i class="bi bi-search"></i><p class="mb-0">Ketik kata kunci atau pilih filter untuk mulai mencari.</p></div>
<?php else: ?>

  <?php if ($employees !== null): ?>
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white d-flex align-items-center">
        <span class="fw-semibold">Pegawai</span>
        <span class="badge text-bg-light border ms-2"><?= (int)$employees['total'] ?></span>
        <?php if ($employees['total'] > count($employees['rows']) && $q !== ''): ?>
          <a class="ms-auto small" href="<?= e(query_url('pegawai', ['q' => $q])) ?>">Lihat semua di Data Pegawai</a>
        <?php endif; ?>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light"><tr><th>Nama</th><th>NIP</th><th>Jabatan</th><th>Status</th><th>Kelengkapan Arsip</th><th>Dokumen Wajib Belum Ada</th></tr></thead>
          <tbody>
            <?php foreach ($employees['rows'] as $r): ?>
              <tr>
                <td>
                  <a class="fw-semibold text-decoration-none" href="<?= e(url('pegawai/detail?id=' . (int)$r['id'])) ?>"><?= e($r['nama_lengkap']) ?></a>
                  <?php if (!$r['status_aktif']): ?><span class="badge text-bg-light border">Tidak aktif</span><?php endif; ?>
                </td>
                <td><?= e($r['nip'] ?: '-') ?></td>
                <td><?= e($r['jabatan'] ?: '-') ?></td>
                <td><?= e($r['status_kepegawaian'] === 'HONORER' ? 'Honorer' : $r['status_kepegawaian']) ?></td>
                <td>
                  <span class="badge text-bg-<?= $r['lengkap'] ? 'success' : 'warning' ?>"><?= $r['lengkap'] ? 'Lengkap' : 'Belum lengkap' ?></span>
                  <small class="text-muted"><?= (int)$r['terpenuhi'] ?>/<?= (int)$r['wajib'] ?> (<?= (int)$r['persen'] ?>%)</small>
                </td>
                <td class="small"><?= e($r['kurang'] ?: '-') ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$employees['rows']): ?>
              <tr><td colspan="6"><div class="empty-state py-4"><i class="bi bi-people"></i><p class="mb-0">Tidak ada pegawai yang cocok.</p></div></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($documents !== null): ?>
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white d-flex align-items-center">
        <span class="fw-semibold">Dokumen</span>
        <span class="badge text-bg-light border ms-2"><?= (int)$documents['total'] ?></span>
        <?php if ($documents['total'] > count($documents['rows'])): ?>
          <a class="ms-auto small" href="<?= e(query_url('arsip', ['q' => $q, 'kategori' => $kategori, 'tahun' => $tahun])) ?>">Lihat semua di Arsip Dokumen</a>
        <?php endif; ?>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light"><tr><th>Pegawai</th><th>Kategori</th><th>Nomor</th><th>Tahun</th><th>Diunggah</th><th class="text-end">Aksi</th></tr></thead>
          <tbody>
            <?php foreach ($documents['rows'] as $r): ?>
              <tr>
                <td>
                  <a class="text-decoration-none" href="<?= e(url('pegawai/detail?id=' . (int)$r['employee_id'])) ?>"><?= e($r['nama_lengkap']) ?></a>
                  <?php if ($r['nip']): ?><div class="small text-muted"><?= e($r['nip']) ?></div><?php endif; ?>
                </td>
                <td><?= e($r['kategori']) ?></td>
                <td><?= e($r['nomor_dokumen'] ?: '-') ?></td>
                <td><?= e($r['tahun'] ?: '-') ?></td>
                <td class="text-nowrap"><?= e(fmt_datetime($r['created_at'])) ?></td>
                <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= e(url('arsip/detail?id=' . (int)$r['id'])) ?>" title="Detail"><i class="bi bi-eye"></i></a></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$documents['rows']): ?>
              <tr><td colspan="6"><div class="empty-state py-4"><i class="bi bi-folder2-open"></i><p class="mb-0">Tidak ada dokumen yang cocok.</p></div></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($employees === null && $documents === null): ?>
    <div class="empty-state"><i class="bi bi-lock"></i><p class="mb-0">Tidak ada hasil yang dapat ditampilkan untuk akun dan filter ini.</p></div>
  <?php endif; ?>
<?php endif; ?>
