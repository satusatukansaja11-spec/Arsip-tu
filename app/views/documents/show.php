<?php
/** @var array $doc @var array $versions @var array $history */
$canDownload = Auth::can('document.download');
$isPdf = $doc['mime_type'] === 'application/pdf';
$isImage = str_starts_with((string)$doc['mime_type'], 'image/');
$id = (int)$doc['id'];
$row = static function (string $label, ?string $value): string {
    return '<dt class="col-sm-4">' . e($label) . '</dt><dd class="col-sm-8">' . e(($value !== null && $value !== '') ? $value : '-') . '</dd>';
};
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <div>
    <h1 class="h4 mb-0"><?= e($doc['kategori']) ?></h1>
    <small class="text-muted">
      <a href="<?= e(url('pegawai/detail?id=' . (int)$doc['employee_id'])) ?>"><?= e($doc['nama_lengkap']) ?></a>
      <?php if ($doc['nomor_dokumen']): ?> &middot; <?= e($doc['nomor_dokumen']) ?><?php endif; ?>
    </small>
  </div>
  <div class="ms-auto d-flex flex-wrap gap-2 no-print">
    <a class="btn btn-outline-secondary" href="<?= e(url('arsip')) ?>"><i class="bi bi-arrow-left"></i> Daftar</a>
    <?php if ($canDownload): ?>
      <a class="btn btn-outline-primary" target="_blank" rel="noopener" href="<?= e(url('arsip/lihat?id=' . $id)) ?>"><i class="bi bi-box-arrow-up-right"></i> Lihat</a>
      <a class="btn btn-primary" href="<?= e(url('arsip/unduh?id=' . $id)) ?>"><i class="bi bi-download"></i> Unduh</a>
    <?php endif; ?>
    <?php if (Auth::can('document.update')): ?>
      <a class="btn btn-outline-secondary" href="<?= e(url('arsip/ubah?id=' . $id)) ?>"><i class="bi bi-pencil"></i> Ubah Data</a>
      <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#replaceBox"><i class="bi bi-arrow-repeat"></i> Ganti Berkas</button>
    <?php endif; ?>
    <?php if (Auth::can('document.archive')): ?>
      <form method="post" action="<?= e(url('arsip/arsipkan')) ?>" data-confirm="Arsipkan dokumen ini? Dokumen tidak lagi tampil di daftar, tetapi berkas fisiknya tetap tersimpan.">
        <?= Csrf::field() ?>
        <input type="hidden" name="id" value="<?= $id ?>">
        <button type="submit" class="btn btn-outline-danger"><i class="bi bi-archive"></i> Arsipkan</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php if (Auth::can('document.update')): ?>
<div class="collapse mb-3 no-print" id="replaceBox">
  <form method="post" action="<?= e(url('arsip/ganti')) ?>" enctype="multipart/form-data" class="card border-0 shadow-sm needs-loading">
    <?= Csrf::field() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <div class="card-body">
      <h2 class="h6">Ganti Berkas</h2>
      <p class="small text-muted">Berkas saat ini disimpan sebagai versi lama dan tetap dapat diakses.</p>
      <div class="input-group">
        <input type="file" class="form-control" name="file" accept=".<?= e(implode(',.', UploadService::allowedExtensions())) ?>" required aria-label="Berkas pengganti">
        <button class="btn btn-primary" type="submit"><i class="bi bi-cloud-upload"></i> Unggah &amp; Ganti</button>
      </div>
      <div class="form-text">Maksimal <?= (int)(UploadService::maxBytes() / 1048576) ?> MB.</div>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white fw-semibold">Data Dokumen</div>
      <div class="card-body">
        <dl class="row mb-0">
          <?= $row('Pegawai', $doc['nama_lengkap']) ?>
          <?= $row('Kategori', $doc['kategori']) ?>
          <?= $row('Nomor dokumen', $doc['nomor_dokumen']) ?>
          <?= $row('Tanggal dokumen', fmt_date($doc['tanggal_dokumen'])) ?>
          <?= $row('Tahun', $doc['tahun'] !== null ? (string)$doc['tahun'] : null) ?>
          <?= $row('Keterangan', $doc['keterangan']) ?>
          <?= $row('Nama berkas', $doc['nama_asli']) ?>
          <?= $row('Jenis / ukuran', ($isPdf ? 'PDF' : 'Gambar') . ' / ' . UploadService::humanSize((int)$doc['ukuran_byte'])) ?>
          <?= $row('Diunggah oleh', $doc['pengunggah']) ?>
          <?= $row('Tanggal unggah', fmt_datetime($doc['created_at'])) ?>
          <?= $row('Terakhir diubah', fmt_datetime($doc['updated_at'])) ?>
          <dt class="col-sm-4">Sidik jari (SHA-256)</dt>
          <dd class="col-sm-8"><code class="small text-break"><?= e($doc['hash_sha256']) ?></code></dd>
        </dl>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white fw-semibold">Pratinjau</div>
      <div class="card-body">
        <?php if (!$canDownload): ?>
          <div class="empty-state"><i class="bi bi-lock"></i><p class="mb-0">Anda tidak memiliki akses ke isi dokumen.</p></div>
        <?php elseif ($isImage): ?>
          <img class="img-fluid border rounded" src="<?= e(url('arsip/lihat?id=' . $id)) ?>" alt="Pratinjau dokumen">
        <?php elseif ($isPdf): ?>
          <iframe src="<?= e(url('arsip/lihat?id=' . $id)) ?>" title="Pratinjau PDF" style="width:100%;height:560px;border:1px solid #dee2e6;border-radius:.375rem"></iframe>
          <p class="small text-muted mt-2 mb-0">Jika pratinjau tidak tampil, gunakan tombol Lihat atau Unduh.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm mt-3">
  <div class="card-header bg-white fw-semibold">Riwayat Versi Berkas</div>
  <?php if (!$versions): ?>
    <div class="card-body"><div class="empty-state py-3"><i class="bi bi-layers"></i><p class="mb-0">Berkas belum pernah diganti. Hanya ada satu versi.</p></div></div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light"><tr><th>Versi</th><th>Nama Berkas</th><th>Ukuran</th><th>Diganti oleh</th><th>Tanggal Diganti</th><th class="no-print text-end">Aksi</th></tr></thead>
        <tbody>
          <tr class="table-primary">
            <td><span class="badge text-bg-primary">Saat ini</span></td>
            <td><?= e($doc['nama_asli']) ?></td>
            <td><?= e(UploadService::humanSize((int)$doc['ukuran_byte'])) ?></td>
            <td colspan="3"></td>
          </tr>
          <?php foreach ($versions as $ver): ?>
            <tr>
              <td>Versi <?= (int)$ver['versi'] ?></td>
              <td><?= e($ver['nama_asli']) ?></td>
              <td><?= e(UploadService::humanSize((int)$ver['ukuran_byte'])) ?></td>
              <td><?= e($ver['pengganti'] ?? '-') ?></td>
              <td><?= e(fmt_datetime($ver['created_at'])) ?></td>
              <td class="no-print text-end text-nowrap">
                <?php if ($canDownload): ?>
                  <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="<?= e(url('arsip/lihat?id=' . $id . '&v=' . (int)$ver['versi'])) ?>" title="Lihat"><i class="bi bi-eye"></i></a>
                  <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('arsip/unduh?id=' . $id . '&v=' . (int)$ver['versi'])) ?>" title="Unduh"><i class="bi bi-download"></i></a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php if (Auth::can('document.update')): ?>
<div class="card border-0 shadow-sm mt-3 no-print">
  <div class="card-header bg-white fw-semibold">Riwayat Aktivitas Dokumen</div>
  <?php if (!$history): ?>
    <div class="card-body"><div class="empty-state py-3"><i class="bi bi-clock-history"></i><p class="mb-0">Belum ada aktivitas.</p></div></div>
  <?php else: ?>
    <ul class="list-group list-group-flush">
      <?php foreach ($history as $h): ?>
        <li class="list-group-item">
          <div><?= e(activity_label($h['aksi'])) ?><?php if ($h['detail']): ?> <small class="text-muted">(<?= e($h['detail']) ?>)</small><?php endif; ?></div>
          <small class="text-muted"><?= e($h['pengguna'] ?? 'Tidak dikenal') ?> &middot; <?= e(fmt_datetime($h['created_at'])) ?></small>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
<?php endif; ?>
