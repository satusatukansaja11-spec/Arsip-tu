<?php
/** @var array $v @var array $errors @var array $employees @var array $categories @var array $exts @var int $maxMb */
$val = static fn(string $k): string => (string)($v[$k] ?? '');
$err = static fn(string $k): string => isset($errors[$k]) ? '<div class="invalid-feedback d-block">' . e($errors[$k]) . '</div>' : '';
$inv = static fn(string $k): string => isset($errors[$k]) ? ' is-invalid' : '';
$accept = '.' . implode(',.', $exts);
?>
<div class="d-flex align-items-center gap-2 mb-3">
  <h1 class="h4 mb-0">Unggah Dokumen</h1>
  <a class="btn btn-outline-secondary ms-auto" href="<?= e(url('arsip')) ?>"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger">Periksa kembali isian Anda. Ada <?= count($errors) ?> kolom yang perlu diperbaiki.
    <?php if (isset($errors['file'])): ?> Pilih ulang berkas setelah memperbaiki isian.<?php endif; ?></div>
<?php endif; ?>

<form method="post" action="<?= e(url('arsip/unggah')) ?>" enctype="multipart/form-data" class="card border-0 shadow-sm needs-loading">
  <?= Csrf::field() ?>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label" for="employee_id">Pegawai <span class="text-danger">*</span></label>
        <select class="form-select<?= $inv('employee_id') ?>" id="employee_id" name="employee_id" required>
          <option value="">Pilih pegawai</option>
          <?php foreach ($employees as $emp): ?>
            <option value="<?= (int)$emp['id'] ?>" <?= $val('employee_id') === (string)$emp['id'] ? 'selected' : '' ?>>
              <?= e($emp['nama_lengkap']) ?><?= $emp['nip'] ? ' (' . e($emp['nip']) . ')' : '' ?><?= $emp['status_aktif'] ? '' : ' - tidak aktif' ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?= $err('employee_id') ?>
      </div>
      <div class="col-md-6">
        <label class="form-label" for="category_id">Kategori Dokumen <span class="text-danger">*</span></label>
        <select class="form-select<?= $inv('category_id') ?>" id="category_id" name="category_id" required>
          <option value="">Pilih kategori</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $val('category_id') === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['nama']) ?></option>
          <?php endforeach; ?>
        </select>
        <?= $err('category_id') ?>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="nomor_dokumen">Nomor Dokumen</label>
        <input type="text" class="form-control<?= $inv('nomor_dokumen') ?>" id="nomor_dokumen" name="nomor_dokumen" maxlength="100" value="<?= e($val('nomor_dokumen')) ?>">
        <?= $err('nomor_dokumen') ?>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="tanggal_dokumen">Tanggal Dokumen</label>
        <input type="date" class="form-control<?= $inv('tanggal_dokumen') ?>" id="tanggal_dokumen" name="tanggal_dokumen" max="<?= e(date('Y-m-d')) ?>" value="<?= e($val('tanggal_dokumen')) ?>">
        <?= $err('tanggal_dokumen') ?>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="tahun">Tahun Dokumen</label>
        <input type="number" class="form-control<?= $inv('tahun') ?>" id="tahun" name="tahun" min="1950" max="<?= (int)date('Y') + 1 ?>" value="<?= e($val('tahun')) ?>">
        <div class="form-text">Kosongkan untuk mengambil dari tanggal dokumen.</div>
        <?= $err('tahun') ?>
      </div>
      <div class="col-12">
        <label class="form-label" for="keterangan">Keterangan</label>
        <input type="text" class="form-control<?= $inv('keterangan') ?>" id="keterangan" name="keterangan" maxlength="255" value="<?= e($val('keterangan')) ?>">
        <?= $err('keterangan') ?>
      </div>
      <div class="col-12">
        <label class="form-label" for="file">Berkas <span class="text-danger">*</span></label>
        <input type="file" class="form-control<?= $inv('file') ?>" id="file" name="file" accept="<?= e($accept) ?>" required>
        <div class="form-text">Format: <?= e(strtoupper(implode(', ', $exts))) ?>. Ukuran maksimal <?= (int)$maxMb ?> MB.</div>
        <?= $err('file') ?>
      </div>
    </div>
  </div>
  <div class="card-footer bg-white d-flex gap-2">
    <button type="submit" class="btn btn-primary"><i class="bi bi-cloud-upload"></i> Unggah</button>
    <a class="btn btn-outline-secondary" href="<?= e(url('arsip')) ?>">Batal</a>
  </div>
</form>
