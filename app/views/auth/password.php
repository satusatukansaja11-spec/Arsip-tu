<div class="row justify-content-center">
  <div class="col-md-7 col-lg-5">
    <div class="card shadow-sm">
      <div class="card-body p-4">
        <h1 class="h5 mb-3"><i class="bi bi-key"></i> Ubah Kata Sandi</h1>
        <?php if (!empty($forced)): ?>
          <div class="alert alert-warning">Kata sandi Anda bersifat sementara. Ganti dengan kata sandi baru untuk melanjutkan.</div>
        <?php endif; ?>
        <form method="post" action="<?= e(url('password')) ?>" class="needs-loading" autocomplete="off">
          <?= Csrf::field() ?>
          <div class="mb-3">
            <label for="current_password" class="form-label">Kata Sandi Saat Ini</label>
            <input type="password" class="form-control" id="current_password" name="current_password" maxlength="200" required autocomplete="current-password">
          </div>
          <div class="mb-3">
            <label for="new_password" class="form-label">Kata Sandi Baru</label>
            <input type="password" class="form-control" id="new_password" name="new_password" maxlength="200" minlength="10" required autocomplete="new-password">
            <div class="form-text">Minimal 10 karakter, mengandung huruf dan angka.</div>
          </div>
          <div class="mb-3">
            <label for="confirm_password" class="form-label">Konfirmasi Kata Sandi Baru</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" maxlength="200" minlength="10" required autocomplete="new-password">
          </div>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan</button>
        </form>
      </div>
    </div>
  </div>
</div>
