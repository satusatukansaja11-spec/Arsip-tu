<?php
declare(strict_types=1);

final class DocumentController
{
    // ------------------------------------------------------------------ daftar
    public function index(): void
    {
        $f = Document::filters($_GET);
        $result = Document::paginate($f, 10);
        $f['page'] = $result['page'];

        render('documents/index', [
            'title'          => 'Arsip Dokumen',
            'f'              => $f,
            'result'         => $result,
            'categories'     => Document::categories(),
            'filterEmployee' => $f['pegawai'] !== '' ? Employee::find((int)$f['pegawai']) : null,
        ]);
    }

    // ------------------------------------------------------------------ unggah
    public function create(): void
    {
        $v = ['employee_id' => '', 'category_id' => '', 'nomor_dokumen' => '', 'tanggal_dokumen' => '', 'tahun' => '', 'keterangan' => ''];
        $eid = self::str($_GET, 'employee_id');
        if (ctype_digit($eid) && Employee::find((int)$eid)) {
            $v['employee_id'] = $eid;
        }
        $this->uploadForm($v, []);
    }

    public function store(): void
    {
        [$d, $errors] = $this->validateMeta($_POST, true);

        $file = UploadService::validate($_FILES['file'] ?? null);
        if (!$file['ok']) {
            $errors['file'] = $file['error'];
        } elseif (!$errors && Document::duplicateExists((int)$d['employee_id'], (int)$d['category_id'], $file['hash'])) {
            $errors['file'] = 'Berkas identik sudah pernah diunggah untuk pegawai dan kategori ini.';
        }
        if ($errors) {
            http_response_code(422);
            $this->uploadForm($_POST, $errors);
            return;
        }

        $saved = UploadService::store($file);
        if (!$saved['ok']) {
            http_response_code(500);
            $this->uploadForm($_POST, ['file' => $saved['error']]);
            return;
        }

        try {
            $id = Document::create($d, $file, $saved['path'], (int)Auth::id());
        } catch (Throwable $e) {
            UploadService::delete($saved['path']);
            throw $e;
        }

        AuditService::log(Auth::id(), 'dokumen_unggah', 'document', $id, 'pegawai #' . (int)$d['employee_id']);
        flash('success', 'Dokumen berhasil diunggah.');
        redirect('arsip/detail?id=' . $id);
    }

    // ------------------------------------------------------------------ detail
    public function show(): void
    {
        $doc = $this->loadOr404();
        render('documents/show', [
            'title'    => 'Detail Dokumen',
            'doc'      => $doc,
            'versions' => Document::versions((int)$doc['id']),
            'history'  => Auth::can('document.update') ? Document::history((int)$doc['id']) : [],
        ]);
    }

    // ------------------------------------------------------------------ lihat / unduh
    public function view(): void
    {
        $this->serve(true);
    }

    public function download(): void
    {
        $this->serve(false);
    }

    private function serve(bool $inline): void
    {
        $doc = $this->loadOr404();
        $file = $doc;
        $detail = $inline ? 'lihat' : 'unduh';

        $ver = $_GET['v'] ?? '';
        if ($ver !== '') {
            $row = (is_string($ver) && ctype_digit($ver)) ? Document::findVersion((int)$doc['id'], (int)$ver) : null;
            if (!$row) {
                $this->notFound();
            }
            $file = $row;
            $detail .= ' versi ' . (int)$ver;
        }

        AuditService::log(Auth::id(), $inline ? 'dokumen_lihat' : 'dokumen_unduh', 'document', (int)$doc['id'], $detail);
        $this->stream((string)$file['file_path'], (string)$file['mime_type'], (string)$file['nama_asli'], $inline);
    }

    /** Kirim berkas lewat PHP (tanpa URL publik) setelah login + izin diperiksa oleh router. */
    private function stream(string $relPath, string $mime, string $name, bool $inline): never
    {
        $abs = UploadService::absolutePath($relPath);
        if ($abs === null) {
            error_log('DocumentController: berkas fisik tidak ditemukan atau path tidak valid');
            $this->notFound();
        }
        if (!in_array($mime, UploadService::MIME_BY_EXT, true)) {
            $mime = 'application/octet-stream';
        }

        $ascii = trim((string)preg_replace('/[^A-Za-z0-9._-]+/', '_', $name), '_');
        if ($ascii === '' || $ascii[0] === '.') {
            $ascii = 'dokumen' . ($ascii === '' ? '' : $ascii);
        }

        session_write_close(); // jangan menahan sesi selama pengiriman berkas
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string)filesize($abs));
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment')
            . '; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($name));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        if ($inline) {
            header('X-Frame-Options: SAMEORIGIN'); // agar pratinjau PDF di halaman detail dapat tampil
        }
        readfile($abs);
        exit;
    }

    // ------------------------------------------------------------------ ubah metadata
    public function edit(): void
    {
        $doc = $this->loadOr404();
        $v = $doc;
        $v['tanggal_dokumen'] = (string)($doc['tanggal_dokumen'] ?? '');
        $v['tahun'] = (string)($doc['tahun'] ?? '');
        $this->editForm($doc, $v, []);
    }

    public function update(): void
    {
        $doc = $this->loadOr404(true);
        [$d, $errors] = $this->validateMeta($_POST, false);
        if ($errors) {
            http_response_code(422);
            $this->editForm($doc, $_POST, $errors);
            return;
        }

        $labels = [
            'category_id' => 'kategori', 'nomor_dokumen' => 'nomor', 'tanggal_dokumen' => 'tanggal',
            'tahun' => 'tahun', 'keterangan' => 'keterangan',
        ];
        $changed = [];
        foreach ($labels as $col => $label) {
            if ((string)($doc[$col] ?? '') !== (string)($d[$col] ?? '')) {
                $changed[] = $label;
            }
        }
        if (!$changed) {
            flash('info', 'Tidak ada perubahan data.');
            redirect('arsip/detail?id=' . $doc['id']);
        }

        Document::updateMeta((int)$doc['id'], $d);
        AuditService::log(Auth::id(), 'dokumen_ubah', 'document', (int)$doc['id'], 'Kolom: ' . implode(', ', $changed));
        flash('success', 'Data dokumen berhasil diperbarui.');
        redirect('arsip/detail?id=' . $doc['id']);
    }

    // ------------------------------------------------------------------ ganti berkas
    public function replace(): void
    {
        $doc = $this->loadOr404(true);
        $back = 'arsip/detail?id=' . (int)$doc['id'];

        $file = UploadService::validate($_FILES['file'] ?? null);
        if (!$file['ok']) {
            flash('danger', $file['error']);
            redirect($back);
        }
        if ($file['hash'] === $doc['hash_sha256']) {
            flash('danger', 'Berkas yang dipilih sama dengan berkas saat ini.');
            redirect($back);
        }

        $saved = UploadService::store($file);
        if (!$saved['ok']) {
            flash('danger', $saved['error']);
            redirect($back);
        }

        try {
            $ver = Document::replaceFile((int)$doc['id'], $file, $saved['path'], (int)Auth::id());
        } catch (Throwable $e) {
            UploadService::delete($saved['path']);
            error_log('DocumentController::replace: ' . $e->getMessage());
            flash('danger', 'Berkas gagal diganti. Silakan coba lagi.');
            redirect($back);
        }

        AuditService::log(Auth::id(), 'dokumen_ganti', 'document', (int)$doc['id'], 'berkas lama menjadi versi ' . $ver);
        flash('success', 'Berkas berhasil diganti. Berkas lama tersimpan sebagai versi ' . $ver . '.');
        redirect($back);
    }

    // ------------------------------------------------------------------ arsipkan
    public function archive(): void
    {
        $doc = $this->loadOr404(true);
        Document::archive((int)$doc['id']);
        AuditService::log(Auth::id(), 'dokumen_arsip', 'document', (int)$doc['id'], 'pegawai #' . (int)$doc['employee_id']);
        flash('success', 'Dokumen diarsipkan. Berkas fisik tetap tersimpan.');
        redirect('arsip');
    }

    // ------------------------------------------------------------------ util
    private static function str(array $in, string $key): string
    {
        $v = $in[$key] ?? '';
        return is_scalar($v) ? trim((string)$v) : '';
    }

    private function notFound(): never
    {
        http_response_code(404);
        render_error(404);
        exit;
    }

    private function loadOr404(bool $fromPost = false): array
    {
        $raw = $fromPost ? ($_POST['id'] ?? '') : ($_GET['id'] ?? '');
        $id = (is_string($raw) && ctype_digit($raw) && strlen($raw) <= 10) ? (int)$raw : 0;
        $doc = $id > 0 ? Document::find($id) : null;
        if (!$doc) {
            $this->notFound();
        }
        return $doc;
    }

    private function uploadForm(array $values, array $errors): void
    {
        render('documents/form', [
            'title'      => 'Unggah Dokumen',
            'v'          => $values,
            'errors'     => $errors,
            'employees'  => Document::employeesForSelect(),
            'categories' => Document::categories(),
            'exts'       => UploadService::allowedExtensions(),
            'maxMb'      => (int)(UploadService::maxBytes() / 1048576),
        ]);
    }

    private function editForm(array $doc, array $values, array $errors): void
    {
        render('documents/edit', [
            'title'      => 'Ubah Data Dokumen',
            'doc'        => $doc,
            'v'          => $values,
            'errors'     => $errors,
            'categories' => Document::categories(),
        ]);
    }

    private static function validDate(string $s): bool
    {
        $d = DateTime::createFromFormat('!Y-m-d', $s);
        return $d !== false && $d->format('Y-m-d') === $s;
    }

    /** Teks satu baris atau beberapa baris, dibatasi panjang dan karakter kontrol. */
    private static function cleanText(array $in, string $key, int $max, string $label, array &$errors): ?string
    {
        $v = self::str($in, $key);
        if ($v === '') {
            return null;
        }
        if (!mb_check_encoding($v, 'UTF-8') || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $v)) {
            $errors[$key] = "$label mengandung karakter tidak valid.";
            return $v;
        }
        $v = (string)preg_replace('/\s+/u', ' ', $v);
        if (mb_strlen($v) > $max) {
            $errors[$key] = "$label maksimal $max karakter.";
        }
        return $v;
    }

    /** @return array{0: array, 1: array} [data, errors] */
    private function validateMeta(array $in, bool $withEmployee): array
    {
        $d = [];
        $e = [];

        if ($withEmployee) {
            $eid = self::str($in, 'employee_id');
            if (!ctype_digit($eid) || strlen($eid) > 10 || !Employee::find((int)$eid)) {
                $e['employee_id'] = 'Pilih pegawai.';
            }
            $d['employee_id'] = ctype_digit($eid) ? (int)$eid : 0;
        }

        $cid = self::str($in, 'category_id');
        if (!ctype_digit($cid) || strlen($cid) > 6
            || !Database::one('SELECT id FROM document_categories WHERE id = ? AND is_active = 1', [(int)$cid])) {
            $e['category_id'] = 'Pilih kategori dokumen.';
        }
        $d['category_id'] = ctype_digit($cid) ? (int)$cid : 0;

        $d['nomor_dokumen'] = self::cleanText($in, 'nomor_dokumen', 100, 'Nomor dokumen', $e);
        $d['keterangan'] = self::cleanText($in, 'keterangan', 255, 'Keterangan', $e);

        $tg = self::str($in, 'tanggal_dokumen');
        if ($tg === '') {
            $d['tanggal_dokumen'] = null;
        } else {
            if (!self::validDate($tg) || $tg < '1950-01-01' || $tg > date('Y-m-d')) {
                $e['tanggal_dokumen'] = 'Tanggal dokumen tidak valid.';
            }
            $d['tanggal_dokumen'] = $tg;
        }

        $th = self::str($in, 'tahun');
        $dateOk = $d['tanggal_dokumen'] !== null && !isset($e['tanggal_dokumen']);
        if ($th === '') {
            $d['tahun'] = $dateOk ? (int)substr((string)$d['tanggal_dokumen'], 0, 4) : null;
        } elseif (!preg_match('/^\d{4}$/', $th) || (int)$th < 1950 || (int)$th > (int)date('Y') + 1) {
            $e['tahun'] = 'Tahun tidak valid.';
            $d['tahun'] = null;
        } else {
            if ($dateOk && (int)substr((string)$d['tanggal_dokumen'], 0, 4) !== (int)$th) {
                $e['tahun'] = 'Tahun tidak sesuai dengan tanggal dokumen.';
            }
            $d['tahun'] = (int)$th;
        }

        return [$d, $e];
    }
}
