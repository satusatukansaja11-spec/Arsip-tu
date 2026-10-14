<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>403 - Akses Ditolak</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">
<div class="text-center p-4">
  <h1 class="display-4 text-primary">403</h1>
  <p class="lead">Anda tidak memiliki izin untuk mengakses halaman ini.</p>
  <a class="btn btn-primary" href="<?= htmlspecialchars(url('dashboard'), ENT_QUOTES, 'UTF-8') ?>">Kembali ke Dashboard</a>
</div></body></html>
