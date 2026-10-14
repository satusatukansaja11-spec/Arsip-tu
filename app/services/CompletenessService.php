<?php
declare(strict_types=1);

/**
 * Kelengkapan arsip berdasarkan aturan category_requirements.
 * Kategori yang tidak diwajibkan untuk status tertentu = "tidak berlaku", bukan "hilang".
 */
final class CompletenessService
{
    /** Ringkasan seluruh pegawai aktif: ['lengkap' => n, 'belum' => n]. */
    public static function summary(): array
    {
        $row = Database::one(
            'SELECT
                COALESCE(SUM(t.terpenuhi >= t.wajib), 0) AS lengkap,
                COALESCE(SUM(t.terpenuhi <  t.wajib), 0) AS belum
             FROM (
               SELECT
                 (SELECT COUNT(*) FROM category_requirements r
                   WHERE r.status_kepegawaian = e.status_kepegawaian) AS wajib,
                 (SELECT COUNT(DISTINCT d.category_id)
                    FROM employee_documents d
                    JOIN category_requirements r
                      ON r.category_id = d.category_id AND r.status_kepegawaian = e.status_kepegawaian
                   WHERE d.employee_id = e.id AND d.deleted_at IS NULL) AS terpenuhi
               FROM employees e
               WHERE e.deleted_at IS NULL AND e.status_aktif = 1
             ) t'
        );
        return ['lengkap' => (int)($row['lengkap'] ?? 0), 'belum' => (int)($row['belum'] ?? 0)];
    }

    /**
     * Rincian per kategori untuk satu pegawai.
     * status: lengkap | belum | tersedia (tidak wajib tapi ada) | tidak_berlaku
     */
    public static function forEmployee(array $employee): array
    {
        $cats = Database::all(
            'SELECT c.id, c.nama,
                    (SELECT COUNT(*) FROM category_requirements r
                      WHERE r.category_id = c.id AND r.status_kepegawaian = ?) AS wajib,
                    (SELECT COUNT(*) FROM employee_documents d
                      WHERE d.category_id = c.id AND d.employee_id = ? AND d.deleted_at IS NULL) AS jumlah
               FROM document_categories c
              WHERE c.is_active = 1
              ORDER BY c.urutan, c.id',
            [$employee['status_kepegawaian'], $employee['id']]
        );

        $items = [];
        $wajib = 0;
        $terpenuhi = 0;
        foreach ($cats as $c) {
            $isWajib = (int)$c['wajib'] > 0;
            $ada = (int)$c['jumlah'] > 0;
            if ($isWajib) {
                $wajib++;
                $status = $ada ? 'lengkap' : 'belum';
                $terpenuhi += $ada ? 1 : 0;
            } else {
                $status = $ada ? 'tersedia' : 'tidak_berlaku';
            }
            $items[] = ['nama' => $c['nama'], 'status' => $status, 'jumlah' => (int)$c['jumlah']];
        }

        return [
            'items'     => $items,
            'wajib'     => $wajib,
            'terpenuhi' => $terpenuhi,
            'persen'    => $wajib > 0 ? (int)round($terpenuhi / $wajib * 100) : 100,
        ];
    }
}
