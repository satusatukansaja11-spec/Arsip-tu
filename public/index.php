<?php
declare(strict_types=1);

require __DIR__ . '/../app/config/bootstrap.php';

$router = new Router();
require BASE_PATH . '/routes/web.php';
$router->dispatch();
