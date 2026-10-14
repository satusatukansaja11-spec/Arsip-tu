<?php
declare(strict_types=1);

/**
 * Installer sekali pakai: membuat akun administrator pertama.
 *
 * Jalankan dari terminal:   php bin/install.php
 *
 * - Hanya bisa dijalankan lewat CLI (bukan browser).
 * - Tidak ada kata sandi bawaan; Anda yang menentukannya saat instalasi.
 * - Setelah selesai, dibuat storage/installed.lock sehingga skrip menolak
 *   dijalankan lagi. Hapus file bin/install.php dari server produksi.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Skrip ini hanya dapat dijalankan dari command line.\n");
}

require __DIR__ . '/../app/config/bootstrap.php';

$lock = STORAGE_PATH . '/installed.lock';
if (is_file($lock)) {
    fwrite(STDERR, "Instalasi sudah pernah dijalankan (installed.lock ada). Dibatalkan.\n");
    exit(1);
}

try {
    $roleId = Database::one("SELECT id FROM roles WHERE kode = 'admin'")['id'] ?? null;
} catch (Throwable $e) {
    fwrite(STDERR, "Gagal terhubung ke database atau tabel belum ada.\n");
    fwrite(STDERR, "Periksa file .env, lalu impor database/schema.sql dan database/seed.sql terlebih dahulu.\n");
    exit(1);
}
if (!$roleId) {
    fwrite(STDERR, "Data role belum ada. Impor database/seed.sql terlebih dahulu.\n");
    exit(1);
}
if ((int)Database::one('SELECT COUNT(*) AS n FROM users')['n'] > 0) {
    fwrite(STDERR, "Sudah ada pengguna di database. Instalasi awal dibatalkan.\n");
    exit(1);
}

function ask(string $label, bool $hidden = false): string
{
    echo $label;
    $isWindows = stripos(PHP_OS, 'WIN') === 0;
    if ($hidden && !$isWindows) {
        system('stty -echo');
        $v = trim((string)fgets(STDIN));
        system('stty echo');
        echo "\n";
        return $v;
    }
    return trim((string)fgets(STDIN));
}

if (stripos(PHP_OS, 'WIN') === 0) {
    echo "Peringatan: di Windows, kata sandi yang diketik akan terlihat di layar.\n";
}

echo "=== Pembuatan Administrator Pertama - Arsip Pegawai TU ===\n";

$nama = ask('Nama lengkap     : ');
$username = ask('Nama pengguna   : ');
if ($nama === '' || !preg_match('/^[A-Za-z0-9._-]{4,50}$/', $username)) {
    fwrite(STDERR, "Nama wajib diisi; nama pengguna 4-50 karakter (huruf, angka, titik, garis).\n");
    exit(1);
}

$pass1 = ask('Kata sandi       : ', true);
$pass2 = ask('Ulangi kata sandi: ', true);
if ($pass1 !== $pass2) {
    fwrite(STDERR, "Kata sandi tidak sama.\n");
    exit(1);
}
if (($err = password_policy_error($pass1)) !== null) {
    fwrite(STDERR, $err . "\n");
    exit(1);
}

Database::query(
    'INSERT INTO users (role_id, username, password_hash, nama_lengkap, is_active, must_change_password, password_changed_at)
     VALUES (?, ?, ?, ?, 1, 0, NOW())',
    [$roleId, $username, password_hash($pass1, PASSWORD_DEFAULT), $nama]
);
$uid = (int)Database::pdo()->lastInsertId();
AuditService::log($uid, 'instalasi_admin_pertama', 'user', $uid);

file_put_contents($lock, 'installed ' . date('c') . "\n");
@chmod($lock, 0600);

echo "\nAdministrator '$username' berhasil dibuat.\n";
echo "Instalasi dikunci. Hapus bin/install.php dari server produksi.\n";
