<?php
declare(strict_types=1);

/**
 * Semua angka dashboard berasal dari database (tanpa data dummy).
 */
final class DashboardService
{
    public static function data(): array
    {
        $byStatus = ['PNS' => 0, 'PPPK' => 0, 'HONORER' => 0];
        foreach (Database::all(
            'SELECT status_kepegawaian AS s, COUNT(*) AS n FROM employees
              WHERE deleted_at IS NULL AND status_aktif = 1 GROUP BY status_kepegawaian'
        ) as $r) {
            $byStatus[$r['s']] = (int)$r['n'];
        }

        $docs = Database::one(
            'SELECT COUNT(*) AS total,
                    COALESCE(SUM(created_at >= (NOW() - INTERVAL 7 DAY)), 0) AS baru
               FROM employee_documents WHERE deleted_at IS NULL'
        );

        $byCategory = Database::all(
            'SELECT c.nama, COUNT(d.id) AS n
               FROM document_categories c
               LEFT JOIN employee_documents d ON d.category_id = c.id AND d.deleted_at IS NULL
              WHERE c.is_active = 1
              GROUP BY c.id, c.nama, c.urutan
              ORDER BY c.urutan, c.id'
        );

        $recentDocs = [];
        if (Auth::can('document.view')) {
            $recentDocs = Database::all(
                'SELECT d.id, d.created_at, d.nomor_dokumen, e.id AS employee_id, e.nama_lengkap, c.nama AS kategori
                   FROM employee_documents d
                   JOIN employees e ON e.id = d.employee_id
                   JOIN document_categories c ON c.id = d.category_id
                  WHERE d.deleted_at IS NULL AND e.deleted_at IS NULL
                  ORDER BY d.created_at DESC, d.id DESC
                  LIMIT 8'
            );
        }

        $activities = [];
        if (Auth::can('log.view.all')) {
            $activities = Database::all(
                'SELECT l.aksi, l.created_at, u.nama_lengkap AS pengguna
                   FROM activity_logs l LEFT JOIN users u ON u.id = l.user_id
                  ORDER BY l.id DESC LIMIT 8'
            );
        } elseif (Auth::can('log.view.own')) {
            $activities = Database::all(
                'SELECT l.aksi, l.created_at, u.nama_lengkap AS pengguna
                   FROM activity_logs l LEFT JOIN users u ON u.id = l.user_id
                  WHERE l.user_id = ?
                  ORDER BY l.id DESC LIMIT 8',
                [Auth::id()]
            );
        }

        $completeness = CompletenessService::summary();

        return [
            'total'         => array_sum($byStatus),
            'byStatus'      => $byStatus,
            'docTotal'      => (int)($docs['total'] ?? 0),
            'docBaru'       => (int)($docs['baru'] ?? 0),
            'lengkap'       => $completeness['lengkap'],
            'belumLengkap'  => $completeness['belum'],
            'byCategory'    => $byCategory,
            'recentDocs'    => $recentDocs,
            'activities'    => $activities,
        ];
    }
}
