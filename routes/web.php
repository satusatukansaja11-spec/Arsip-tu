<?php
declare(strict_types=1);

$router->get('/', static function (): void {
    redirect(Auth::check() ? 'dashboard' : 'login');
});

$router->get('/login',  [AuthController::class, 'showLogin'], ['guest']);
$router->post('/login', [AuthController::class, 'login'],     ['guest']);
$router->post('/logout', [AuthController::class, 'logout'],   ['auth']);

$router->get('/password',  [AuthController::class, 'showChangePassword'], ['auth']);
$router->post('/password', [AuthController::class, 'changePassword'],     ['auth']);

$router->get('/dashboard', [DashboardController::class, 'index'], ['can:dashboard.view']);

// Data pegawai
$router->get('/pegawai',          [EmployeeController::class, 'index'],  ['can:employee.view']);
$router->get('/pegawai/ekspor',   [EmployeeController::class, 'export'], ['can:employee.view', 'can:report.view']);
$router->get('/pegawai/detail',   [EmployeeController::class, 'show'],   ['can:employee.view']);
$router->get('/pegawai/tambah',   [EmployeeController::class, 'create'], ['can:employee.create']);
$router->post('/pegawai/tambah',  [EmployeeController::class, 'store'],  ['can:employee.create']);
$router->get('/pegawai/ubah',     [EmployeeController::class, 'edit'],   ['can:employee.update']);
$router->post('/pegawai/ubah',    [EmployeeController::class, 'update'], ['can:employee.update']);
$router->post('/pegawai/arsipkan', [EmployeeController::class, 'archive'], ['can:employee.archive']);
