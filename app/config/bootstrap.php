<?php
declare(strict_types=1);

/**
 * Inisialisasi aplikasi: path, .env, error handling, autoload, sesi aman.
 */

define('BASE_PATH', dirname(__DIR__, 2));
define('APP_PATH', BASE_PATH . '/app');

require APP_PATH . '/config/Env.php';
Env::load(BASE_PATH . '/.env');

define('STORAGE_PATH', rtrim(Env::get('STORAGE_PATH') ?: BASE_PATH . '/storage', '/\\'));

// ---- Error handling: tidak pernah tampil ke pengguna, dicatat ke log ----
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
if (is_dir(STORAGE_PATH . '/logs') && is_writable(STORAGE_PATH . '/logs')) {
    ini_set('error_log', STORAGE_PATH . '/logs/php-error.log');
}
date_default_timezone_set('Asia/Makassar'); // WITA (Alor, NTT)

// ---- Autoload sederhana ----
spl_autoload_register(static function (string $class): void {
    foreach (['config', 'controllers', 'models', 'services', 'middleware', 'helpers'] as $dir) {
        $file = APP_PATH . "/$dir/$class.php";
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

require APP_PATH . '/helpers/functions.php';

set_exception_handler(static function (Throwable $e): void {
    error_log(get_class($e) . ': ' . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    render_error(500);
    exit;
});

// ---- Sesi aman ----
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

define('IS_HTTPS', $https);
define('SESSION_LIFETIME', Env::int('SESSION_LIFETIME_MIN', 30) * 60);

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_name('ARSIPTUSESS');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => $https,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// Kedaluwarsa karena tidak aktif
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
    $expired = isset($_SESSION['user_id']);
    session_unset();
    session_destroy();
    session_start();
    if ($expired) {
        $_SESSION['flash'][] = ['warning', 'Sesi Anda telah berakhir. Silakan masuk kembali.'];
    }
}
$_SESSION['last_activity'] = time();

// ---- Header keamanan dasar ----
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
if ($https) {
    header('Strict-Transport-Security: max-age=31536000');
}
