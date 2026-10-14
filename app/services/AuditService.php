<?php
declare(strict_types=1);

/**
 * Pencatat audit. Hanya INSERT. Jangan kirim kata sandi, token, atau isi dokumen.
 */
final class AuditService
{
    public static function log(?int $userId, string $aksi, ?string $objekTipe = null, ?int $objekId = null, ?string $detail = null): void
    {
        try {
            Database::query(
                'INSERT INTO activity_logs (user_id, aksi, objek_tipe, objek_id, detail, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $userId,
                    substr($aksi, 0, 60),
                    $objekTipe,
                    $objekId,
                    $detail !== null ? substr($detail, 0, 255) : null,
                    client_ip(),
                    substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                ]
            );
        } catch (Throwable $e) {
            error_log('AuditService: ' . $e->getMessage());
        }
    }
}
