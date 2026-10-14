<?php
declare(strict_types=1);

/**
 * Rute modul Laporan dan Pencarian. Dimuat dari routes/web.php
 * (variabel $router berasal dari public/index.php).
 */
$router->get('/laporan',        [ReportController::class, 'index'],  ['can:report.view']);
$router->get('/laporan/tampil', [ReportController::class, 'show'],   ['can:report.view']);
$router->get('/laporan/ekspor', [ReportController::class, 'export'], ['can:report.view']);

// Izin per bagian (pegawai / dokumen) diperiksa lagi di dalam SearchController
$router->get('/cari', [SearchController::class, 'index'], ['can:dashboard.view']);
