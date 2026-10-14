<?php
declare(strict_types=1);

final class ReportController
{
    public function index(): void
    {
        render('reports/index', [
            'title'   => 'Laporan',
            'reports' => ReportService::available(),
        ]);
    }

    public function show(): void
    {
        [$key, $def] = $this->resolve();
        $f = ReportService::filters($_GET);
        $data = ReportService::build($key, $f, ReportService::MAX_ROWS_HTML);

        render('reports/show', [
            'title'    => $def['judul'],
            'key'      => $key,
            'def'      => $def,
            'f'        => $f,
            'report'   => $data,
            'options'  => ReportService::options($def['filter']),
            'describe' => ReportService::describe($key, $f),
            'maxRows'  => ReportService::MAX_ROWS_HTML,
        ]);
    }

    public function export(): void
    {
        [$key] = $this->resolve();
        $f = ReportService::filters($_GET);
        $data = ReportService::build($key, $f, ReportService::MAX_ROWS_CSV);

        $count = 0;
        foreach ($data['rows'] as $r) {
            if (!isset($r['_group'])) {
                $count++;
            }
        }
        AuditService::log(Auth::id(), 'laporan_ekspor', 'report', null, $key . ' (' . $count . ' baris)');

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="laporan-' . $key . '-' . date('Ymd-His') . '.csv"');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8

        fputcsv($out, array_map(static fn(array $c): string => $c['l'], $data['columns']), ',', '"', '');
        foreach ($data['rows'] as $r) {
            if (isset($r['_group'])) {
                continue; // baris judul grup hanya untuk tampilan
            }
            fputcsv($out, array_map(static fn($v): string => csv_safe((string)$v), $r), ',', '"', '');
        }
        if ($data['truncated']) {
            fputcsv($out, ['Catatan: hanya ' . ReportService::MAX_ROWS_CSV . ' baris pertama dari ' . $data['total'] . ' baris yang diekspor. Persempit dengan filter.'], ',', '"', '');
        }
        fclose($out);
    }

    /** Validasi parameter jenis laporan dan izin akses (server-side). */
    private function resolve(): array
    {
        $key = $_GET['jenis'] ?? '';
        $defs = ReportService::definitions();
        if (!is_string($key) || !isset($defs[$key])) {
            http_response_code(404);
            render_error(404);
            exit;
        }
        if (!ReportService::allowed($key)) {
            AuditService::log(Auth::id(), 'akses_ditolak', 'report', null, $key);
            http_response_code(403);
            render_error(403);
            exit;
        }
        return [$key, $defs[$key]];
    }
}
