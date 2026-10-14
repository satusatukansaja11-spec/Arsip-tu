-- ============================================================
-- Data contoh FIKTIF untuk pengujian. Jangan dipakai di produksi.
-- Impor setelah schema.sql dan seed.sql.
-- Semua nama, NIP, dan kontak di bawah ini rekaan.
-- ============================================================
USE arsip_tu;
SET NAMES utf8mb4;

INSERT INTO employees
  (nip, nama_lengkap, tempat_lahir, tanggal_lahir, jenis_kelamin, pangkat_golongan, jabatan, unit_kerja,
   status_kepegawaian, pendidikan_terakhir, telepon, email, alamat, tanggal_mulai, status_aktif) VALUES
  ('198001012005011001', 'Contoh Satu',    'Kalabahi', '1980-01-01', 'L', 'Penata (III/c)',      'Guru Madya',          'Madrasah',       'PNS',     'S1', '081200000001', 'contoh1@example.test', 'Alamat contoh 1', '2005-01-01', 1),
  ('198505052010012002', 'Contoh Dua',     'Kupang',   '1985-05-05', 'P', 'Penata Muda (III/a)', 'Guru Pertama',        'Madrasah',       'PNS',     'S1', '081200000002', 'contoh2@example.test', 'Alamat contoh 2', '2010-01-01', 1),
  ('199003032021012003', 'Contoh Tiga',    'Alor',     '1990-03-03', 'P', NULL,                  'Guru Pertama',        'Madrasah',       'PPPK',    'S1', '081200000003', 'contoh3@example.test', 'Alamat contoh 3', '2021-03-01', 1),
  ('199207072022011004', 'Contoh Empat',   'Alor',     '1992-07-07', 'L', NULL,                  'Guru Pertama',        'Madrasah',       'PPPK',    'S1', '081200000004', 'contoh4@example.test', 'Alamat contoh 4', '2022-01-01', 1),
  (NULL,                 'Contoh Lima',    'Kalabahi', '1995-09-09', 'L', NULL,                  'Staf Tata Usaha',     'Tata Usaha',     'HONORER', 'SMA/MA/SMK', '081200000005', NULL, 'Alamat contoh 5', '2018-07-01', 1),
  (NULL,                 'Contoh Enam',    'Kalabahi', '1998-11-11', 'P', NULL,                  'Staf Perpustakaan',   'Perpustakaan',   'HONORER', 'D3', '081200000006', NULL, 'Alamat contoh 6', '2020-07-01', 1),
  ('197512121999031005', 'Contoh Tujuh',   'Alor',     '1975-12-12', 'L', 'Pembina (IV/a)',      'Kepala Madrasah',     'Madrasah',       'PNS',     'S2', '081200000007', 'contoh7@example.test', 'Alamat contoh 7', '1999-03-01', 1),
  (NULL,                 'Contoh Delapan', 'Kupang',   '1993-04-04', 'P', NULL,                  'Penjaga Madrasah',    'Tata Usaha',     'HONORER', 'SMP', '081200000008', NULL, 'Alamat contoh 8', '2019-01-01', 0);
