<?php
declare(strict_types=1);

final class Document
{
    /** Whitelist pengurutan: kunci dari URL => kolom SQL. */
    private const SORTS = [
        'unggah'   => 'd.created_at',
        'pegawai'  => 'e.nama_lengkap',
        'kategori' => 'c.urutan',
        'tahun'    => 'd.tahun',
        'nomor'    => 'd.nomor_dokumen',
    ];

    private const FROM = 'FROM employee_documents d
        JOIN employees e ON e.id = d.employee_id AND e.deleted_at IS NULL
        JOIN document_categories c ON c.id = d.category_id';

    // ------------------------------------------------------------------ filter & daftar
    public static function filters(array $in): array
    {
        $str = static function (string $k, int $max = 100) use ($in): string {
            $v = $in[$k] ?? '';
            return is_scalar($v) ? mb_substr(trim((string)$v), 0, $max) : '';
        };
        $date = static function (string $s): string {
            $d = DateTime::createFromFormat('!Y-m-d', $s);
            return ($d !== false && $d->format('Y-m-d') === $s) ? $s : '';
        };
        $digits = static fn(string $s, int $len): string => (ctype_digit($s) && strlen($s) <= $len) ? $s : '';

        $sort = $str('sort', 10);
        $sort = isset(self::SORTS[$sort]) ? $sort : 'unggah';
        $dirIn = strtolower($str('dir', 4));
        $dir = in_array($dirIn, ['asc', 'desc'], true) ? $dirIn : ($sort === 'unggah' ? 'desc' : 'asc');

        $tahun = $str('tahun', 4);

        return [
            'q'        => $str('q'),
            'kategori' => $digits($str('kategori', 6), 6),
            'pegawai'  => $digits($str('pegawai', 10), 10),
            'tahun'    => preg_match('/^\d{4}$/', $tahun) ? $tahun : '',
            'dari'     => $date($str('dari', 10)),
            'sampai'   => $date($str('sampai', 10)),
            'sort'     => $sort,
            'dir'      => $dir,
            'page'     => max(1, (int)$digits($str('page', 6), 6)),
        ];
    }

    private static function where(array $f): array
    {
        $w = ['d.deleted_at IS NULL'];
        $p = [];

        if ($f['q'] !== '') {
            $like = '%' . addcslashes($f['q'], '%_\\') . '%';
            $w[] = "(e.nama_lengkap LIKE ? ESCAPE '\\\\' OR e.nip LIKE ? ESCAPE '\\\\' OR d.nomor_dokumen LIKE ? ESCAPE '\\\\')";
            array_push($p, $like, $like, $like);
        }
        if ($f['kategori'] !== '') {
            $w[] = 'd.category_id = ?';
            $p[] = (int)$f['kategori'];
        }
        if ($f['pegawai'] !== '') {
            $w[] = 'd.employee_id = ?';
            $p[] = (int)$f['pegawai'];
        }
        if ($f['tahun'] !== '') {
            $w[] = 'd.tahun = ?';
            $p[] = (int)$f['tahun'];
        }
        if ($f['dari'] !== '') {
            $w[] = 'd.created_at >= ?';
            $p[] = $f['dari'] . ' 00:00:00';
        }
        if ($f['sampai'] !== '') {
            $w[] = 'd.created_at <= ?';
            $p[] = $f['sampai'] . ' 23:59:59';
        }
        return [implode(' AND ', $w), $p];
    }

    public static function paginate(array $f, int $perPage = 10): array
    {
        [$where, $params] = self::where($f);
        $from = self::FROM;

        $total = (int)Database::one("SELECT COUNT(*) AS n $from WHERE $where", $params)['n'];
        $pages = max(1, (int)ceil($total / $perPage));
        $page = min($f['page'], $pages);
        $offset = ($page - 1) * $perPage;
        $dir = $f['dir'] === 'desc' ? 'DESC' : 'ASC';
        $order = self::SORTS[$f['sort']] . " $dir, d.id DESC";

        $rows = Database::all(
            "SELECT d.id, d.nomor_dokumen, d.tanggal_dokumen, d.tahun, d.nama_asli, d.mime_type, d.ukuran_byte, d.created_at,
                    e.id AS employee_id, e.nama_lengkap, e.nip, c.nama AS kategori
               $from WHERE $where ORDER BY $order LIMIT " . (int)$perPage . ' OFFSET ' . (int)$offset,
            $params
        );

        return ['rows' => $rows, 'total' => $total, 'pages' => $pages, 'page' => $page, 'perPage' => $perPage];
    }

    // ------------------------------------------------------------------ satu dokumen
    /** Dokumen aktif milik pegawai yang belum diarsipkan; null bila tidak ada. */
    public static function find(int $id): ?array
    {
        return Database::one(
            'SELECT d.*, e.nama_lengkap, e.nip, e.status_kepegawaian, c.nama AS kategori, u.nama_lengkap AS pengunggah
               FROM employee_documents d
               JOIN employees e ON e.id = d.employee_id AND e.deleted_at IS NULL
               JOIN document_categories c ON c.id = d.category_id
               LEFT JOIN users u ON u.id = d.uploaded_by
              WHERE d.id = ? AND d.deleted_at IS NULL',
            [$id]
        );
    }

    public static function categories(): array
    {
        return Database::all('SELECT id, nama FROM document_categories WHERE is_active = 1 ORDER BY urutan, id');
    }

    public static function employeesForSelect(): array
    {
        return Database::all(
            'SELECT id, nama_lengkap, nip, status_aktif FROM employees WHERE deleted_at IS NULL ORDER BY nama_lengkap'
        );
    }

    /** Berkas identik (hash sama) untuk pegawai + kategori yang sama. */
    public static function duplicateExists(int $employeeId, int $categoryId, string $hash, ?int $exceptId = null): bool
    {
        $row = Database::one(
            'SELECT id FROM employee_documents
              WHERE employee_id = ? AND category_id = ? AND hash_sha256 = ? AND deleted_at IS NULL AND id <> ?',
            [$employeeId, $categoryId, $hash, $exceptId ?? 0]
        );
        return $row !== null;
    }

    public static function create(array $d, array $file, string $path, int $userId): int
    {
        Database::query(
            'INSERT INTO employee_documents
               (employee_id, category_id, nomor_dokumen, tanggal_dokumen, tahun, keterangan,
                file_path, nama_asli, mime_type, ukuran_byte, hash_sha256, uploaded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $d['employee_id'], $d['category_id'], $d['nomor_dokumen'], $d['tanggal_dokumen'], $d['tahun'], $d['keterangan'],
                $path, $file['name'], $file['mime'], $file['size'], $file['hash'], $userId,
            ]
        );
        return (int)Database::pdo()->lastInsertId();
    }

    public static function updateMeta(int $id, array $d): void
    {
        Database::query(
            'UPDATE employee_documents
                SET category_id = ?, nomor_dokumen = ?, tanggal_dokumen = ?, tahun = ?, keterangan = ?
              WHERE id = ? AND deleted_at IS NULL',
            [$d['category_id'], $d['nomor_dokumen'], $d['tanggal_dokumen'], $d['tahun'], $d['keterangan'], $id]
        );
    }

    /**
     * Mengganti berkas: berkas lama dicatat sebagai versi (tetap tersimpan), berkas baru menjadi aktif.
     * @return int nomor versi yang diberikan kepada berkas lama
     */
    public static function replaceFile(int $id, array $file, string $newPath, int $userId): int
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $cur = Database::one('SELECT * FROM employee_documents WHERE id = ? AND deleted_at IS NULL FOR UPDATE', [$id]);
            if (!$cur) {
                throw new RuntimeException('Dokumen tidak ditemukan.');
            }
            $ver = (int)Database::one(
                'SELECT COALESCE(MAX(versi), 0) + 1 AS v FROM document_versions WHERE document_id = ?',
                [$id]
            )['v'];

            Database::query(
                'INSERT INTO document_versions (document_id, versi, file_path, nama_asli, mime_type, ukuran_byte, hash_sha256, replaced_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$id, $ver, $cur['file_path'], $cur['nama_asli'], $cur['mime_type'], $cur['ukuran_byte'], $cur['hash_sha256'], $userId]
            );
            Database::query(
                'UPDATE employee_documents
                    SET file_path = ?, nama_asli = ?, mime_type = ?, ukuran_byte = ?, hash_sha256 = ?
                  WHERE id = ?',
                [$newPath, $file['name'], $file['mime'], $file['size'], $file['hash'], $id]
            );
            $pdo->commit();
            return $ver;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** Pengarsipan (soft delete). Berkas fisik dan versinya tidak pernah dihapus. */
    public static function archive(int $id): void
    {
        Database::query('UPDATE employee_documents SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL', [$id]);
    }

    // ------------------------------------------------------------------ versi & riwayat
    public static function versions(int $docId): array
    {
        return Database::all(
            'SELECT v.versi, v.nama_asli, v.mime_type, v.ukuran_byte, v.created_at, u.nama_lengkap AS pengganti
               FROM document_versions v LEFT JOIN users u ON u.id = v.replaced_by
              WHERE v.document_id = ? ORDER BY v.versi DESC',
            [$docId]
        );
    }

    public static function findVersion(int $docId, int $versi): ?array
    {
        return Database::one(
            'SELECT v.* FROM document_versions v
               JOIN employee_documents d ON d.id = v.document_id AND d.deleted_at IS NULL
               JOIN employees e ON e.id = d.employee_id AND e.deleted_at IS NULL
              WHERE v.document_id = ? AND v.versi = ?',
            [$docId, $versi]
        );
    }

    /** Riwayat aktivitas dokumen: aksi, pelaku, waktu (tanpa isi dokumen). */
    public static function history(int $docId): array
    {
        return Database::all(
            "SELECT l.aksi, l.detail, l.created_at, u.nama_lengkap AS pengguna
               FROM activity_logs l LEFT JOIN users u ON u.id = l.user_id
              WHERE l.objek_tipe = 'document' AND l.objek_id = ?
              ORDER BY l.id DESC LIMIT 50",
            [$docId]
        );
    }
}
