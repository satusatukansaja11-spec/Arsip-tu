-- ============================================================
-- Arsip Pegawai TU - MTsN 1 Alor
-- Skema database (MySQL 5.7+ / MariaDB 10.3+)
-- Engine InnoDB, charset utf8mb4
-- ============================================================

CREATE DATABASE IF NOT EXISTS arsip_tu
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE arsip_tu;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS application_settings;
DROP TABLE IF EXISTS password_reset_tokens;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS document_versions;
DROP TABLE IF EXISTS employee_documents;
DROP TABLE IF EXISTS category_requirements;
DROP TABLE IF EXISTS document_categories;
DROP TABLE IF EXISTS employees;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- roles
-- ------------------------------------------------------------
CREATE TABLE roles (
  id          TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode        VARCHAR(30)  NOT NULL,
  nama        VARCHAR(60)  NOT NULL,
  deskripsi   VARCHAR(255) NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_roles_kode (kode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- users
-- ------------------------------------------------------------
CREATE TABLE users (
  id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  role_id               TINYINT UNSIGNED NOT NULL,
  username              VARCHAR(50)  NOT NULL,
  password_hash         VARCHAR(255) NOT NULL,
  nama_lengkap          VARCHAR(120) NOT NULL,
  email                 VARCHAR(150) NULL,
  is_active             TINYINT(1)   NOT NULL DEFAULT 1,
  must_change_password  TINYINT(1)   NOT NULL DEFAULT 0,
  last_login_at         DATETIME NULL,
  password_changed_at   DATETIME NULL,
  created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username),
  KEY idx_users_role (role_id),
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- employees (pegawai). Tidak pernah dihapus permanen: soft delete.
-- ------------------------------------------------------------
CREATE TABLE employees (
  id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nip                 VARCHAR(25) NULL,
  nik                 VARCHAR(20) NULL,
  nama_lengkap        VARCHAR(120) NOT NULL,
  tempat_lahir        VARCHAR(80) NULL,
  tanggal_lahir       DATE NULL,
  jenis_kelamin       ENUM('L','P') NULL,
  pangkat_golongan    VARCHAR(60) NULL,
  jabatan             VARCHAR(100) NULL,
  unit_kerja          VARCHAR(100) NULL,
  status_kepegawaian  ENUM('PNS','PPPK','HONORER') NOT NULL,
  pendidikan_terakhir VARCHAR(50) NULL,
  telepon             VARCHAR(25) NULL,
  email               VARCHAR(150) NULL,
  alamat              TEXT NULL,
  tanggal_mulai       DATE NULL,
  status_aktif        TINYINT(1) NOT NULL DEFAULT 1,
  foto_path           VARCHAR(255) NULL,
  catatan             TEXT NULL,
  created_by          INT UNSIGNED NULL,
  created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at          DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_employees_nip (nip),
  KEY idx_employees_nama (nama_lengkap),
  KEY idx_employees_status (status_kepegawaian, status_aktif),
  KEY idx_employees_jabatan (jabatan),
  KEY idx_employees_unit (unit_kerja),
  KEY idx_employees_deleted (deleted_at),
  CONSTRAINT fk_employees_creator FOREIGN KEY (created_by) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- document_categories
-- ------------------------------------------------------------
CREATE TABLE document_categories (
  id          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama        VARCHAR(100) NOT NULL,
  urutan      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_nama (nama)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Aturan: kategori mana yang WAJIB untuk status kepegawaian tertentu.
-- Kategori yang tidak ada di tabel ini untuk suatu status dianggap
-- "tidak berlaku" (bukan "hilang").
CREATE TABLE category_requirements (
  category_id         SMALLINT UNSIGNED NOT NULL,
  status_kepegawaian  ENUM('PNS','PPPK','HONORER') NOT NULL,
  PRIMARY KEY (category_id, status_kepegawaian),
  CONSTRAINT fk_req_category FOREIGN KEY (category_id) REFERENCES document_categories (id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- employee_documents
-- ------------------------------------------------------------
CREATE TABLE employee_documents (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  employee_id      INT UNSIGNED NOT NULL,
  category_id      SMALLINT UNSIGNED NOT NULL,
  nomor_dokumen    VARCHAR(100) NULL,
  tanggal_dokumen  DATE NULL,
  tahun            SMALLINT UNSIGNED NULL,
  keterangan       VARCHAR(255) NULL,
  file_path        VARCHAR(255) NOT NULL COMMENT 'path relatif terhadap storage/documents',
  nama_asli        VARCHAR(255) NOT NULL,
  mime_type        VARCHAR(100) NOT NULL,
  ukuran_byte      INT UNSIGNED NOT NULL,
  hash_sha256      CHAR(64) NOT NULL,
  uploaded_by      INT UNSIGNED NULL,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at       DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_docs_employee (employee_id),
  KEY idx_docs_category (category_id),
  KEY idx_docs_nomor (nomor_dokumen),
  KEY idx_docs_tahun (tahun),
  KEY idx_docs_created (created_at),
  CONSTRAINT fk_docs_employee FOREIGN KEY (employee_id) REFERENCES employees (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_docs_category FOREIGN KEY (category_id) REFERENCES document_categories (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_docs_uploader FOREIGN KEY (uploaded_by) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- document_versions: file lama saat dokumen diganti
-- ------------------------------------------------------------
CREATE TABLE document_versions (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  document_id  INT UNSIGNED NOT NULL,
  versi        SMALLINT UNSIGNED NOT NULL,
  file_path    VARCHAR(255) NOT NULL,
  nama_asli    VARCHAR(255) NOT NULL,
  mime_type    VARCHAR(100) NOT NULL,
  ukuran_byte  INT UNSIGNED NOT NULL,
  hash_sha256  CHAR(64) NOT NULL,
  replaced_by  INT UNSIGNED NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_version (document_id, versi),
  CONSTRAINT fk_versions_doc FOREIGN KEY (document_id) REFERENCES employee_documents (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_versions_user FOREIGN KEY (replaced_by) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- activity_logs (hanya INSERT dari aplikasi; tanpa fitur ubah/hapus)
-- ------------------------------------------------------------
CREATE TABLE activity_logs (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id      INT UNSIGNED NULL,
  aksi         VARCHAR(60) NOT NULL,
  objek_tipe   VARCHAR(40) NULL,
  objek_id     INT UNSIGNED NULL,
  detail       VARCHAR(255) NULL,
  ip_address   VARCHAR(45) NULL,
  user_agent   VARCHAR(255) NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_logs_user (user_id),
  KEY idx_logs_aksi (aksi),
  KEY idx_logs_objek (objek_tipe, objek_id),
  KEY idx_logs_created (created_at),
  CONSTRAINT fk_logs_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- login_attempts
-- ------------------------------------------------------------
CREATE TABLE login_attempts (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  username      VARCHAR(50) NOT NULL,
  ip_address    VARCHAR(45) NOT NULL,
  berhasil      TINYINT(1) NOT NULL DEFAULT 0,
  attempted_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_attempts_user_time (username, attempted_at),
  KEY idx_attempts_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- password_reset_tokens (disiapkan untuk reset token sekali pakai)
-- ------------------------------------------------------------
CREATE TABLE password_reset_tokens (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED NOT NULL,
  token_hash  CHAR(64) NOT NULL,
  expires_at  DATETIME NOT NULL,
  used_at     DATETIME NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_reset_token (token_hash),
  KEY idx_reset_user (user_id),
  CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- application_settings
-- ------------------------------------------------------------
CREATE TABLE application_settings (
  setting_key    VARCHAR(60) NOT NULL,
  setting_value  TEXT NULL,
  updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
