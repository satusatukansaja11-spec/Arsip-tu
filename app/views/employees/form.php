<?php
/** @var string $mode @var array $v @var array $errors @var ?int $id */
$val = static fn(string $k): string => (string)($v[$k] ?? '');
$err = static fn(string $k): string => isset($errors[$k]) ? '<div class="invalid-feedback d-block">' . e($errors[$k]) . '</div>' : '';
$inv = static fn(string $k): string => isset($errors[$k]) ? ' is-invalid' : '';
$action = $mode === 'create' ? 'pegawai/tambah' : 'pegawai/ubah';
?>
<div class="d-flex align-items-center gap-2 mb-3">
  <h1 class="h4 mb-0"><?= $mode === 'create' ? 'Tambah Pegawai' : 'Ubah Data Pegawai' ?></h1>
  <a class="btn btn-outline-secondary ms-auto" href="<?= e($mode === 'edit' ? url('pegawai/detail?id=' . (int)$id) : url('pegawai')) ?>"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger">Periksa kembali isian Anda. Ada <?= count($errors) ?> kolom yang perlu diperbaiki.</div>
<?php endif; ?>

<form method="post" action="<?= e(url($action)) ?>" class="card border-0 shadow-sm needs-loading" novalidate>
  <?= Csrf::field() ?>
  <?php if ($mode === 'edit'): ?><input type="hidden" name="id" value="<?= (int)$id ?>"><?php endif; ?>
  <div class="card-body">
    <h2 class="h6 text-primary mb-3">Identitas</h2>
    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <label class="form-label" for="nama_lengkap">Nama Lengkap <span class="text-danger">*</span></label>
        <input type="text" class="form-control<?= $inv('nama_lengkap') ?>" id="nama_lengkap" name="nama_lengkap" maxlength="120" required value="<?= e($val('nama_lengkap')) ?>">
        <?= $err('nama_lengkap') ?>
      </div>
      <div class="col-md-3">
        <label class="form-label" for="nip">NIP</label>
        <input type="text" class="form-control<?= $inv('nip') ?>" id="nip" name="nip" maxlength="30" inputmode="numeric" value="<?= e($val('nip')) ?>">
        <?= $err('nip') ?>
      </div>
      <div class="col-md-3">
        <label class="form-label" for="nik">NIK</label>
        <input type="text" class="form-control<?= $inv('nik') ?>" id="nik" name="nik" maxlength="20" inputmode="numeric" value="<?= e($val('nik')) ?>">
        <?= $err('nik') ?>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="tempat_lahir">Tempat Lahir</label>
        <input type="text" class="form-control<?= $inv('tempat_lahir') ?>" id="tempat_lahir" name="tempat_lahir" maxlength="80" value="<?= e($val('tempat_lahir')) ?>">
        <?= $err('tempat_lahir') ?>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="tanggal_lahir">Tanggal Lahir</label>
        <input type="date" class="form-control<?= $inv('tanggal_lahir') ?>" id="tanggal_lahir" name="tanggal_lahir" value="<?= e($val('tanggal_lahir')) ?>">
        <?= $err('tanggal_lahir') ?>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="jenis_kelamin">Jenis Kelamin</label>
        <select class="form-select<?= $inv('jenis_kelamin') ?>" id="jenis_kelamin" name="jenis_kelamin">
          <option value="">-</option>
          <option value="L" <?= $val('jenis_kelamin') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
          <option value="P" <?= $val('jenis_kelamin') === 'P' ? 'selected' : '' ?>>Perempuan</option>
        </select>
        <?= $err('jenis_kelamin') ?>
      </div>
    </div>

    <h2 class="h6 text-primary mb-3">Kepegawaian</h2>
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <label class="form-label" for="status_kepegawaian">Status Kepegawaian <span class="text-danger">*</span></label>
        <select class="form-select<?= $inv('status_kepegawaian') ?>" id="status_kepegawaian" name="status_kepegawaian" required>
          <option value="">Pilih status</option>
          <option value="PNS" <?= $val('status_kepegawaian') === 'PNS' ? 'selected' : '' ?>>PNS</option>
          <option value="PPPK" <?= $val('status_kepegawaian') === 'PPPK' ? 'selected' : '' ?>>PPPK</option>
          <option value="HONORER" <?= $val('status_kepegawaian') === 'HONORER' ? 'selected' : '' ?>>Honorer</option>
        </select>
        <?= $err('status_kepegawaian') ?>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="pangkat_golongan">Pangkat / Golongan</label>
        <input type="text" class="form-control<?= $inv('pangkat_golongan') ?>" id="pangkat_golongan" name="pangkat_golongan" maxlength="60" value="<?= e($val('pangkat_golongan')) ?>">
        <?= $err('pangkat_golongan') ?>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="pendidikan_terakhir">Pendidikan Terakhir</label>
        <select class="form-select<?= $inv('pendidikan_terakhir') ?>" id="pendidikan_terakhir" name="pendidikan_terakhir">
          <option value="">-</option>
          <?php foreach (Employee::EDUCATION as $p): ?>
            <option value="<?= e($p) ?>" <?= $val('pendidikan_terakhir') === $p ? 'selected' : '' ?>><?= e($p) ?></option>
          <?php endforeach; ?>
        </select>
        <?= $err('pendidikan_terakhir') ?>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="jabatan">Jabatan</label>
        <input type="text" class="form-control<?= $inv('jabatan') ?>" id="jabatan" name="jabatan" maxlength="100" value="<?= e($val('jabatan')) ?>">
        <?= $err('jabatan') ?>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="unit_kerja">Unit Kerja</label>
        <input type="text" class="form-control<?= $inv('unit_kerja') ?>" id="unit_kerja" name="unit_kerja" maxlength="100" value="<?= e($val('unit_kerja')) ?>">
        <?= $err('unit_kerja') ?>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="tanggal_mulai">Tanggal Mulai Bekerja</label>
        <input type="date" class="form-control<?= $inv('tanggal_mulai') ?>" id="tanggal_mulai" name="tanggal_mulai" value="<?= e($val('tanggal_mulai')) ?>">
        <?= $err('tanggal_mulai') ?>
      </div>
    </div>

    <h2 class="h6 text-primary mb-3">Kontak</h2>
    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <label class="form-label" for="telepon">Nomor Telepon</label>
        <input type="text" class="form-control<?= $inv('telepon') ?>" id="telepon" name="telepon" maxlength="25" inputmode="tel" value="<?= e($val('telepon')) ?>">
        <?= $err('telepon') ?>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="email">Email</label>
        <input type="email" class="form-control<?= $inv('email') ?>" id="email" name="email" maxlength="150" value="<?= e($val('email')) ?>">
        <?= $err('email') ?>
      </div>
      <div class="col-12">
        <label class="form-label" for="alamat">Alamat</label>
        <textarea class="form-control<?= $inv('alamat') ?>" id="alamat" name="alamat" rows="2" maxlength="500"><?= e($val('alamat')) ?></textarea>
        <?= $err('alamat') ?>
      </div>
    </div>

    <h2 class="h6 text-primary mb-3">Lainnya</h2>
    <div class="row g-3">
      <div class="col-12">
        <label class="form-label" for="catatan">Catatan Administrasi</label>
        <textarea class="form-control<?= $inv('catatan') ?>" id="catatan" name="catatan" rows="2" maxlength="1000"><?= e($val('catatan')) ?></textarea>
        <?= $err('catatan') ?>
      </div>
      <div class="col-12">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" role="switch" id="status_aktif" name="status_aktif" value="1" <?= $val('status_aktif') === '1' ? 'checked' : '' ?>>
          <label class="form-check-label" for="status_aktif">Pegawai aktif</label>
        </div>
      </div>
    </div>
  </div>
  <div class="card-footer bg-white d-flex gap-2">
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan</button>
    <a class="btn btn-outline-secondary" href="<?= e($mode === 'edit' ? url('pegawai/detail?id=' . (int)$id) : url('pegawai')) ?>">Batal</a>
  </div>
</form>
