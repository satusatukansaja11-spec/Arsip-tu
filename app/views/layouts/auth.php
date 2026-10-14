<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Masuk') ?> - <?= e(setting('nama_aplikasi', 'Arsip Pegawai TU')) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">
</head>
<body class="auth-body">
  <div class="auth-card card shadow-sm">
    <div class="card-body p-4">
      <div class="text-center mb-4">
        <i class="bi bi-archive-fill auth-icon"></i>
        <h1 class="h4 mt-2 mb-0"><?= e(setting('nama_aplikasi', 'Arsip Pegawai TU')) ?></h1>
        <div class="text-muted"><?= e(setting('nama_sekolah', 'MTsN 1 Alor')) ?></div>
      </div>
      <?php foreach (pull_flash() as [$type, $msg]): ?>
        <div class="alert alert-<?= e($type) ?>" role="alert"><?= e($msg) ?></div>
      <?php endforeach; ?>
      <?= $content ?>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
