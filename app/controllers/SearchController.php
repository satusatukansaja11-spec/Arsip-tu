<?php
declare(strict_types=1);

/**
 * Pencarian cepat: pegawai (nama/NIP + status kelengkapan) dan dokumen (nomor, kategori, tahun).
 * Hasil dibatasi sesuai izin: bagian pegawai butuh employee.view, bagian dokumen butuh document.view.
 * Data yang ditampilkan hanya yang non-sensitif.
 */
final class SearchController
{
    public function index(): void
    {
        $canEmp = Auth::can('employee.view');
        $canDoc = Auth::can('document.view');
        if (!$canEmp && !$canDoc) {
            http_response_code(403);
            render_error(403);
            exit;
        }

        $q = self::str('q', 100);
        $kategori = self::str('kategori', 6);
        $kategori = ctype_digit($kategori) ? $kategori : '';
        $tahun = self::str('tahun', 4);
        $tahun = preg_match('/^\d{4}$/', $tahun) === 1 ? $tahun : '';
        $kel = self::str('kelengkapan', 10);
        $kel = in_array($kel, ['lengkap', 'belum'], true) ? $kel : '';

        $employees = null;
        $documents = null;

        if ($canEmp && ($q !== '' || $kel !== '')) {
            $employees = ReportService::completenessRows(['q' => $q, 'kelengkapan' => $kel, 'aktif' => '', 'status' => '', 'limit' => 20]);
        }
        // Filter kelengkapan hanya berlaku untuk pegawai
        if ($canDoc && $kel === '' && ($q !== '' || $kategori !== '' || $tahun !== '')) {
            $documents = Document::paginate(Document::filters(['q' => $q, 'kategori' => $kategori, 'tahun' => $tahun]), 20);
        }

        render('search/index', [
            'title'      => 'Pencarian',
            'q'          => $q,
            'kategori'   => $kategori,
            'tahun'      => $tahun,
            'kel'        => $kel,
            'canEmp'     => $canEmp,
            'canDoc'     => $canDoc,
            'employees'  => $employees,
            'documents'  => $documents,
            'categories' => $canDoc ? Document::categories() : [],
            'searched'   => $q !== '' || $kategori !== '' || $tahun !== '' || $kel !== '',
        ]);
    }

    private static function str(string $key, int $max): string
    {
        $v = $_GET[$key] ?? '';
        return is_scalar($v) ? mb_substr(trim((string)$v), 0, $max) : '';
    }
}
