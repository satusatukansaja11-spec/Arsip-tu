<?php
declare(strict_types=1);

/**
 * Rute modul Arsip Dokumen. Dimuat dari routes/web.php (variabel $router berasal dari public/index.php).
 * Pemeriksaan login + izin dilakukan di server oleh middleware 'can:...'.
 */
$router->get('/arsip',           [DocumentController::class, 'index'],    ['can:document.view']);
$router->get('/arsip/unggah',    [DocumentController::class, 'create'],   ['can:document.upload']);
$router->post('/arsip/unggah',   [DocumentController::class, 'store'],    ['can:document.upload']);
$router->get('/arsip/detail',    [DocumentController::class, 'show'],     ['can:document.view']);
$router->get('/arsip/lihat',     [DocumentController::class, 'view'],     ['can:document.download']);
$router->get('/arsip/unduh',     [DocumentController::class, 'download'], ['can:document.download']);
$router->get('/arsip/ubah',      [DocumentController::class, 'edit'],     ['can:document.update']);
$router->post('/arsip/ubah',     [DocumentController::class, 'update'],   ['can:document.update']);
$router->post('/arsip/ganti',    [DocumentController::class, 'replace'],  ['can:document.update']);
$router->post('/arsip/arsipkan', [DocumentController::class, 'archive'],  ['can:document.archive']);
