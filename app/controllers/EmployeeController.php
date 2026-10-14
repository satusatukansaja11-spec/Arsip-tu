<?php
declare(strict_types=1);

final class EmployeeController
{
    private const LABELS = [
        'nip' => 'NIP', 'nik' => 'NIK', 'nama_lengkap' => 'Nama lengkap', 'tempat_lahir' => 'Tempat lahir',
        'tanggal_lahir' => 'Tanggal lahir', 'jenis_kelamin' => 'Jenis kelamin', 'pangkat_golongan' => 'Pangkat/golongan',
        'jabatan' => 'Jabatan', 'unit_kerja' => 'Unit kerja', 'status_kepegawaian' => 'Status kepegawaian',
        'pendidikan_terakhir' => 'Pendidikan terakhir', 'telepon' => 'Telepon', 'email' => 'Email',
        'alamat' => 'Alamat', 'tanggal_mulai' => 'Tanggal mulai bekerja', 'status_aktif' => 'Status aktif', 'catatan' => 'Catatan',
    ];

    // ------------------------------------------------------------------ daftar
    public function index(): void
    {
        $f = Employee::filters($_GET);
        $result = Employee::paginate($f, 10);
        $f['page'] = $result['page'];

        render('employees/index', [
            'title'    => 'Data Pegawai',
            'f'        => $f,
            'result'   => $result,
            'jabatans' => Employee::distinct('jabatan'),
            'units'    => Employee::distinct('unit_kerja'),
        ]);
    }

    // ------------------------------------------------------------------ ekspor CSV
    public function export(): void
    {
        $f = Employee::filters($_GET);
        $rows = Employee::allForExport($f);
        $sensitive = Auth::can('employee.view.sensitive');

        AuditService::log(Auth::id(), 'pegawai_ekspor', 'employee', null, count($rows) . ' baris');

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="pegawai-' . date('Ymd-His') . '.csv"');
        header('Cache-Control: no-store');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
        $head = ['No', 'Nama Lengkap', 'NIP', 'Jabatan', 'Unit Kerja', 'Status Kepegawaian', 'Pendidikan', 'Aktif'];
        if ($sensitive) {
            array_push($head, 'Tanggal Lahir', 'Telepon', 'Email');
        }
        fputcsv($out, $head);

        $no = 0;
        foreach ($rows as $r) {
            $line = [
                ++$no,
                csv_safe($r['nama_lengkap']), csv_safe($r['nip']), csv_safe($r['jabatan']), csv_safe($r['unit_kerja']),
                $r['status_kepegawaian'], csv_safe($r['pendidikan_terakhir']), $r['status_aktif'] ? 'Aktif' : 'Tidak aktif',
            ];
            if ($sensitive) {
                array_push($line, fmt_date($r['tanggal_lahir']), csv_safe($r['telepon']), csv_safe($r['email']));
            }
            fputcsv($out, $line);
        }
        fclose($out);
    }

    // ------------------------------------------------------------------ detail
    public function show(): void
    {
        $emp = $this->loadOr404();

        render('employees/detail', [
            'title'        => $emp['nama_lengkap'],
            'emp'          => $emp,
            'sensitive'    => Auth::can('employee.view.sensitive'),
            'documents'    => Auth::can('document.view') ? Employee::documents((int)$emp['id']) : [],
            'completeness' => CompletenessService::forEmployee($emp),
            'history'      => Employee::history((int)$emp['id']),
        ]);
    }

    // ------------------------------------------------------------------ tambah
    public function create(): void
    {
        $this->form('create', ['status_aktif' => '1'], []);
    }

    public function store(): void
    {
        [$data, $errors] = $this->validate($_POST, null);
        if ($errors) {
            http_response_code(422);
            $this->form('create', $_POST, $errors);
            return;
        }

        $id = Employee::create($data, (int)Auth::id());
        AuditService::log(Auth::id(), 'pegawai_tambah', 'employee', $id);
        flash('success', 'Data pegawai berhasil ditambahkan.');
        redirect('pegawai/detail?id=' . $id);
    }

    // ------------------------------------------------------------------ ubah
    public function edit(): void
    {
        $emp = $this->loadOr404();
        $values = $emp;
        $values['status_aktif'] = (string)(int)$emp['status_aktif'];
        $this->form('edit', $values, [], (int)$emp['id']);
    }

    public function update(): void
    {
        $emp = $this->loadOr404(true);
        [$data, $errors] = $this->validate($_POST, (int)$emp['id']);
        if ($errors) {
            http_response_code(422);
            $this->form('edit', $_POST, $errors, (int)$emp['id']);
            return;
        }

        $changed = [];
        foreach (Employee::FIELDS as $field) {
            if ((string)($emp[$field] ?? '') !== (string)($data[$field] ?? '')) {
                $changed[] = $field;
            }
        }
        if (!$changed) {
            flash('info', 'Tidak ada perubahan data.');
            redirect('pegawai/detail?id=' . $emp['id']);
        }

        Employee::update((int)$emp['id'], $data);
        AuditService::log(Auth::id(), 'pegawai_ubah', 'employee', (int)$emp['id'], 'Kolom: ' . implode(', ', $changed));
        flash('success', 'Data pegawai berhasil diperbarui.');
        redirect('pegawai/detail?id=' . $emp['id']);
    }

    // ------------------------------------------------------------------ arsipkan
    public function archive(): void
    {
        $emp = $this->loadOr404(true);
        Employee::archive((int)$emp['id']);
        AuditService::log(Auth::id(), 'pegawai_arsip', 'employee', (int)$emp['id']);
        flash('success', 'Pegawai telah diarsipkan. Data dan dokumennya tetap tersimpan.');
        redirect('pegawai');
    }

    // ------------------------------------------------------------------ util
    /** Ambil pegawai dari id (GET atau POST). 404 bila tidak ada / sudah diarsipkan. */
    private function loadOr404(bool $fromPost = false): array
    {
        $raw = $fromPost ? ($_POST['id'] ?? '') : ($_GET['id'] ?? '');
        $id = (is_string($raw) && ctype_digit($raw)) ? (int)$raw : 0;
        $emp = $id > 0 ? Employee::find($id) : null;
        if (!$emp) {
            http_response_code(404);
            render_error(404);
            exit;
        }
        return $emp;
    }

    private function form(string $mode, array $values, array $errors, ?int $id = null): void
    {
        render('employees/form', [
            'title'  => $mode === 'create' ? 'Tambah Pegawai' : 'Ubah Data Pegawai',
            'mode'   => $mode,
            'v'      => $values,
            'errors' => $errors,
            'id'     => $id,
        ]);
    }

    private static function validDate(string $s): bool
    {
        $d = DateTime::createFromFormat('!Y-m-d', $s);
        return $d !== false && $d->format('Y-m-d') === $s;
    }

    /** @return array{0: array, 1: array} [data, errors] */
    private function validate(array $in, ?int $exceptId): array
    {
        $d = [];
        $e = [];
        $badChars = '/[\x00-\x08\x0B\x0C\x0E-\x1F]/';

        $text = function (string $key, int $max, bool $required = false, bool $multiline = false) use ($in, &$d, &$e, $badChars): void {
            $v = trim((string)($in[$key] ?? ''));
            if (!$multiline) {
                $v = preg_replace('/\s+/u', ' ', $v) ?? '';
            }
            if ($v === '') {
                if ($required) {
                    $e[$key] = self::LABELS[$key] . ' wajib diisi.';
                }
                $d[$key] = null;
                return;
            }
            if (preg_match($badChars, $v) || !mb_check_encoding($v, 'UTF-8')) {
                $e[$key] = self::LABELS[$key] . ' mengandung karakter tidak valid.';
            } elseif (mb_strlen($v) > $max) {
                $e[$key] = self::LABELS[$key] . " maksimal $max karakter.";
            }
            $d[$key] = $v;
        };

        $text('nama_lengkap', 120, true);
        $text('tempat_lahir', 80);
        $text('pangkat_golongan', 60);
        $text('jabatan', 100);
        $text('unit_kerja', 100);
        $text('alamat', 500, false, true);
        $text('catatan', 1000, false, true);

        // NIP
        $nip = preg_replace('/\s+/', '', (string)($in['nip'] ?? ''));
        if ($nip === '') {
            $d['nip'] = null;
        } elseif (!preg_match('/^\d{8,25}$/', $nip)) {
            $e['nip'] = 'NIP harus berupa 8-25 digit angka.';
            $d['nip'] = $nip;
        } elseif (Employee::nipTaken($nip, $exceptId)) {
            $e['nip'] = 'NIP sudah terdaftar.';
            $d['nip'] = $nip;
        } else {
            $d['nip'] = $nip;
        }

        // NIK
        $nik = preg_replace('/\s+/', '', (string)($in['nik'] ?? ''));
        if ($nik === '') {
            $d['nik'] = null;
        } elseif (!preg_match('/^\d{16}$/', $nik)) {
            $e['nik'] = 'NIK harus 16 digit angka.';
            $d['nik'] = $nik;
        } else {
            $d['nik'] = $nik;
        }

        // Tanggal lahir
        $tl = trim((string)($in['tanggal_lahir'] ?? ''));
        if ($tl === '') {
            $d['tanggal_lahir'] = null;
        } elseif (!self::validDate($tl) || $tl > date('Y-m-d') || $tl < '1900-01-01') {
            $e['tanggal_lahir'] = 'Tanggal lahir tidak valid.';
            $d['tanggal_lahir'] = $tl;
        } else {
            $d['tanggal_lahir'] = $tl;
        }

        // Tanggal mulai
        $tm = trim((string)($in['tanggal_mulai'] ?? ''));
        if ($tm === '') {
            $d['tanggal_mulai'] = null;
        } elseif (!self::validDate($tm) || $tm < '1950-01-01' || $tm > date('Y-m-d', strtotime('+1 year'))) {
            $e['tanggal_mulai'] = 'Tanggal mulai bekerja tidak valid.';
            $d['tanggal_mulai'] = $tm;
        } else {
            $d['tanggal_mulai'] = $tm;
        }

        // Jenis kelamin
        $jk = (string)($in['jenis_kelamin'] ?? '');
        if ($jk !== '' && !in_array($jk, ['L', 'P'], true)) {
            $e['jenis_kelamin'] = 'Jenis kelamin tidak valid.';
        }
        $d['jenis_kelamin'] = $jk === '' ? null : $jk;

        // Status kepegawaian
        $st = (string)($in['status_kepegawaian'] ?? '');
        if (!in_array($st, Employee::STATUSES, true)) {
            $e['status_kepegawaian'] = 'Pilih status kepegawaian.';
        }
        $d['status_kepegawaian'] = $st;

        // Pendidikan
        $pd = (string)($in['pendidikan_terakhir'] ?? '');
        if ($pd !== '' && !in_array($pd, Employee::EDUCATION, true)) {
            $e['pendidikan_terakhir'] = 'Pendidikan terakhir tidak valid.';
        }
        $d['pendidikan_terakhir'] = $pd === '' ? null : $pd;

        // Telepon
        $tel = trim((string)($in['telepon'] ?? ''));
        if ($tel !== '' && !preg_match('/^[0-9+\-\s()]{6,25}$/', $tel)) {
            $e['telepon'] = 'Nomor telepon tidak valid.';
        }
        $d['telepon'] = $tel === '' ? null : $tel;

        // Email
        $em = trim((string)($in['email'] ?? ''));
        if ($em !== '' && (mb_strlen($em) > 150 || !filter_var($em, FILTER_VALIDATE_EMAIL))) {
            $e['email'] = 'Alamat email tidak valid.';
        }
        $d['email'] = $em === '' ? null : $em;

        $d['status_aktif'] = (($in['status_aktif'] ?? '') === '1') ? 1 : 0;

        return [$d, $e];
    }
}
