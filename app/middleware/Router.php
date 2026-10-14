<?php
declare(strict_types=1);

/**
 * Router sederhana. Rute POST otomatis diverifikasi CSRF.
 * Middleware: 'auth' (wajib login), 'guest' (hanya tamu), atau string izin 'can:xxx'.
 */
final class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler, array $mw = []): void
    {
        $this->routes['GET'][$path] = [$handler, $mw];
    }

    public function post(string $path, callable|array $handler, array $mw = []): void
    {
        $this->routes['POST'][$path] = [$handler, $mw];
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = base_path_url();
        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }
        $uri = '/' . trim($uri, '/');
        if ($uri === '/index.php') {
            $uri = '/';
        }

        $route = $this->routes[$method][$uri] ?? null;
        if ($route === null) {
            http_response_code(404);
            render_error(404);
            return;
        }

        [$handler, $mw] = $route;

        if ($method === 'POST' && !Csrf::valid()) {
            http_response_code(419);
            flash('danger', 'Sesi formulir tidak valid atau kedaluwarsa. Silakan coba lagi.');
            header('Location: ' . (Auth::check() ? url('dashboard') : url('login')));
            return;
        }

        foreach ($mw as $m) {
            $this->runMiddleware($m);
        }

        if (is_array($handler)) {
            [$class, $action] = $handler;
            (new $class())->$action();
        } else {
            $handler();
        }
    }

    private function runMiddleware(string $m): void
    {
        if ($m === 'guest') {
            if (Auth::check()) {
                redirect('dashboard');
            }
            return;
        }

        // Semua middleware selain 'guest' butuh login
        if (!Auth::check()) {
            flash('warning', 'Silakan masuk terlebih dahulu.');
            redirect('login');
        }

        // Paksa ganti sandi sementara sebelum akses halaman lain
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        $onChangePage = str_ends_with($path, '/password') || str_ends_with($path, '/logout');
        if ((int)Auth::user()['must_change_password'] === 1 && !$onChangePage) {
            redirect('password');
        }

        if (str_starts_with($m, 'can:')) {
            Auth::require(substr($m, 4));
        }
    }
}
