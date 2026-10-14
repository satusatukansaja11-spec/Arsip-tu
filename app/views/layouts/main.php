<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Arsip Pegawai TU') ?> - <?= e(setting('nama_aplikasi', 'Arsip Pegawai TU')) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">
</head>
<body>
<?php
$user = Auth::user();
$current = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$menu = [
    ['dashboard', 'Dashboard',        'bi-speedometer2', 'dashboard.view'],
    ['pegawai',   'Data Pegawai',     'bi-people',       'employee.view'],
    ['arsip',     'Arsip Dokumen',    'bi-folder2-open', 'document.view'],
    ['laporan',   'Laporan',          'bi-file-earmark-text', 'report.view'],
    ['log',       'Riwayat Aktivitas','bi-clock-history','log.view.all'],
    ['pengguna',  'Kelola Akun',      'bi-person-gear',  'user.manage'],
    ['backup',    'Backup',           'bi-hdd',          'backup.manage'],
    ['pengaturan','Pengaturan',       'bi-gear',         'settings.manage'],
];
?>
<div class="app-shell">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <i class="bi bi-archive-fill"></i>
      <div>
        <div class="fw-bold"><?= e(setting('nama_aplikasi', 'Arsip Pegawai TU')) ?></div>
        <small><?= e(setting('nama_sekolah', 'MTsN 1 Alor')) ?></small>
      </div>
    </div>
    <nav class="nav flex-column">
      <?php foreach ($menu as [$path, $label, $icon, $perm]): ?>
        <?php if (Auth::can($perm)): ?>
          <?php
            $rel = $current;
            $b = base_path_url();
            if ($b !== '' && str_starts_with($rel, $b)) { $rel = substr($rel, strlen($b)); }
            $rel = '/' . trim($rel, '/');
            $active = $rel === '/' . $path || str_starts_with($rel, '/' . $path . '/');
          ?>
          <?php if (in_array($path, ['dashboard', 'pegawai'], true)): ?>
            <a class="nav-link <?= $active ? 'active' : '' ?>" href="<?= e(url($path)) ?>"><i class="bi <?= e($icon) ?>"></i> <?= e($label) ?></a>
          <?php else: ?>
            <span class="nav-link disabled" title="Tersedia pada tahap berikutnya"><i class="bi <?= e($icon) ?>"></i> <?= e($label) ?></span>
          <?php endif; ?>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>
  </aside>

  <div class="main-area">
    <header class="topbar">
      <button class="btn btn-light d-lg-none" type="button" id="sidebarToggle" aria-label="Buka menu"><i class="bi bi-list"></i></button>
      <div class="ms-auto dropdown">
        <button class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown" type="button">
          <i class="bi bi-person-circle"></i> <?= e($user['nama_lengkap'] ?? '') ?>
          <span class="badge text-bg-primary ms-1"><?= e($user['role_nama'] ?? '') ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="<?= e(url('password')) ?>"><i class="bi bi-key"></i> Ubah Kata Sandi</a></li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <form method="post" action="<?= e(url('logout')) ?>">
              <?= Csrf::field() ?>
              <button class="dropdown-item" type="submit"><i class="bi bi-box-arrow-right"></i> Keluar</button>
            </form>
          </li>
        </ul>
      </div>
    </header>

    <main class="content">
      <?php foreach (pull_flash() as [$type, $msg]): ?>
        <div class="alert alert-<?= e($type) ?> alert-dismissible fade show" role="alert">
          <?= e($msg) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
      <?php endforeach; ?>
      <?= $content ?>
    </main>
    <footer class="footer-note">&copy; <?= date('Y') ?> <?= e(setting('nama_sekolah', 'MTsN 1 Alor')) ?></footer>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
<?php foreach (($extraScripts ?? []) as $src): ?>
<script src="<?= e($src) ?>"></script>
<?php endforeach; ?>
</body>
</html>
