<?php
declare(strict_types=1);

/**
 * Unggah berkas arsip secara aman.
 *
 * - Jenis berkas ditentukan dari ISI (finfo) dan harus cocok dengan ekstensi.
 * - Nama file di server acak (32 hex); nama asli hanya disimpan sebagai metadata.
 * - Ekstensi ganda berbahaya (mis. foto.php.pdf) ditolak.
 * - File disimpan di STORAGE_PATH/documents (di luar folder publik), dibagi per tahun/bulan.
 */
final class UploadService
{
    public const MIME_BY_EXT = [
        'pdf'  => 'application/pdf',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
    ];

    private const DANGEROUS_EXT = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'pht', 'phar', 'phps',
        'exe', 'dll', 'com', 'bat', 'cmd', 'sh', 'bash', 'cgi', 'pl', 'py', 'rb', 'msi', 'scr', 'lnk',
        'js', 'mjs', 'vbs', 'wsf', 'jar', 'jsp', 'asp', 'aspx', 'html', 'htm', 'svg', 'xml', 'htaccess', 'ini',
    ];

    private const PATH_PATTERN = '#^\d{4}/\d{2}/[a-f0-9]{32}\.(pdf|jpg|png)$#';

    public static function root(): string
    {
        return STORAGE_PATH . '/documents';
    }

    public static function maxBytes(): int
    {
        $mb = (int)setting('max_upload_mb', '5');
        return max(1, min($mb, 100)) * 1024 * 1024;
    }

    /** @return string[] ekstensi yang diizinkan (irisan pengaturan dengan daftar aman) */
    public static function allowedExtensions(): array
    {
        $cfg = array_filter(array_map('trim', explode(',', strtolower(setting('allowed_extensions', 'pdf,jpg,jpeg,png')))));
        $ok = array_values(array_intersect($cfg, array_keys(self::MIME_BY_EXT)));
        return $ok ?: ['pdf', 'jpg', 'jpeg', 'png'];
    }

    public static function humanSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0, ',', '.') . ' KB';
        }
        return $bytes . ' B';
    }

    private static function fail(string $msg): array
    {
        return ['ok' => false, 'error' => $msg];
    }

    /**
     * Memeriksa berkas unggahan TANPA memindahkannya.
     * @return array{ok:bool, error?:string, name?:string, ext?:string, mime?:string, size?:int, hash?:string, tmp?:string}
     */
    public static function validate(?array $f): array
    {
        if ($f === null || !isset($f['error']) || is_array($f['error'])) {
            return self::fail('Berkas wajib dipilih.');
        }

        switch ((int)$f['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return self::fail('Berkas wajib dipilih.');
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return self::fail('Ukuran berkas melebihi batas yang diizinkan server.');
            case UPLOAD_ERR_PARTIAL:
                return self::fail('Berkas terunggah sebagian. Silakan coba lagi.');
            default:
                error_log('UploadService: kode error unggah ' . (int)$f['error']);
                return self::fail('Gagal mengunggah berkas. Silakan coba lagi.');
        }

        $tmp = (string)($f['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return self::fail('Berkas tidak valid.');
        }

        $size = (int)filesize($tmp);
        if ($size <= 0) {
            return self::fail('Berkas kosong.');
        }
        if ($size > self::maxBytes()) {
            return self::fail('Ukuran berkas maksimal ' . (int)(self::maxBytes() / 1048576) . ' MB.');
        }

        // Nama asli: hanya untuk tampilan. Bersihkan path, karakter kontrol, dan null byte.
        $raw = (string)($f['name'] ?? '');
        if ($raw === '' || strpos($raw, "\0") !== false || !mb_check_encoding($raw, 'UTF-8')) {
            return self::fail('Nama berkas tidak valid.');
        }
        $name = basename(str_replace('\\', '/', $raw));
        $name = trim((string)preg_replace('/[\x00-\x1F\x7F]/u', '', $name));
        if ($name === '' || $name === '.' || $name === '..') {
            return self::fail('Nama berkas tidak valid.');
        }
        if (mb_strlen($name) > 200) {
            return self::fail('Nama berkas terlalu panjang (maksimal 200 karakter).');
        }

        // Ekstensi
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $allowed = self::allowedExtensions();
        if (!in_array($ext, $allowed, true)) {
            return self::fail('Jenis berkas tidak diizinkan. Gunakan: ' . strtoupper(implode(', ', $allowed)) . '.');
        }

        // Ekstensi ganda mencurigakan, mis. "surat.php.pdf"
        $parts = explode('.', strtolower($name));
        array_pop($parts);   // ekstensi terakhir
        array_shift($parts); // nama dasar
        foreach ($parts as $p) {
            if (in_array(trim($p), self::DANGEROUS_EXT, true)) {
                return self::fail('Nama berkas mengandung ekstensi ganda yang tidak diizinkan.');
            }
        }

        // Jenis sebenarnya dari isi berkas
        if (!class_exists('finfo')) {
            error_log('UploadService: ekstensi PHP fileinfo tidak aktif');
            return self::fail('Server belum mengaktifkan ekstensi fileinfo. Hubungi administrator.');
        }
        $mime = (string)(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
        $expected = self::MIME_BY_EXT[$ext];
        if ($mime !== $expected) {
            return self::fail('Isi berkas tidak sesuai dengan jenis yang diizinkan.');
        }

        if ($expected === 'application/pdf') {
            $head = (string)file_get_contents($tmp, false, null, 0, 1024);
            if (strpos($head, '%PDF-') === false) {
                return self::fail('Berkas PDF tidak valid.');
            }
        } else {
            $info = @getimagesize($tmp);
            if ($info === false || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
                return self::fail('Berkas gambar tidak valid.');
            }
        }

        return [
            'ok'   => true,
            'name' => $name,
            'ext'  => $ext === 'jpeg' ? 'jpg' : $ext,
            'mime' => $mime,
            'size' => $size,
            'hash' => (string)hash_file('sha256', $tmp),
            'tmp'  => $tmp,
        ];
    }

    /**
     * Memindahkan berkas yang sudah lolos validate() ke penyimpanan.
     * @return array{ok:bool, error?:string, path?:string}
     */
    public static function store(array $v): array
    {
        $root = self::root();
        $sub = date('Y') . '/' . date('m');
        $dir = $root . '/' . $sub;

        if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
            error_log('UploadService: tidak dapat membuat folder ' . $dir);
            return self::fail('Folder penyimpanan tidak dapat dibuat. Hubungi administrator.');
        }
        if (!is_writable($dir)) {
            error_log('UploadService: folder tidak dapat ditulis ' . $dir);
            return self::fail('Folder penyimpanan tidak dapat ditulis. Hubungi administrator.');
        }

        do {
            $rel = $sub . '/' . bin2hex(random_bytes(16)) . '.' . $v['ext'];
            $abs = $root . '/' . $rel;
        } while (file_exists($abs));

        if (!move_uploaded_file($v['tmp'], $abs)) {
            error_log('UploadService: move_uploaded_file gagal');
            return self::fail('Berkas gagal disimpan. Silakan coba lagi.');
        }
        @chmod($abs, 0640);

        return ['ok' => true, 'path' => $rel];
    }

    /** Path absolut yang aman untuk path relatif dari database; null bila tidak valid/tidak ada. */
    public static function absolutePath(string $relative): ?string
    {
        if (!preg_match(self::PATH_PATTERN, $relative)) {
            return null;
        }
        $real = realpath(self::root() . '/' . $relative);
        $root = realpath(self::root());
        if ($real === false || $root === false || !is_file($real)) {
            return null;
        }
        return str_starts_with($real, $root . DIRECTORY_SEPARATOR) ? $real : null;
    }

    /** Hapus berkas hasil unggahan yang gagal dicatat (rollback). Tidak untuk arsip normal. */
    public static function delete(string $relative): void
    {
        if (preg_match(self::PATH_PATTERN, $relative)) {
            @unlink(self::root() . '/' . $relative);
        }
    }
}
