<?php
declare(strict_types=1);

/**
 * Data laporan. Satu sumber data untuk tampilan HTML, cetak, dan ekspor CSV,
 * sehingga ekspor tidak pernah memuat kolom yang tidak tampil untuk role pengguna.
 *
 * Hasil build():
 *   columns  : list [ 'l' => label, 'a' => 'start'|'end', 'csv' => true bila hanya untuk CSV ]
 *   rows     : list baris (array nilai string sejajar columns) atau ['_group' => 'teks judul grup']
 *   summary  : list [label, nilai]
 *   total    : jumlah seluruh baris data (tanpa batas)
 *   truncated: true bila baris dipotong oleh batas
 */
final class ReportService
{
    public const MAX_ROWS_HTML = 2000;
    public const MAX_ROWS_CSV  = 5000;

    /** Aktivitas pengelolaan arsip yang masuk laporan aktivitas. */
    public const AKSI_ARSIP = [
        'dokumen_unggah', 'dokumen_ubah', 'dokumen_ganti', 'dokumen_lihat', 'dokumen_unduh', 'dokumen_arsip',
        'pegawai_tambah', 'pegawai_ubah', 'pegawai_arsip', 'pegawai_ekspor', 'laporan_ekspor',
    ];

    private const STATUS_LABEL = ['PNS' => 'PNS', 'PPPK' => 'PPPK', 'HONORER' => 'Honorer'];

    // ------------------------------------------------------------------ definisi
    public static function definitions(): array
    {
        return [
            'pegawai' => [
                'judul' => 'Daftar Seluruh Pegawai', 'ikon' => 'bi-people',
                'deskripsi' => 'Seluruh pegawai beserta jabatan, unit kerja, dan status kepegawaian.',
                'filter' => ['status', 'aktif'], 'izin' => 'employee.view',
            ],
            'pegawai_status' => [
                'judul' => 'Daftar Pegawai Berdasarkan Status Kepegawaian', 'ikon' => 'bi-person-lines-fill',
                'deskripsi' => 'Pegawai dikelompokkan menurut PNS, PPPK, dan Honorer beserta jumlah tiap kelompok.',
                'filter' => ['status', 'aktif'], 'izin' => 'employee.view',
            ],
            'rekap_kategori' => [
                'judul' => 'Rekapitulasi Arsip Berdasarkan Kategori', 'ikon' => 'bi-bar-chart',
                'deskripsi' => 'Jumlah dokumen, jumlah pegawai pemilik, dan total ukuran untuk tiap kategori dokumen.',
                'filter' => ['kategori', 'dari', 'sampai'], 'izin' => 'document.view', 'periode' => 'Tanggal unggah',
            ],
            'dokumen_pegawai' => [
                'judul' => 'Daftar Dokumen Setiap Pegawai', 'ikon' => 'bi-folder2-open',
                'deskripsi' => 'Rincian dokumen yang dimiliki tiap pegawai, dikelompokkan per pegawai.',
                'filter' => ['pegawai', 'kategori', 'dari', 'sampai'], 'izin' => 'document.view', 'periode' => 'Tanggal unggah',
            ],
            'kelengkapan' => [
                'judul' => 'Laporan Kelengkapan Dokumen', 'ikon' => 'bi-clipboard-check',
                'deskripsi' => 'Kelengkapan dokumen wajib tiap pegawai aktif beserta dokumen yang masih kurang.',
                'filter' => ['status', 'kelengkapan'], 'izin' => 'document.view',
            ],
            'per_tahun' => [
                'judul' => 'Laporan Arsip per Tahun', 'ikon' => 'bi-calendar3',
                'deskripsi' => 'Jumlah dokumen menurut tahun dokumen, dirinci per kategori.',
                'filter' => ['kategori', 'tahun_dari', 'tahun_sampai'], 'izin' => 'document.view',
            ],
            'aktivitas' => [
                'judul' => 'Laporan Aktivitas Pengelolaan Arsip', 'ikon' => 'bi-clock-history',
                'deskripsi' => 'Riwayat tambah/ubah data, unggah, ganti, lihat, dan unduh dokumen, serta ekspor laporan.',
                'filter' => ['aksi', 'pengguna', 'dari', 'sampai'], 'izin' => 'log', 'periode' => 'Tanggal aktivitas',
            ],
        ];
    }

    public static function allowed(string $key): bool
    {
        $defs = self::definitions();
        if (!isset($defs[$key]) || !Auth::can('report.view')) {
            return false;
        }
        if ($key === 'aktivitas') {
            return Auth::can('log.view.all') || Auth::can('log.view.own');
        }
        return Auth::can($defs[$key]['izin']);
    }

    /** Laporan yang boleh dibuka pengguna saat ini. */
    public static function available(): array
    {
        return array_filter(self::definitions(), static fn(string $k): bool => self::allowed($k), ARRAY_FILTER_USE_KEY);
    }

    // ------------------------------------------------------------------ filter
    public static function filters(array $in): array
    {
        $str = static function (string $k, int $max = 50) use ($in): string {
            $v = $in[$k] ?? '';
            return is_scalar($v) ? mb_substr(trim((string)$v), 0, $max) : '';
        };
        $date = static function (string $s): string {
            $d = DateTime::createFromFormat('!Y-m-d', $s);
            return ($d !== false && $d->format('Y-m-d') === $s) ? $s : '';
        };
        $digits = static fn(string $s, int $len): string => (ctype_digit($s) && strlen($s) <= $len) ? $s : '';
        $year = static fn(string $s): string => preg_match('/^\d{4}$/', $s) === 1 ? $s : '';

        $status = $str('status', 10);
        $aktif  = $str('aktif', 1);
        $kel    = $str('kelengkapan', 10);
        $aksi   = $str('aksi', 30);

        return [
            'status'       => in_array($status, Employee::STATUSES, true) ? $status : '',
            'aktif'        => in_array($aktif, ['0', '1'], true) ? $aktif : '',
            'kelengkapan'  => in_array($kel, ['lengkap', 'belum'], true) ? $kel : '',
            'kategori'     => $digits($str('kategori', 6), 6),
            'pegawai'      => $digits($str('pegawai', 10), 10),
            'pengguna'     => $digits($str('pengguna', 10), 10),
            'dari'         => $date($str('dari', 10)),
            'sampai'       => $date($str('sampai', 10)),
            'tahun_dari'   => $year($str('tahun_dari', 4)),
            'tahun_sampai' => $year($str('tahun_sampai', 4)),
            'aksi'         => in_array($aksi, self::AKSI_ARSIP, true) ? $aksi : '',
        ];
    }

    /** Daftar pilihan untuk formulir filter. */
    public static function options(array $filterKeys): array
    {
        $o = [];
        if (in_array('kategori', $filterKeys, true)) {
            $o['kategori'] = Document::categories();
        }
        if (in_array('pegawai', $filterKeys, true)) {
            $o['pegawai'] = Document::employeesForSelect();
        }
        if (in_array('pengguna', $filterKeys, true) && Auth::can('log.view.all')) {
            $o['pengguna'] = Database::all('SELECT id, nama_lengkap FROM users ORDER BY nama_lengkap');
        }
        if (in_array('aksi', $filterKeys, true)) {
            foreach (self::AKSI_ARSIP as $a) {
                $o['aksi'][$a] = activity_label($a);
            }
        }
        return $o;
    }

    /** Ringkasan filter untuk kop cetak. */
    public static function describe(string $key, array $f): array
    {
        $def = self::definitions()[$key];
        $keys = $def['filter'];
        $has = static fn(string $k): bool => in_array($k, $keys, true) && ($f[$k] ?? '') !== '';
        $out = [];

        if ($has('status')) {
            $out[] = 'Status kepegawaian: ' . self::statusLabel($f['status']);
        }
        if ($has('aktif')) {
            $out[] = 'Status aktif: ' . ($f['aktif'] === '1' ? 'Aktif' : 'Tidak aktif');
        }
        if ($has('kelengkapan')) {
            $out[] = 'Kelengkapan: ' . ($f['kelengkapan'] === 'lengkap' ? 'Lengkap' : 'Belum lengkap');
        }
        if ($has('kategori') && ($r = Database::one('SELECT nama FROM document_categories WHERE id = ?', [(int)$f['kategori']]))) {
            $out[] = 'Kategori: ' . $r['nama'];
        }
        if ($has('pegawai') && ($r = Database::one('SELECT nama_lengkap FROM employees WHERE id = ?', [(int)$f['pegawai']]))) {
            $out[] = 'Pegawai: ' . $r['nama_lengkap'];
        }
        if ($has('pengguna') && Auth::can('log.view.all')
            && ($r = Database::one('SELECT nama_lengkap FROM users WHERE id = ?', [(int)$f['pengguna']]))) {
            $out[] = 'Pengguna: ' . $r['nama_lengkap'];
        }
        if ($has('aksi')) {
            $out[] = 'Aktivitas: ' . activity_label($f['aksi']);
        }
        if (in_array('dari', $keys, true) && ($f['dari'] !== '' || $f['sampai'] !== '')) {
            $out[] = ($def['periode'] ?? 'Periode') . ': '
                . ($f['dari'] !== '' ? fmt_date($f['dari']) : '...') . ' s.d. '
                . ($f['sampai'] !== '' ? fmt_date($f['sampai']) : '...');
        }
        if (in_array('tahun_dari', $keys, true) && ($f['tahun_dari'] !== '' || $f['tahun_sampai'] !== '')) {
            $out[] = 'Tahun dokumen: ' . ($f['tahun_dari'] !== '' ? $f['tahun_dari'] : '...')
                . ' s.d. ' . ($f['tahun_sampai'] !== '' ? $f['tahun_sampai'] : '...');
        }
        return $out;
    }

    // ------------------------------------------------------------------ build
    public static function build(string $key, array $f, int $limit): array
    {
        return match ($key) {
            'pegawai'         => self::pegawai($f, $limit, false),
            'pegawai_status'  => self::pegawai($f, $limit, true),
            'rekap_kategori'  => self::rekapKategori($f),
            'dokumen_pegawai' => self::dokumenPegawai($f, $limit),
            'kelengkapan'     => self::kelengkapan($f, $limit),
            'per_tahun'       => self::perTahun($f, $limit),
            'aktivitas'       => self::aktivitas($f, $limit),
            default           => throw new InvalidArgumentException('Laporan tidak dikenal.'),
        };
    }

    // ------------------------------------------------------------------ util
    private static function col(string $label, string $align = 'start', bool $csvOnly = false): array
    {
        return ['l' => $label, 'a' => $align, 'csv' => $csvOnly];
    }

    private static function statusLabel(string $s): string
    {
        return self::STATUS_LABEL[$s] ?? $s;
    }

    /** Teks aman untuk sel: null menjadi string kosong (tampilan HTML menampilkan "-"). */
    private static function s(mixed $v): string
    {
        return $v === null ? '' : (string)$v;
    }

    private static function size(mixed $bytes): string
    {
        return UploadService::humanSize((int)$bytes);
    }

    private static function periode(array $f, string $column, array &$w, array &$p): void
    {
        if (($f['dari'] ?? '') !== '') {
            $w[] = "$column >= ?";
            $p[] = $f['dari'] . ' 00:00:00';
        }
        if (($f['sampai'] ?? '') !== '') {
            $w[] = "$column <= ?";
            $p[] = $f['sampai'] . ' 23:59:59';
        }
    }

    // ------------------------------------------------------------------ 1 & 2: pegawai
    private static function pegawai(array $f, int $limit, bool $grouped): array
    {
        $w = ['e.deleted_at IS NULL'];
        $p = [];
        if ($f['status'] !== '') {
            $w[] = 'e.status_kepegawaian = ?';
            $p[] = $f['status'];
        }
        if ($f['aktif'] !== '') {
            $w[] = 'e.status_aktif = ?';
            $p[] = (int)$f['aktif'];
        }
        $where = implode(' AND ', $w);
        $sens = Auth::can('employee.view.sensitive');

        $counts = [];
        foreach (Database::all(
            "SELECT e.status_kepegawaian AS s, COUNT(*) AS n FROM employees e WHERE $where GROUP BY e.status_kepegawaian",
            $p
        ) as $r) {
            $counts[$r['s']] = (int)$r['n'];
        }
        $total = array_sum($counts);

        $order = $grouped ? "FIELD(e.status_kepegawaian, 'PNS', 'PPPK', 'HONORER'), e.nama_lengkap, e.id" : 'e.nama_lengkap, e.id';
        $rows = Database::all(
            "SELECT e.nama_lengkap, e.nip, e.jabatan, e.unit_kerja, e.status_kepegawaian, e.pendidikan_terakhir,
                    e.tanggal_mulai, e.status_aktif, e.tanggal_lahir, e.telepon, e.email
               FROM employees e WHERE $where ORDER BY $order LIMIT " . (int)$limit,
            $p
        );

        $cols = [
            self::col('No', 'end'), self::col('Nama Lengkap'), self::col('NIP'), self::col('Jabatan'),
            self::col('Unit Kerja'), self::col('Status', 'start', $grouped), self::col('Pendidikan'),
            self::col('Mulai Bekerja'), self::col('Aktif'),
        ];
        if ($sens) {
            array_push($cols, self::col('Tanggal Lahir'), self::col('Telepon'), self::col('Email'));
        }

        $out = [];
        $no = 0;
        $cur = null;
        foreach ($rows as $r) {
            if ($grouped && $r['status_kepegawaian'] !== $cur) {
                $cur = $r['status_kepegawaian'];
                $no = 0;
                $out[] = ['_group' => 'Pegawai ' . self::statusLabel($cur) . ' (' . ($counts[$cur] ?? 0) . ' orang)'];
            }
            $line = [
                (string)++$no, $r['nama_lengkap'], self::s($r['nip']), self::s($r['jabatan']), self::s($r['unit_kerja']),
                self::statusLabel($r['status_kepegawaian']), self::s($r['pendidikan_terakhir']),
                $r['tanggal_mulai'] ? fmt_date($r['tanggal_mulai']) : '', $r['status_aktif'] ? 'Aktif' : 'Tidak aktif',
            ];
            if ($sens) {
                array_push($line, $r['tanggal_lahir'] ? fmt_date($r['tanggal_lahir']) : '', self::s($r['telepon']), self::s($r['email']));
            }
            $out[] = $line;
        }

        $summary = [['Total pegawai', (string)$total]];
        if ($grouped) {
            foreach (['PNS', 'PPPK', 'HONORER'] as $s) {
                if (($counts[$s] ?? 0) > 0 || $f['status'] === '') {
                    $summary[] = [self::statusLabel($s), (string)($counts[$s] ?? 0)];
                }
            }
        }

        return ['columns' => $cols, 'rows' => $out, 'summary' => $summary, 'total' => $total, 'truncated' => $total > $limit];
    }

    // ------------------------------------------------------------------ 3: rekap kategori
    private static function rekapKategori(array $f): array
    {
        $join = 'd.category_id = c.id AND d.deleted_at IS NULL
                 AND d.employee_id IN (SELECT id FROM employees WHERE deleted_at IS NULL)';
        $p = [];
        $extra = [];
        self::periode($f, 'd.created_at', $extra, $p);
        if ($extra) {
            $join .= ' AND ' . implode(' AND ', $extra);
        }

        $where = 'c.is_active = 1';
        if ($f['kategori'] !== '') {
            $where .= ' AND c.id = ?';
            $p[] = (int)$f['kategori'];
        }

        $rows = Database::all(
            "SELECT c.nama, COUNT(d.id) AS jml, COUNT(DISTINCT d.employee_id) AS peg,
                    COALESCE(SUM(d.ukuran_byte), 0) AS ukuran, MAX(d.created_at) AS terakhir
               FROM document_categories c LEFT JOIN employee_documents d ON $join
              WHERE $where GROUP BY c.id, c.nama, c.urutan ORDER BY c.urutan, c.id",
            $p
        );

        $cols = [
            self::col('No', 'end'), self::col('Kategori Dokumen'), self::col('Jumlah Dokumen', 'end'),
            self::col('Jumlah Pegawai', 'end'), self::col('Total Ukuran', 'end'), self::col('Terakhir Diunggah'),
        ];
        $out = [];
        $no = 0;
        $docs = 0;
        $bytes = 0;
        foreach ($rows as $r) {
            $docs += (int)$r['jml'];
            $bytes += (int)$r['ukuran'];
            $out[] = [
                (string)++$no, $r['nama'], (string)(int)$r['jml'], (string)(int)$r['peg'],
                (int)$r['jml'] > 0 ? self::size($r['ukuran']) : '', $r['terakhir'] ? fmt_datetime($r['terakhir']) : '',
            ];
        }

        return [
            'columns' => $cols, 'rows' => $out,
            'summary' => [['Total dokumen', (string)$docs], ['Total ukuran', self::size($bytes)]],
            'total' => count($out), 'truncated' => false,
        ];
    }

    // ------------------------------------------------------------------ 4: dokumen per pegawai
    private static function dokumenPegawai(array $f, int $limit): array
    {
        $w = ['d.deleted_at IS NULL'];
        $p = [];
        if ($f['pegawai'] !== '') {
            $w[] = 'd.employee_id = ?';
            $p[] = (int)$f['pegawai'];
        }
        if ($f['kategori'] !== '') {
            $w[] = 'd.category_id = ?';
            $p[] = (int)$f['kategori'];
        }
        self::periode($f, 'd.created_at', $w, $p);
        $where = implode(' AND ', $w);
        $from = 'FROM employee_documents d
                 JOIN employees e ON e.id = d.employee_id AND e.deleted_at IS NULL
                 JOIN document_categories c ON c.id = d.category_id';

        $perEmp = [];
        foreach (Database::all("SELECT d.employee_id AS id, COUNT(*) AS n $from WHERE $where GROUP BY d.employee_id", $p) as $r) {
            $perEmp[(int)$r['id']] = (int)$r['n'];
        }
        $total = array_sum($perEmp);

        $rows = Database::all(
            "SELECT d.employee_id, e.nama_lengkap, e.nip, c.nama AS kategori, d.nomor_dokumen, d.tanggal_dokumen,
                    d.tahun, d.nama_asli, d.ukuran_byte, d.created_at
               $from WHERE $where
              ORDER BY e.nama_lengkap, e.id, c.urutan, d.tanggal_dokumen, d.id LIMIT " . (int)$limit,
            $p
        );

        $cols = [
            self::col('No', 'end'), self::col('Pegawai', 'start', true), self::col('NIP', 'start', true),
            self::col('Kategori'), self::col('Nomor Dokumen'), self::col('Tanggal Dokumen'), self::col('Tahun'),
            self::col('Nama Berkas'), self::col('Ukuran', 'end'), self::col('Diunggah'),
        ];

        $out = [];
        $no = 0;
        $cur = null;
        foreach ($rows as $r) {
            if ((int)$r['employee_id'] !== $cur) {
                $cur = (int)$r['employee_id'];
                $no = 0;
                $out[] = ['_group' => $r['nama_lengkap'] . ($r['nip'] ? ' (' . $r['nip'] . ')' : '')
                    . ' - ' . ($perEmp[$cur] ?? 0) . ' dokumen'];
            }
            $out[] = [
                (string)++$no, $r['nama_lengkap'], self::s($r['nip']), $r['kategori'], self::s($r['nomor_dokumen']),
                $r['tanggal_dokumen'] ? fmt_date($r['tanggal_dokumen']) : '', self::s($r['tahun']),
                $r['nama_asli'], self::size($r['ukuran_byte']), fmt_datetime($r['created_at']),
            ];
        }

        return [
            'columns' => $cols, 'rows' => $out,
            'summary' => [['Pegawai', (string)count($perEmp)], ['Total dokumen', (string)$total]],
            'total' => $total, 'truncated' => $total > $limit,
        ];
    }

    // ------------------------------------------------------------------ 5: kelengkapan
    /**
     * Kelengkapan dokumen wajib per pegawai (dipakai juga oleh pencarian).
     * Opsi: aktif ('', '0', '1'), status, q, kelengkapan ('', 'lengkap', 'belum').
     * @return array{0:string,1:array} [SQL dasar, parameter]
     */
    private static function completenessInner(array $o): array
    {
        $w = ['e.deleted_at IS NULL'];
        $p = [];
        $aktif = (string)($o['aktif'] ?? '');
        if ($aktif !== '') {
            $w[] = 'e.status_aktif = ?';
            $p[] = (int)$aktif;
        }
        $status = (string)($o['status'] ?? '');
        if ($status !== '') {
            $w[] = 'e.status_kepegawaian = ?';
            $p[] = $status;
        }
        $q = (string)($o['q'] ?? '');
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $w[] = "(e.nama_lengkap LIKE ? ESCAPE '\\\\' OR e.nip LIKE ? ESCAPE '\\\\')";
            array_push($p, $like, $like);
        }

        $sql = "SELECT e.id, e.nama_lengkap, e.nip, e.jabatan, e.status_kepegawaian, e.status_aktif,
                  (SELECT COUNT(*) FROM category_requirements r
                     JOIN document_categories c ON c.id = r.category_id AND c.is_active = 1
                    WHERE r.status_kepegawaian = e.status_kepegawaian) AS wajib,
                  (SELECT COUNT(DISTINCT d.category_id) FROM employee_documents d
                     JOIN category_requirements r ON r.category_id = d.category_id AND r.status_kepegawaian = e.status_kepegawaian
                     JOIN document_categories c ON c.id = d.category_id AND c.is_active = 1
                    WHERE d.employee_id = e.id AND d.deleted_at IS NULL) AS terpenuhi,
                  (SELECT GROUP_CONCAT(c.nama ORDER BY c.urutan SEPARATOR '; ')
                     FROM category_requirements r
                     JOIN document_categories c ON c.id = r.category_id AND c.is_active = 1
                    WHERE r.status_kepegawaian = e.status_kepegawaian
                      AND NOT EXISTS (SELECT 1 FROM employee_documents d
                                       WHERE d.employee_id = e.id AND d.category_id = r.category_id AND d.deleted_at IS NULL)
                  ) AS kurang
                FROM employees e WHERE " . implode(' AND ', $w);

        return [$sql, $p];
    }

    /** @return array{rows: array, total: int} */
    public static function completenessRows(array $o): array
    {
        [$sql, $p] = self::completenessInner($o);
        $kel = (string)($o['kelengkapan'] ?? '');
        $outer = $kel === 'lengkap' ? 'WHERE t.terpenuhi >= t.wajib' : ($kel === 'belum' ? 'WHERE t.terpenuhi < t.wajib' : '');
        $limit = max(1, (int)($o['limit'] ?? 20));

        $total = (int)Database::one("SELECT COUNT(*) AS n FROM ($sql) t $outer", $p)['n'];
        $rows = Database::all("SELECT t.* FROM ($sql) t $outer ORDER BY t.nama_lengkap, t.id LIMIT $limit", $p);
        foreach ($rows as &$r) {
            $r['wajib'] = (int)$r['wajib'];
            $r['terpenuhi'] = (int)$r['terpenuhi'];
            $r['persen'] = $r['wajib'] > 0 ? (int)round($r['terpenuhi'] / $r['wajib'] * 100) : 100;
            $r['lengkap'] = $r['terpenuhi'] >= $r['wajib'];
        }
        unset($r);

        return ['rows' => $rows, 'total' => $total];
    }

    private static function kelengkapan(array $f, int $limit): array
    {
        $o = ['aktif' => '1', 'status' => $f['status'], 'kelengkapan' => $f['kelengkapan'], 'limit' => $limit];
        $res = self::completenessRows($o);

        // Ringkasan tidak ikut filter kelengkapan agar kedua angka selalu terlihat
        [$sql, $p] = self::completenessInner(['aktif' => '1', 'status' => $f['status']]);
        $sum = Database::one(
            "SELECT COUNT(*) AS total, COALESCE(SUM(t.terpenuhi >= t.wajib), 0) AS lengkap,
                    COALESCE(SUM(t.terpenuhi < t.wajib), 0) AS belum FROM ($sql) t",
            $p
        );

        $cols = [
            self::col('No', 'end'), self::col('Nama Lengkap'), self::col('NIP'), self::col('Status'), self::col('Jabatan'),
            self::col('Wajib', 'end'), self::col('Terpenuhi', 'end'), self::col('Persentase', 'end'),
            self::col('Status Kelengkapan'), self::col('Dokumen Wajib yang Belum Ada'),
        ];
        $out = [];
        $no = 0;
        foreach ($res['rows'] as $r) {
            $out[] = [
                (string)++$no, $r['nama_lengkap'], self::s($r['nip']), self::statusLabel($r['status_kepegawaian']),
                self::s($r['jabatan']), (string)$r['wajib'], (string)$r['terpenuhi'], $r['persen'] . '%',
                $r['lengkap'] ? 'Lengkap' : 'Belum lengkap', self::s($r['kurang']),
            ];
        }

        return [
            'columns' => $cols, 'rows' => $out,
            'summary' => [
                ['Pegawai aktif', (string)(int)$sum['total']],
                ['Arsip lengkap', (string)(int)$sum['lengkap']],
                ['Belum lengkap', (string)(int)$sum['belum']],
            ],
            'total' => $res['total'], 'truncated' => $res['total'] > $limit,
        ];
    }

    // ------------------------------------------------------------------ 6: per tahun
    private static function perTahun(array $f, int $limit): array
    {
        $w = ['d.deleted_at IS NULL'];
        $p = [];
        if ($f['kategori'] !== '') {
            $w[] = 'd.category_id = ?';
            $p[] = (int)$f['kategori'];
        }
        if ($f['tahun_dari'] !== '') {
            $w[] = 'd.tahun >= ?';
            $p[] = (int)$f['tahun_dari'];
        }
        if ($f['tahun_sampai'] !== '') {
            $w[] = 'd.tahun <= ?';
            $p[] = (int)$f['tahun_sampai'];
        }
        $where = implode(' AND ', $w);
        $from = 'FROM employee_documents d
                 JOIN employees e ON e.id = d.employee_id AND e.deleted_at IS NULL
                 JOIN document_categories c ON c.id = d.category_id';

        $perYear = [];
        foreach (Database::all(
            "SELECT d.tahun, COUNT(*) AS jml, COUNT(DISTINCT d.employee_id) AS peg, SUM(d.ukuran_byte) AS ukuran
               $from WHERE $where GROUP BY d.tahun",
            $p
        ) as $r) {
            $perYear[$r['tahun'] === null ? '-' : (string)$r['tahun']] = $r;
        }
        $total = 0;
        foreach ($perYear as $r) {
            $total += (int)$r['jml'];
        }

        $rows = Database::all(
            "SELECT d.tahun, c.nama AS kategori, COUNT(*) AS jml, COUNT(DISTINCT d.employee_id) AS peg, SUM(d.ukuran_byte) AS ukuran
               $from WHERE $where
              GROUP BY d.tahun, c.id, c.nama, c.urutan
              ORDER BY (d.tahun IS NULL), d.tahun DESC, c.urutan LIMIT " . (int)$limit,
            $p
        );

        $cols = [
            self::col('Tahun', 'start', true), self::col('Kategori Dokumen'), self::col('Jumlah Dokumen', 'end'),
            self::col('Jumlah Pegawai', 'end'), self::col('Total Ukuran', 'end'),
        ];
        $out = [];
        $cur = false;
        foreach ($rows as $r) {
            $y = $r['tahun'] === null ? '-' : (string)$r['tahun'];
            if ($y !== $cur) {
                $cur = $y;
                $g = $perYear[$y];
                $out[] = ['_group' => ($y === '-' ? 'Tanpa tahun' : 'Tahun ' . $y) . ' - ' . (int)$g['jml'] . ' dokumen, '
                    . (int)$g['peg'] . ' pegawai, ' . self::size($g['ukuran'])];
            }
            $out[] = [$y === '-' ? 'Tanpa tahun' : $y, $r['kategori'], (string)(int)$r['jml'], (string)(int)$r['peg'], self::size($r['ukuran'])];
        }

        return [
            'columns' => $cols, 'rows' => $out,
            'summary' => [['Jumlah tahun', (string)count($perYear)], ['Total dokumen', (string)$total]],
            'total' => count($rows), 'truncated' => false,
        ];
    }

    // ------------------------------------------------------------------ 7: aktivitas
    private static function aktivitas(array $f, int $limit): array
    {
        $w = ['l.aksi IN (' . implode(',', array_fill(0, count(self::AKSI_ARSIP), '?')) . ')'];
        $p = self::AKSI_ARSIP;

        if (!Auth::can('log.view.all')) {
            $w[] = 'l.user_id = ?'; // Petugas hanya melihat aktivitasnya sendiri
            $p[] = (int)Auth::id();
        } elseif ($f['pengguna'] !== '') {
            $w[] = 'l.user_id = ?';
            $p[] = (int)$f['pengguna'];
        }
        if ($f['aksi'] !== '') {
            $w[] = 'l.aksi = ?';
            $p[] = $f['aksi'];
        }
        self::periode($f, 'l.created_at', $w, $p);
        $where = implode(' AND ', $w);

        $from = "FROM activity_logs l
                 LEFT JOIN users u ON u.id = l.user_id
                 LEFT JOIN employees e1 ON l.objek_tipe = 'employee' AND e1.id = l.objek_id
                 LEFT JOIN employee_documents d ON l.objek_tipe = 'document' AND d.id = l.objek_id
                 LEFT JOIN employees e2 ON e2.id = d.employee_id";

        $total = (int)Database::one("SELECT COUNT(*) AS n $from WHERE $where", $p)['n'];
        $rows = Database::all(
            "SELECT l.created_at, u.nama_lengkap AS pengguna, l.aksi, l.detail,
                    COALESCE(e1.nama_lengkap, e2.nama_lengkap) AS pegawai
               $from WHERE $where ORDER BY l.id DESC LIMIT " . (int)$limit,
            $p
        );

        $cols = [
            self::col('No', 'end'), self::col('Waktu'), self::col('Pengguna'), self::col('Aktivitas'),
            self::col('Pegawai Terkait'), self::col('Keterangan'),
        ];
        $out = [];
        $no = 0;
        foreach ($rows as $r) {
            $out[] = [
                (string)++$no, fmt_datetime($r['created_at']), self::s($r['pengguna']), activity_label($r['aksi']),
                self::s($r['pegawai']), self::s($r['detail']),
            ];
        }

        return [
            'columns' => $cols, 'rows' => $out,
            'summary' => [['Jumlah aktivitas', (string)$total]],
            'total' => $total, 'truncated' => $total > $limit,
        ];
    }
}
