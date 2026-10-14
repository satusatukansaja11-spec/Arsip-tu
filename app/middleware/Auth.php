<?php
declare(strict_types=1);

/**
 * Autentikasi dan otorisasi (RBAC). Semua pemeriksaan di sisi server.
 */
final class Auth
{
    private static ?array $user = null;
    private static ?array $perms = null;

    /** Hash tiruan agar waktu respons sama saat username tidak ada. */
    private const DUMMY_HASH = '$2y$10$usesomesillystringforsalt.Xe6l4n0o8c1Lw9Zq3Bq6Z5mYx5W2G';

    public static function attempt(string $username, string $password): array
    {
        $ip = client_ip();
        $username = trim($username);

        $wait = RateLimiter::lockedSeconds($username, $ip);
        if ($wait > 0) {
            AuditService::log(null, 'login_diblokir', 'user', null, 'percobaan saat terkunci');
            return ['ok' => false, 'error' => 'Terlalu banyak percobaan. Coba lagi dalam ' . ceil($wait / 60) . ' menit.'];
        }

        $user = Database::one(
            'SELECT u.*, r.kode AS role_kode FROM users u JOIN roles r ON r.id = u.role_id WHERE u.username = ?',
            [$username]
        );

        $hash = $user['password_hash'] ?? self::DUMMY_HASH;
        $valid = password_verify($password, $hash);

        if (!$user || !$valid || !(int)$user['is_active']) {
            RateLimiter::record($username, $ip, false);
            AuditService::log($user ? (int)$user['id'] : null, 'login_gagal', 'user', $user ? (int)$user['id'] : null);
            // Pesan sama untuk akun tidak ada / sandi salah / nonaktif
            return ['ok' => false, 'error' => 'Nama pengguna atau kata sandi salah.'];
        }

        RateLimiter::record($username, $ip, true);

        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            Database::query('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['csrf'] = bin2hex(random_bytes(32));

        Database::query('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$user['id']]);
        AuditService::log((int)$user['id'], 'login_berhasil', 'user', (int)$user['id']);

        return ['ok' => true];
    }

    public static function logout(): void
    {
        $uid = self::id();
        if ($uid) {
            AuditService::log($uid, 'logout', 'user', $uid);
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], '', $p['secure'], $p['httponly']);
        }
        session_destroy();
        session_start();
        session_regenerate_id(true);
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    /** Pengguna saat ini (dimuat ulang dari DB agar penonaktifan/perubahan role langsung berlaku). */
    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }
        $id = self::id();
        if (!$id) {
            return null;
        }
        $u = Database::one(
            'SELECT u.id, u.username, u.nama_lengkap, u.email, u.is_active, u.must_change_password,
                    u.last_login_at, r.kode AS role_kode, r.nama AS role_nama
               FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?',
            [$id]
        );
        if (!$u || !(int)$u['is_active']) {
            unset($_SESSION['user_id']);
            return null;
        }
        return self::$user = $u;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function role(): ?string
    {
        return self::user()['role_kode'] ?? null;
    }

    public static function can(string $permission): bool
    {
        $role = self::role();
        if ($role === null) {
            return false;
        }
        self::$perms ??= require APP_PATH . '/config/permissions.php';
        return in_array($permission, self::$perms[$role] ?? [], true);
    }

    /** Hentikan dengan 403 bila tidak punya izin. */
    public static function require(string $permission): void
    {
        if (!self::can($permission)) {
            AuditService::log(self::id(), 'akses_ditolak', null, null, $permission);
            http_response_code(403);
            render_error(403);
            exit;
        }
    }
}
