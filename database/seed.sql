-- ============================================================
-- Arsip Pegawai TU - MTsN 1 Alor
-- Data awal: role, kategori dokumen, aturan kewajiban, pengaturan.
-- TIDAK ada akun pengguna di sini. Admin pertama dibuat lewat
-- `php bin/install.php` (tanpa kata sandi bawaan).
-- ============================================================
USE arsip_tu;
SET NAMES utf8mb4;

INSERT INTO roles (id, kode, nama, deskripsi) VALUES
  (1, 'admin',      'Administrator',        'Akses penuh ke seluruh fitur dan pengaturan'),
  (2, 'petugas_tu', 'Petugas TU',           'Mengelola data pegawai dan arsip dokumen'),
  (3, 'pimpinan',   'Pimpinan / Kepala',    'Melihat dashboard, data, laporan, dan mengunduh dokumen');

INSERT INTO document_categories (id, nama, urutan) VALUES
  (1,  'SK Pengangkatan',               1),
  (2,  'SK Kenaikan Pangkat',           2),
  (3,  'SK Kenaikan Gaji Berkala',      3),
  (4,  'Ijazah dan Transkrip Nilai',    4),
  (5,  'Sertifikat Pelatihan',          5),
  (6,  'Sertifikat Pendidik',           6),
  (7,  'Dokumen Identitas',             7),
  (8,  'Dokumen Cuti',                  8),
  (9,  'Dokumen Pensiun',               9),
  (10, 'Dokumen Mutasi',               10),
  (11, 'Penilaian Kinerja',            11),
  (12, 'Dokumen Kepegawaian Lainnya',  12);

-- Kategori WAJIB per status kepegawaian.
-- Kategori yang tidak tercantum = "tidak berlaku" (bukan "hilang").
-- Catatan: silakan sesuaikan dengan kebijakan madrasah.
INSERT INTO category_requirements (category_id, status_kepegawaian) VALUES
  -- PNS: pengangkatan, pangkat, gaji berkala, ijazah, identitas, kinerja
  (1, 'PNS'), (2, 'PNS'), (3, 'PNS'), (4, 'PNS'), (7, 'PNS'), (11, 'PNS'),
  -- PPPK: pengangkatan, ijazah, identitas, kinerja
  (1, 'PPPK'), (4, 'PPPK'), (7, 'PPPK'), (11, 'PPPK'),
  -- Honorer: ijazah dan identitas
  (4, 'HONORER'), (7, 'HONORER');

INSERT INTO application_settings (setting_key, setting_value) VALUES
  ('nama_aplikasi',        'Arsip Pegawai TU'),
  ('nama_sekolah',         'MTsN 1 Alor'),
  ('alamat_sekolah',       ''),
  ('max_upload_mb',        '5'),
  ('allowed_extensions',   'pdf,jpg,jpeg,png'),
  ('session_lifetime_min', '30');
