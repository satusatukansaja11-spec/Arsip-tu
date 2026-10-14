<?php
declare(strict_types=1);

/**
 * Izin per role. Pemeriksaan dilakukan di server lewat Auth::can() / Auth::require().
 * Pimpinan hanya-baca (sesuai master prompt). Sesuai keputusan pengguna,
 * semua role boleh mengunduh semua dokumen (document.download).
 */
return [
    'admin' => [
        'dashboard.view',
        'employee.view', 'employee.view.sensitive', 'employee.create', 'employee.update', 'employee.archive',
        'document.view', 'document.download', 'document.upload', 'document.update', 'document.archive',
        'category.manage',
        'report.view', 'report.print',
        'log.view.all',
        'user.manage',
        'backup.manage',
        'settings.manage',
    ],
    'petugas_tu' => [
        'dashboard.view',
        'employee.view', 'employee.view.sensitive', 'employee.create', 'employee.update',
        'document.view', 'document.download', 'document.upload', 'document.update',
        'report.view', 'report.print',
        'log.view.own',
    ],
    'pimpinan' => [
        'dashboard.view',
        'employee.view',
        'document.view', 'document.download',
        'report.view', 'report.print',
    ],
];
