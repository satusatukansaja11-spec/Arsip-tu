<?php
declare(strict_types=1);

/** Escape output HTML. */
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Path dasar aplikasi (mendukung subfolder, mis. /arsip-tu/public). */
function base_path_url(): string
{
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    return $dir === '/' || $dir === '.' ? '' : rtrim($dir, '/');
}

function url(string $path = ''): string
{
    return base_path_url() . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = [$type, $message];
}

function pull_flash(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (Database::all('SELECT setting_key, setting_value FROM application_settings') as $r) {
                $cache[$r['setting_key']] = (string)$r['setting_value'];
            }
        } catch (Throwable $e) {
            error_log('setting(): ' . $e->getMessage());
        }
    }
    return $cache[$key] ?? $default;
}

function client_ip(): string
{
    // Hanya REMOTE_ADDR: header proxy mudah dipalsukan.
    return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

function render(string $view, array $data = [], string $layout = 'main'): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    require APP_PATH . '/views/' . $view . '.php';
    $content = ob_get_clean();
    require APP_PATH . '/views/layouts/' . $layout . '.php';
}

function render_error(int $code): void
{
    $file = APP_PATH . "/views/errors/$code.php";
    if (!is_file($file)) {
        $file = APP_PATH . '/views/errors/500.php';
    }
    require $file;
}

function password_policy_error(string $password): ?string
{
    if (strlen($password) < 10) {
        return 'Kata sandi minimal 10 karakter.';
    }
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        return 'Kata sandi harus mengandung huruf dan angka.';
    }
    return null;
}

/** Format tanggal Y-m-d menjadi d-m-Y; "-" bila kosong. */
function fmt_date(?string $date): string
{
    if ($date === null || $date === '' || str_starts_with($date, '0000')) {
        return '-';
    }
    $ts = strtotime($date);
    return $ts ? date('d-m-Y', $ts) : '-';
}

function fmt_datetime(?string $dt): string
{
    $ts = $dt ? strtotime($dt) : false;
    return $ts ? date('d-m-Y H:i', $ts) : '-';
}

/** URL dengan query string: gabungan parameter dasar + override (nilai kosong dibuang). */
function query_url(string $path, array $base, array $override = []): string
{
    $q = array_filter(array_merge($base, $override), static fn($v) => $v !== '' && $v !== null);
    return url($path) . ($q ? '?' . http_build_query($q) : '');
}

/** Cegah CSV/formula injection di Excel. */
function csv_safe(?string $v): string
{
    $v = (string)$v;
    return ($v !== '' && strpbrk($v[0], "=+-@\t\r") !== false) ? "'" . $v : $v;
}

function activity_label(string $aksi): string
{
    static $map = [
        'login_berhasil'         => 'Masuk ke aplikasi',
        'login_gagal'            => 'Percobaan masuk gagal',
        'login_diblokir'         => 'Percobaan masuk diblokir',
        'logout'                 => 'Keluar dari aplikasi',
        'ubah_kata_sandi'        => 'Mengubah kata sandi',
        'akses_ditolak'          => 'Akses ditolak',
        'pegawai_tambah'         => 'Menambah data pegawai',
        'pegawai_ubah'           => 'Mengubah data pegawai',
        'pegawai_arsip'          => 'Mengarsipkan pegawai',
        'pegawai_ekspor'         => 'Mengekspor data pegawai',
        'instalasi_admin_pertama' => 'Instalasi administrator',
    ];
    return $map[$aksi] ?? $aksi;
}
