<?php
declare(strict_types=1);

final class DashboardController
{
    public function index(): void
    {
        render('dashboard/index', [
            'title'        => 'Dashboard',
            'd'            => DashboardService::data(),
            'extraScripts' => [
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js',
                asset('js/dashboard.js'),
            ],
        ]);
    }
}
