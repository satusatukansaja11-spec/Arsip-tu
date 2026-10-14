<?php
declare(strict_types=1);

/**
 * Pembatasan percobaan login dengan jeda progresif.
 * Hitung kegagalan 15 menit terakhir per (username + IP) dan per IP.
 * Setelah batas, kunci selama 2^(n-batas) menit (maks. 15 menit).
 */
final class RateLimiter
{
    private const WINDOW_MIN = 15;
    private const MAX_LOCK_MIN = 15;

    /** @return int sisa detik terkunci (0 = boleh mencoba) */
    public static function lockedSeconds(string $username, string $ip): int
    {
        $max = Env::int('LOGIN_MAX_ATTEMPTS', 5);

        $row = Database::one(
            'SELECT COUNT(*) AS n, MAX(attempted_at) AS last_at
               FROM login_attempts
              WHERE berhasil = 0 AND attempted_at > (NOW() - INTERVAL ' . self::WINDOW_MIN . ' MINUTE)
                AND (username = ? AND ip_address = ?)',
            [$username, $ip]
        );
        $byUser = self::lock((int)($row['n'] ?? 0), $row['last_at'] ?? null, $max);

        // Batas lebih longgar per IP untuk menahan tebakan banyak username
        $rowIp = Database::one(
            'SELECT COUNT(*) AS n, MAX(attempted_at) AS last_at
               FROM login_attempts
              WHERE berhasil = 0 AND attempted_at > (NOW() - INTERVAL ' . self::WINDOW_MIN . ' MINUTE)
                AND ip_address = ?',
            [$ip]
        );
        $byIp = self::lock((int)($rowIp['n'] ?? 0), $rowIp['last_at'] ?? null, $max * 4);

        return max($byUser, $byIp);
    }

    private static function lock(int $fails, ?string $lastAt, int $max): int
    {
        if ($fails < $max || $lastAt === null) {
            return 0;
        }
        $lockMin = min(self::MAX_LOCK_MIN, 2 ** ($fails - $max));
        $until = strtotime($lastAt) + $lockMin * 60;
        return max(0, $until - time());
    }

    public static function record(string $username, string $ip, bool $success): void
    {
        Database::query(
            'INSERT INTO login_attempts (username, ip_address, berhasil) VALUES (?, ?, ?)',
            [substr($username, 0, 50), $ip, $success ? 1 : 0]
        );
        if ($success) {
            // Reset hitungan gagal untuk kombinasi ini
            Database::query(
                'DELETE FROM login_attempts WHERE berhasil = 0 AND username = ? AND ip_address = ?',
                [substr($username, 0, 50), $ip]
            );
        }
        // Bersihkan catatan lama (> 7 hari) sesekali
        if (random_int(1, 50) === 1) {
            Database::query('DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 7 DAY)');
        }
    }
}
