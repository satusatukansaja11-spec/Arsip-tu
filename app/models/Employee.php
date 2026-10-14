<?php
declare(strict_types=1);

final class Employee
{
    public const STATUSES = ['PNS', 'PPPK', 'HONORER'];

    public const EDUCATION = ['SD', 'SMP', 'SMA/MA/SMK', 'D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3'];

    /** Kolom yang boleh diisi dari formulir (konstanta, aman dirangkai ke SQL). */
    public const FIELDS = [
        'nip', 'nik', 'nama_lengkap', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin',
        'pangkat_golongan', 'jabatan', 'unit_kerja', 'status_kepegawaian', 'pendidikan_terakhir',
        'telepon', 'email', 'alamat', 'tanggal_mulai', 'status_aktif', 'catatan',
    ];

    /** Whitelist kolom pengurutan: kunci dari URL => kolom SQL. */
    private const SORTS = [
        'nama'    => 'e.nama_lengkap',
        'nip'     => 'e.nip',
        'status'  => 'e.status_kepegawaian',
        'jabatan' => 'e.jabatan',
        'unit'    => 'e.unit_kerja',
        'mulai'   => 'e.tanggal_mulai',
        'dibuat'  => 'e.created_at',
    ];

    /** Normalisasi parameter filter dari query string. */
    public static function filters(array $in): array
    {
        $str = static fn(string $k, int $max = 100): string => mb_substr(trim((string)($in[$k] ?? '')), 0, $max);

        $status = $str('status', 10);
        $aktif  = $str('aktif', 1);
        $sort   = $str('sort', 10);
        $dir    = strtolower($str('dir', 4));

        return [
            'q'       => $str('q', 100),
            'status'  => in_array($status, self::STATUSES, true) ? $status : '',
            'jabatan' => $str('jabatan'),
            'unit'    => $str('unit'),
            'aktif'   => in_array($aktif, ['0', '1'], true) ? $aktif : '',
            'sort'    => isset(self::SORTS[$sort]) ? $sort : 'nama',
            'dir'     => $dir === 'desc' ? 'desc' : 'asc',
            'page'    => max(1, (int)($in['page'] ?? 1)),
        ];
    }

    private static function where(array $f): array
    {
        $w = ['e.deleted_at IS NULL'];
        $p = [];

        if ($f['q'] !== '') {
            $like = '%' . addcslashes($f['q'], '%_\\') . '%';
            $w[] = "(e.nama_lengkap LIKE ? ESCAPE '\\\\' OR e.nip LIKE ? ESCAPE '\\\\')";
            array_push($p, $like, $like);
        }
        if ($f['status'] !== '') {
            $w[] = 'e.status_kepegawaian = ?';
            $p[] = $f['status'];
        }
        if ($f['jabatan'] !== '') {
            $w[] = 'e.jabatan = ?';
            $p[] = $f['jabatan'];
        }
        if ($f['unit'] !== '') {
            $w[] = 'e.unit_kerja = ?';
            $p[] = $f['unit'];
        }
        if ($f['aktif'] !== '') {
            $w[] = 'e.status_aktif = ?';
            $p[] = (int)$f['aktif'];
        }
        return [implode(' AND ', $w), $p];
    }

    private static function orderBy(array $f): string
    {
        $col = self::SORTS[$f['sort']];
        $dir = $f['dir'] === 'desc' ? 'DESC' : 'ASC';
        return "$col $dir, e.id ASC";
    }

    public static function paginate(array $f, int $perPage = 10): array
    {
        [$where, $params] = self::where($f);
        $total = (int)Database::one("SELECT COUNT(*) AS n FROM employees e WHERE $where", $params)['n'];
        $pages = max(1, (int)ceil($total / $perPage));
        $page  = min($f['page'], $pages);
        $offset = ($page - 1) * $perPage;
        $order = self::orderBy($f);

        $rows = Database::all(
            "SELECT e.id, e.nip, e.nama_lengkap, e.jabatan, e.unit_kerja, e.status_kepegawaian, e.status_aktif
               FROM employees e WHERE $where ORDER BY $order LIMIT " . (int)$perPage . ' OFFSET ' . (int)$offset,
            $params
        );

        return ['rows' => $rows, 'total' => $total, 'pages' => $pages, 'page' => $page, 'perPage' => $perPage];
    }

    /** Untuk ekspor (dibatasi 5000 baris). */
    public static function allForExport(array $f): array
    {
        [$where, $params] = self::where($f);
        return Database::all(
            "SELECT e.* FROM employees e WHERE $where ORDER BY " . self::orderBy($f) . ' LIMIT 5000',
            $params
        );
    }

    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM employees WHERE id = ? AND deleted_at IS NULL', [$id]);
    }

    /** Nilai unik untuk dropdown filter. Nama kolom dibatasi whitelist. */
    public static function distinct(string $column): array
    {
        if (!in_array($column, ['jabatan', 'unit_kerja'], true)) {
            return [];
        }
        $rows = Database::all(
            "SELECT DISTINCT $column AS v FROM employees WHERE deleted_at IS NULL AND $column IS NOT NULL AND $column <> '' ORDER BY $column"
        );
        return array_column($rows, 'v');
    }

    /** NIP unik, termasuk pegawai yang sudah diarsipkan (batas UNIQUE di database). */
    public static function nipTaken(string $nip, ?int $exceptId = null): bool
    {
        $row = Database::one(
            'SELECT id FROM employees WHERE nip = ? AND id <> ?',
            [$nip, $exceptId ?? 0]
        );
        return $row !== null;
    }

    public static function create(array $d, int $userId): int
    {
        $cols = implode(', ', self::FIELDS);
        $marks = implode(', ', array_fill(0, count(self::FIELDS), '?'));
        $vals = array_map(static fn($f) => $d[$f] ?? null, self::FIELDS);
        $vals[] = $userId;
        Database::query("INSERT INTO employees ($cols, created_by) VALUES ($marks, ?)", $vals);
        return (int)Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        $set = implode(', ', array_map(static fn($f) => "$f = ?", self::FIELDS));
        $vals = array_map(static fn($f) => $d[$f] ?? null, self::FIELDS);
        $vals[] = $id;
        Database::query("UPDATE employees SET $set WHERE id = ? AND deleted_at IS NULL", $vals);
    }

    /** Pengarsipan (soft delete). Data dan dokumen tidak pernah dihapus permanen. */
    public static function archive(int $id): void
    {
        Database::query(
            'UPDATE employees SET deleted_at = NOW(), status_aktif = 0 WHERE id = ? AND deleted_at IS NULL',
            [$id]
        );
    }

    public static function documents(int $employeeId): array
    {
        return Database::all(
            'SELECT d.id, d.nomor_dokumen, d.tanggal_dokumen, d.tahun, d.keterangan, d.created_at, c.nama AS kategori
               FROM employee_documents d JOIN document_categories c ON c.id = d.category_id
              WHERE d.employee_id = ? AND d.deleted_at IS NULL
              ORDER BY c.urutan, d.tanggal_dokumen DESC, d.id DESC',
            [$employeeId]
        );
    }

    /** Riwayat perubahan: hanya jenis aksi, nama kolom yang berubah, pelaku, dan waktu (tanpa nilai). */
    public static function history(int $employeeId): array
    {
        return Database::all(
            "SELECT l.aksi, l.detail, l.created_at, u.nama_lengkap AS pengguna
               FROM activity_logs l LEFT JOIN users u ON u.id = l.user_id
              WHERE l.objek_tipe = 'employee' AND l.objek_id = ?
              ORDER BY l.id DESC LIMIT 50",
            [$employeeId]
        );
    }
}
