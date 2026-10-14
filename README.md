# Arsip Pegawai TU - MTsN 1 Alor

Aplikasi web pengelolaan data pegawai dan arsip dokumen kepegawaian.
PHP Native + MySQL/MariaDB + Bootstrap 5.

> Status: **Tahap 1-5 selesai** (database, fondasi, login/hak akses, dashboard, modul data pegawai).
> Modul arsip dokumen, laporan, backup, dan pengujian menyeluruh menyusul pada tahap berikutnya.
> Belum ada klaim "siap produksi"; pengujian menyeluruh dilakukan di Tahap 9.

## Kebutuhan

- PHP 8.1+ (ekstensi: pdo_mysql, fileinfo, mbstring)
- MySQL 5.7+ atau MariaDB 10.3+
- Apache dengan mod_rewrite (XAMPP sudah termasuk)

## Instalasi di XAMPP

1. Salin folder `arsip-tu` ke `C:\xampp\htdocs\`.
2. Jalankan Apache dan MySQL dari XAMPP Control Panel.
3. Buka phpMyAdmin, impor berurutan:
   - `database/schema.sql`
   - `database/seed.sql`
4. Salin `.env.example` menjadi `.env`, lalu sesuaikan `DB_USER` dan `DB_PASS`.
   - (opsional, hanya untuk uji coba) `database/seed_sample.sql`: 8 pegawai fiktif
5. Buat administrator pertama (di folder proyek):
   ```
   C:\xampp\php\php.exe bin\install.php
   ```
   Anda menentukan sendiri nama pengguna dan kata sandi. Tidak ada akun bawaan.
6. Buka `http://localhost/arsip-tu/public/` lalu masuk.

Catatan: di Windows, kata sandi yang diketik pada langkah 5 terlihat di layar.

## Deployment di hosting (cPanel)

- Letakkan isi `public/` di `public_html`, sedangkan `app/`, `routes/`, `storage/`, `database/`, `bin/`, dan `.env` sebaiknya di folder **di atas** `public_html`. Sesuaikan path `require` di `public/index.php` jika struktur diubah.
- Isi `STORAGE_PATH` di `.env` dengan folder di luar `public_html`.
- Aktifkan HTTPS. Cookie sesi otomatis memakai flag `Secure` saat HTTPS terdeteksi.
- Setelah instalasi, **hapus `bin/install.php`**.

## Keamanan yang sudah ada (Tahap 1-3)

- PDO prepared statements (tanpa emulasi).
- Kata sandi: `password_hash()` / `password_verify()`, minimal 10 karakter.
- Token CSRF pada semua permintaan POST (diperiksa di router).
- Sesi: ID diregenerasi saat login, cookie HttpOnly + SameSite, timeout tidak aktif (default 30 menit).
- Pembatasan login dengan jeda progresif; pesan error tidak membocorkan keberadaan akun.
- RBAC di sisi server (`app/config/permissions.php`, `Auth::require()`).
- Audit log (login, logout, akses ditolak, ganti sandi).
- Error PHP tidak ditampilkan; dicatat ke `storage/logs/`.

## Fitur Tahap 4-5

- Dashboard: 8 kartu statistik, grafik status pegawai dan dokumen per kategori (Chart.js), dokumen dan aktivitas terbaru. Semua angka dari database. Aktivitas: Admin melihat semua, Petugas TU hanya miliknya, Pimpinan tidak melihat.
- Data pegawai: daftar dengan pencarian nama/NIP, filter status/jabatan/unit/aktif, pengurutan (whitelist kolom), paginasi, tambah, ubah, detail 5 tab, arsipkan (hanya Admin, soft delete), cetak, dan ekspor CSV.
- Pimpinan tidak melihat NIK, tanggal lahir, telepon, email, alamat, dan catatan. Ekspor CSV menyesuaikan kewenangan dan menetralkan formula Excel.
- Kelengkapan arsip dihitung dari tabel `category_requirements`; kategori yang tidak diwajibkan ditampilkan "Tidak berlaku", bukan hilang.
- Riwayat perubahan mencatat nama kolom yang berubah, tanpa menyimpan nilainya.
- Foto pegawai belum ada (akan memakai layanan unggah aman di Tahap 6).

## Hak akses unduh dokumen

Sesuai keputusan: Admin, Petugas TU, dan Pimpinan boleh mengunduh semua dokumen.
Pimpinan tetap hanya-baca (tidak dapat mengubah atau menghapus).

## Struktur

Lihat rancangan awal. Perubahan dari template: ditambah `bin/` untuk installer CLI.

## Catatan

- Bootstrap dan Bootstrap Icons dimuat dari CDN (butuh internet). Untuk pemakaian offline, unduh dan simpan di `public/assets/`.
- Zona waktu aplikasi dan sesi MySQL: WITA (UTC+8).
