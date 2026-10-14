<form method="post" action="<?= e(url('login')) ?>" class="needs-loading" autocomplete="off">
  <?= Csrf::field() ?>
  <div class="mb-3">
    <label for="username" class="form-label">Nama Pengguna</label>
    <div class="input-group">
      <span class="input-group-text"><i class="bi bi-person"></i></span>
      <input type="text" class="form-control" id="username" name="username" maxlength="50" required autofocus autocomplete="username">
    </div>
  </div>
  <div class="mb-3">
    <label for="password" class="form-label">Kata Sandi</label>
    <div class="input-group">
      <span class="input-group-text"><i class="bi bi-lock"></i></span>
      <input type="password" class="form-control" id="password" name="password" maxlength="200" required autocomplete="current-password">
    </div>
  </div>
  <button type="submit" class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right"></i> Masuk</button>
</form>
