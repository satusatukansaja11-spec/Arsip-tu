<?php
/** @var array $emp @var bool $sensitive @var array $documents @var array $completeness @var array $history */
$statusLabel = ['PNS' => 'PNS', 'PPPK' => 'PPPK', 'HONORER' => 'Honorer'];
$jk = ['L' => 'Laki-laki', 'P' => 'Perempuan'];
$row = static function (string $label, ?string $value): string {
    return '<dt class="col-sm-4">' . e($label) . '</dt><dd class="col-sm-8">' . e(($value !== null && $value !== '') ? $value : '-') . '</dd>';
};
$statusBadgeMap = [
    'lengkap'       => ['success', 'Lengkap'],
    'belum'         => ['danger', 'Belum ada'],
    'tersedia'      => ['info', 'Tersedia (tidak wajib)'],
    'tidak_berlaku' => ['secondary', 'Tidak berlaku'],
];
$pct = (int)$completeness['persen'];
$barColor = $pct >= 100 ? 'success' : ($pct >= 50 ? 'warning' : 'danger');
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <div>
    <h1 class="h4 mb-0"><?= e($emp['nama_lengkap']) ?></h1>
    <small class="text-muted">
      <span class="badge text-bg-primary"><?= e($statusLabel[$emp['status_kepegawaian']] ?? $emp['status_kepegawaian']) ?></span>
      <?= $emp['status_aktif'] ? '<span class="badge text-bg-success">Aktif</span>' : '<span class="badge text-bg-light border">Tidak aktif</span>' ?>
      <?php if ($emp['jabatan']): ?> &middot; <?= e($emp['jabatan']) ?><?php endif; ?>
    </small>
  </div>
  <div class="ms-auto d-flex flex-wrap gap-2 no-print">
    <a class="btn btn-outline-secondary" href="<?= e(url('pegawai')) ?>"><i class="bi bi-arrow-left"></i> Daftar</a>
    <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Cetak</button>
    <?php if (Auth::can('employee.update')): ?>
      <a class="btn btn-primary" href="<?= e(url('pegawai/ubah?id=' . (int)$emp['id'])) ?>"><i class="bi bi-pencil"></i> Ubah</a>
    <?php endif; ?>
    <?php if (Auth::can('employee.archive')): ?>
      <form method="post" action="<?= e(url('pegawai/arsipkan')) ?>" data-confirm="Arsipkan pegawai ini? Pegawai tidak lagi tampil di daftar, tetapi data dan dokumennya tetap tersimpan.">
        <?= Csrf::field() ?>
        <input type="hidden" name="id" value="<?= (int)$emp['id'] ?>">
        <button type="submit" class="btn btn-outline-danger"><i class="bi bi-archive"></i> Arsipkan</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-header bg-white">
    <ul class="nav nav-tabs card-header-tabs" role="tablist">
      <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-profil" type="button" role="tab">Profil Pegawai</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-kepegawaian" type="button" role="tab">Data Kepegawaian</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-dokumen" type="button" role="tab">Daftar Dokumen <span class="badge text-bg-light border"><?= count($documents) ?></span></button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-riwayat" type="button" role="tab">Riwayat Perubahan</button></li>
      <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-kelengkapan" type="button" role="tab">Kelengkapan Arsip</button></li>
    </ul>
  </div>
  <div class="card-body tab-content">

    <div class="tab-pane fade show active" id="tab-profil" role="tabpanel">
      <dl class="row mb-0">
        <?= $row('Nama lengkap', $emp['nama_lengkap']) ?>
        <?= $row('Jenis kelamin', $jk[$emp['jenis_kelamin'] ?? ''] ?? null) ?>
        <?= $row('Tempat lahir', $emp['tempat_lahir']) ?>
        <?php if ($sensitive): ?>
          <?= $row('Tanggal lahir', fmt_date($emp['tanggal_lahir'])) ?>
          <?= $row('NIK', $emp['nik']) ?>
          <?= $row('Telepon', $emp['telepon']) ?>
          <?= $row('Email', $emp['email']) ?>
          <?= $row('Alamat', $emp['alamat']) ?>
        <?php else: ?>
          <dt class="col-sm-4">Data pribadi lain</dt>
          <dd class="col-sm-8 text-muted">Disembunyikan sesuai hak akses.</dd>
        <?php endif; ?>
      </dl>
    </div>

    <div class="tab-pane fade" id="tab-kepegawaian" role="tabpanel">
      <dl class="row mb-0">
        <?= $row('NIP', $emp['nip']) ?>
        <?= $row('Status kepegawaian', $statusLabel[$emp['status_kepegawaian']] ?? $emp['status_kepegawaian']) ?>
        <?= $row('Pangkat / golongan', $emp['pangkat_golongan']) ?>
        <?= $row('Jabatan', $emp['jabatan']) ?>
        <?= $row('Unit kerja', $emp['unit_kerja']) ?>
        <?= $row('Pendidikan terakhir', $emp['pendidikan_terakhir']) ?>
        <?= $row('Tanggal mulai bekerja', fmt_date($emp['tanggal_mulai'])) ?>
        <?= $row('Status', $emp['status_aktif'] ? 'Aktif' : 'Tidak aktif') ?>
        <?php if ($sensitive): ?>
          <?= $row('Catatan administrasi', $emp['catatan']) ?>
        <?php endif; ?>
      </dl>
    </div>

    <div class="tab-pane fade" id="tab-dokumen" role="tabpanel">
      <?php if (!Auth::can('document.view')): ?>
        <div class="empty-state"><i class="bi bi-lock"></i><p class="mb-0">Anda tidak memiliki akses ke daftar dokumen.</p></div>
      <?php elseif (!$documents): ?>
        <div class="empty-state"><i class="bi bi-folder2-open"></i><p class="mb-0">Belum ada dokumen untuk pegawai ini.</p></div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Kategori</th><th>Nomor</th><th>Tanggal</th><th>Keterangan</th><th>Diunggah</th></tr></thead>
            <tbody>
              <?php foreach ($documents as $d): ?>
                <tr>
                  <td><?= e($d['kategori']) ?></td>
                  <td><?= e($d['nomor_dokumen'] ?: '-') ?></td>
                  <td><?= e(fmt_date($d['tanggal_dokumen'])) ?></td>
                  <td><?= e($d['keterangan'] ?: '-') ?></td>
                  <td><?= e(fmt_datetime($d['created_at'])) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <div class="tab-pane fade" id="tab-riwayat" role="tabpanel">
      <?php if (!$history): ?>
        <div class="empty-state"><i class="bi bi-clock-history"></i><p class="mb-0">Belum ada riwayat perubahan.</p></div>
      <?php else: ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($history as $h): ?>
            <li class="list-group-item px-0">
              <div><?= e(activity_label($h['aksi'])) ?><?php if ($h['detail']): ?> <small class="text-muted">(<?= e($h['detail']) ?>)</small><?php endif; ?></div>
              <small class="text-muted"><?= e($h['pengguna'] ?? 'Tidak dikenal') ?> &middot; <?= e(fmt_datetime($h['created_at'])) ?></small>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <div class="tab-pane fade" id="tab-kelengkapan" role="tabpanel">
      <div class="mb-3">
        <div class="d-flex justify-content-between small mb-1">
          <span><?= (int)$completeness['terpenuhi'] ?> dari <?= (int)$completeness['wajib'] ?> dokumen wajib terpenuhi</span>
          <strong><?= $pct ?>%</strong>
        </div>
        <div class="progress" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
          <div class="progress-bar bg-<?= e($barColor) ?>" style="width: <?= $pct ?>%"></div>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead class="table-light"><tr><th>Kategori Dokumen</th><th>Status</th><th>Jumlah</th></tr></thead>
          <tbody>
            <?php foreach ($completeness['items'] as $it): ?>
              <?php [$color, $text] = $statusBadgeMap[$it['status']]; ?>
              <tr>
                <td><?= e($it['nama']) ?></td>
                <td><span class="badge text-bg-<?= e($color) ?>"><?= e($text) ?></span></td>
                <td><?= (int)$it['jumlah'] ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p class="small text-muted mt-2 mb-0">Dokumen yang tidak diwajibkan untuk status ini ditandai "Tidak berlaku", bukan hilang.</p>
    </div>

  </div>
</div>
