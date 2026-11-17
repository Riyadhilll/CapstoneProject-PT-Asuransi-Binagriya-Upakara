CREATE TABLE divisi (
  id     SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kode   VARCHAR(10)  NOT NULL UNIQUE,
  nama   VARCHAR(100) NOT NULL UNIQUE,
  aktif  BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE kanal (
  id     SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kode   VARCHAR(10)  NOT NULL UNIQUE,
  nama   VARCHAR(100) NOT NULL UNIQUE,
  aktif  BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE kategori (
  id                SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kode              VARCHAR(10)  NOT NULL UNIQUE,
  nama              VARCHAR(100) NOT NULL UNIQUE,
  divisi_usulan_id  SMALLINT UNSIGNED NULL,
  aktif             BOOLEAN NOT NULL DEFAULT TRUE,
  CONSTRAINT fk_kategori_divisi FOREIGN KEY (divisi_usulan_id) REFERENCES divisi(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE status (
  kode    VARCHAR(10) PRIMARY KEY,
  nama    VARCHAR(50) NOT NULL UNIQUE,
  urutan  TINYINT UNSIGNED NOT NULL UNIQUE,
  CONSTRAINT ck_status_kode CHECK (kode IN ('MENUNGGU', 'PROSES', 'SELESAI'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hari_libur (
  tanggal  DATE PRIMARY KEY,
  nama     VARCHAR(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE kalender (
  tanggal     DATE PRIMARY KEY,
  hari_kerja  BOOLEAN NOT NULL,
  KEY ix_kalender_kerja (hari_kerja, tanggal)
) ENGINE=InnoDB;

CREATE TABLE pengguna (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama            VARCHAR(150) NOT NULL,
  username        VARCHAR(50)  NOT NULL UNIQUE,
  email           VARCHAR(150) NOT NULL UNIQUE,
  password_hash   VARCHAR(255) NOT NULL,
  remember_token  VARCHAR(100) NULL,
  peran           ENUM('petugas', 'pic', 'pimpinan', 'admin') NOT NULL,
  divisi_id       SMALLINT UNSIGNED NULL,
  aktif           BOOLEAN NOT NULL DEFAULT TRUE,
  dibuat_pada     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  dibuat_oleh     BIGINT UNSIGNED NULL,
  CONSTRAINT fk_pengguna_divisi FOREIGN KEY (divisi_id) REFERENCES divisi(id),
  CONSTRAINT fk_pengguna_pembuat FOREIGN KEY (dibuat_oleh) REFERENCES pengguna(id),
  CONSTRAINT pic_wajib_divisi CHECK (peran <> 'pic' OR divisi_id IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE konsumen (
  id       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama     VARCHAR(150) NOT NULL,
  email    VARCHAR(150) NOT NULL,
  telepon  VARCHAR(20)  NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE nomor_counter (
  periode   CHAR(6) PRIMARY KEY,
  terakhir  INT UNSIGNED NOT NULL
) ENGINE=InnoDB;

CREATE TABLE layanan (
  id                     BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nomor                  VARCHAR(20) NOT NULL DEFAULT '',
  konsumen_id            BIGINT UNSIGNED NOT NULL,
  kanal_id               SMALLINT UNSIGNED NOT NULL,
  kategori_id            SMALLINT UNSIGNED NOT NULL,
  jenis                  ENUM('pertanyaan', 'pengaduan') NOT NULL,
  bentuk                 ENUM('lisan', 'tertulis') NOT NULL,
  uraian                 TEXT NOT NULL,
  diterima_pada          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  tgl_dokumen_lengkap    DATE NULL,
  mulai_hitung           DATE NULL,
  batas_hk               TINYINT UNSIGNED NULL,
  tenggat                DATE NULL,
  selesai_saat_diterima  BOOLEAN NOT NULL DEFAULT FALSE,
  status_kode            VARCHAR(10) NOT NULL DEFAULT 'MENUNGGU',
  divisi_tujuan_id       SMALLINT UNSIGNED NULL,
  pic_id                 BIGINT UNSIGNED NULL,
  dicatat_oleh           BIGINT UNSIGNED NOT NULL,
  hasil                  TEXT NULL,
  metode_penyampaian     ENUM('email', 'telepon', 'whatsapp', 'tatap_muka') NULL,
  selesai_pada           DATETIME NULL,
  dibuat_pada            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  diubah_pada            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  UNIQUE KEY uq_layanan_nomor (nomor),
  KEY ix_layanan_pic (pic_id, status_kode),
  KEY ix_layanan_status_tenggat (status_kode, tenggat),
  KEY ix_layanan_diterima (diterima_pada),
  CONSTRAINT fk_layanan_konsumen FOREIGN KEY (konsumen_id) REFERENCES konsumen(id),
  CONSTRAINT fk_layanan_kanal    FOREIGN KEY (kanal_id) REFERENCES kanal(id),
  CONSTRAINT fk_layanan_kategori FOREIGN KEY (kategori_id) REFERENCES kategori(id),
  CONSTRAINT fk_layanan_status   FOREIGN KEY (status_kode) REFERENCES status(kode),
  CONSTRAINT fk_layanan_divisi   FOREIGN KEY (divisi_tujuan_id) REFERENCES divisi(id),
  CONSTRAINT fk_layanan_pic      FOREIGN KEY (pic_id) REFERENCES pengguna(id),
  CONSTRAINT fk_layanan_pencatat FOREIGN KEY (dicatat_oleh) REFERENCES pengguna(id),

  CONSTRAINT dokumen_hanya_tertulis
    CHECK (bentuk = 'tertulis' OR tgl_dokumen_lengkap IS NULL),
  CONSTRAINT langsung_tanpa_pic
    CHECK (selesai_saat_diterima = FALSE
           OR (status_kode = 'SELESAI' AND pic_id IS NULL AND divisi_tujuan_id IS NULL)),
  CONSTRAINT diteruskan_wajib_pic
    CHECK (selesai_saat_diterima = TRUE OR (divisi_tujuan_id IS NOT NULL AND pic_id IS NOT NULL)),
  CONSTRAINT selesai_lengkap
    CHECK (status_kode <> 'SELESAI'
           OR (hasil IS NOT NULL AND metode_penyampaian IS NOT NULL AND selesai_pada IS NOT NULL)),
  CONSTRAINT belum_selesai_tanpa_waktu
    CHECK (status_kode = 'SELESAI' OR selesai_pada IS NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE perpanjangan (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  layanan_id    BIGINT UNSIGNED NOT NULL,
  tambahan_hk   TINYINT UNSIGNED NOT NULL,
  alasan        TEXT NOT NULL,
  tenggat_lama  DATE NULL,
  tenggat_baru  DATE NULL,
  oleh          BIGINT UNSIGNED NOT NULL,
  pada          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_perpanjangan_layanan FOREIGN KEY (layanan_id) REFERENCES layanan(id),
  CONSTRAINT fk_perpanjangan_oleh    FOREIGN KEY (oleh) REFERENCES pengguna(id),
  CONSTRAINT ck_perpanjangan_hk      CHECK (tambahan_hk > 0),
  CONSTRAINT ck_perpanjangan_alasan  CHECK (CHAR_LENGTH(TRIM(alasan)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE riwayat_layanan (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  layanan_id   BIGINT UNSIGNED NOT NULL,
  jenis        ENUM('dibuat', 'status', 'penugasan', 'perpanjangan') NOT NULL,
  status_lama  VARCHAR(10) NULL,
  status_baru  VARCHAR(10) NULL,
  divisi_lama  SMALLINT UNSIGNED NULL,
  divisi_baru  SMALLINT UNSIGNED NULL,
  pic_lama     BIGINT UNSIGNED NULL,
  pic_baru     BIGINT UNSIGNED NULL,
  catatan      TEXT NULL,
  oleh         BIGINT UNSIGNED NOT NULL,
  pada         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY ix_riwayat_layanan (layanan_id, pada),
  CONSTRAINT fk_riwayat_layanan FOREIGN KEY (layanan_id) REFERENCES layanan(id),
  CONSTRAINT fk_riwayat_oleh    FOREIGN KEY (oleh) REFERENCES pengguna(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mart_dim_divisi   (divisi_id SMALLINT UNSIGNED PRIMARY KEY, kode VARCHAR(10), nama VARCHAR(100), aktif BOOLEAN) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE mart_dim_kanal    (kanal_id SMALLINT UNSIGNED PRIMARY KEY, kode VARCHAR(10), nama VARCHAR(100), aktif BOOLEAN) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE mart_dim_kategori (kategori_id SMALLINT UNSIGNED PRIMARY KEY, kode VARCHAR(10), nama VARCHAR(100), aktif BOOLEAN) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE mart_dim_status   (status_kode VARCHAR(10) PRIMARY KEY, nama VARCHAR(50), urutan TINYINT UNSIGNED) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE mart_dim_pic      (pic_id BIGINT UNSIGNED PRIMARY KEY, nama VARCHAR(150), divisi_id SMALLINT UNSIGNED, aktif BOOLEAN) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mart_fact_layanan (
  layanan_id             BIGINT UNSIGNED PRIMARY KEY,
  nomor                  VARCHAR(20) NOT NULL,
  tgl_diterima           DATE NOT NULL,
  tgl_mulai              DATE NULL,
  tgl_selesai            DATE NULL,
  tenggat                DATE NULL,
  kanal_id               SMALLINT UNSIGNED NOT NULL,
  kategori_id            SMALLINT UNSIGNED NOT NULL,
  divisi_id              SMALLINT UNSIGNED NULL,
  pic_id                 BIGINT UNSIGNED NULL,
  status_kode            VARCHAR(10) NOT NULL,
  jenis                  VARCHAR(20) NOT NULL,
  bentuk                 VARCHAR(20) NOT NULL,
  selesai_saat_diterima  BOOLEAN NOT NULL,
  metode_penyampaian     VARCHAR(20) NULL,
  diterima_pada          DATETIME NOT NULL,
  selesai_pada           DATETIME NULL,
  batas_hk               TINYINT UNSIGNED NULL,
  jumlah_perpanjangan    SMALLINT UNSIGNED NOT NULL,
  total_perpanjangan_hk  SMALLINT UNSIGNED NOT NULL,
  jumlah_pengalihan      SMALLINT UNSIGNED NOT NULL,
  lama_hk                INT NULL,
  tepat_waktu            BOOLEAN NULL,
  etl_pada               DATETIME NOT NULL,
  KEY ix_fact_diterima (tgl_diterima),
  KEY ix_fact_selesai (tgl_selesai),
  KEY ix_fact_status_tenggat (status_kode, tenggat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mart_fact_status_tahap (
  layanan_id   BIGINT UNSIGNED NOT NULL,
  urutan       SMALLINT UNSIGNED NOT NULL,
  status_kode  VARCHAR(10) NOT NULL,
  masuk_pada   DATETIME NOT NULL,
  keluar_pada  DATETIME NULL,
  durasi_jam   DECIMAL(10,2) NULL,
  PRIMARY KEY (layanan_id, urutan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mart_fact_perpanjangan (
  perpanjangan_id  BIGINT UNSIGNED PRIMARY KEY,
  layanan_id       BIGINT UNSIGNED NOT NULL,
  tanggal          DATE NOT NULL,
  tambahan_hk      TINYINT UNSIGNED NOT NULL,
  alasan           TEXT NOT NULL,
  KEY ix_fp_layanan (layanan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mart_etl_log (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  mulai        DATETIME(3) NOT NULL,
  selesai      DATETIME(3) NULL,
  mode         ENUM('inkremental', 'penuh') NOT NULL,
  layanan_id   BIGINT UNSIGNED NULL,
  baris        INT NULL,
  durasi_ms    INT NULL,
  jumlah_app   INT NULL,
  jumlah_mart  INT NULL,
  hasil        ENUM('berhasil', 'gagal') NOT NULL,
  pesan        TEXT NULL,
  KEY ix_etl_mulai (mulai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER $$

DROP FUNCTION IF EXISTS tambah_hari_kerja $$
CREATE FUNCTION tambah_hari_kerja(mulai DATE, n INT) RETURNS DATE
READS SQL DATA
BEGIN
  DECLARE hasil DATE DEFAULT NULL;
  DECLARE k INT DEFAULT GREATEST(n, 1) - 1;
  SELECT tanggal INTO hasil FROM kalender
   WHERE tanggal > mulai AND hari_kerja = TRUE
   ORDER BY tanggal LIMIT k, 1;
  IF hasil IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Kalender kerja belum mencakup tanggal tenggat';
  END IF;
  RETURN hasil;
END $$

DROP FUNCTION IF EXISTS hitung_hari_kerja $$
CREATE FUNCTION hitung_hari_kerja(dari DATE, sampai DATE) RETURNS INT
READS SQL DATA
BEGIN
  IF sampai <= dari THEN RETURN 0; END IF;
  RETURN (SELECT COUNT(*) FROM kalender
           WHERE tanggal > dari AND tanggal <= sampai AND hari_kerja = TRUE);
END $$

DROP PROCEDURE IF EXISTS isi_kalender $$
CREATE PROCEDURE isi_kalender(IN dari DATE, IN sampai DATE)
BEGIN
  INSERT IGNORE INTO kalender (tanggal, hari_kerja)
  SELECT d, (WEEKDAY(d) < 5 AND NOT EXISTS (SELECT 1 FROM hari_libur h WHERE h.tanggal = d))
  FROM (
    SELECT DATE_ADD(dari, INTERVAL (a.n + b.n * 10 + c.n * 100 + e.n * 1000) DAY) AS d
    FROM (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
          UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) a
    CROSS JOIN (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
          UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) b
    CROSS JOIN (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
          UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) c
    CROSS JOIN (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
          UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) e
  ) x
  WHERE d <= sampai;
END $$

DROP PROCEDURE IF EXISTS validasi_pic $$
CREATE PROCEDURE validasi_pic(IN p_pic BIGINT UNSIGNED, IN p_divisi SMALLINT UNSIGNED)
BEGIN
  DECLARE v_peran VARCHAR(20);
  DECLARE v_aktif BOOLEAN;
  DECLARE v_divisi SMALLINT UNSIGNED;
  IF p_pic IS NOT NULL THEN
    SELECT peran, aktif, divisi_id INTO v_peran, v_aktif, v_divisi FROM pengguna WHERE id = p_pic;
    IF v_peran <> 'pic' OR NOT v_aktif THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'PIC harus akun aktif berperan pic';
    END IF;
    IF NOT (v_divisi <=> p_divisi) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'PIC harus berasal dari divisi tujuan';
    END IF;
  END IF;
END $$

DROP PROCEDURE IF EXISTS hitung_ulang_tenggat $$
CREATE PROCEDURE hitung_ulang_tenggat()
BEGIN
  UPDATE layanan SET diubah_pada = NOW() WHERE status_kode <> 'SELESAI';
END $$

CREATE TRIGGER hari_libur_ai AFTER INSERT ON hari_libur FOR EACH ROW
BEGIN
  UPDATE kalender SET hari_kerja = FALSE WHERE tanggal = NEW.tanggal;
END $$

CREATE TRIGGER hari_libur_au AFTER UPDATE ON hari_libur FOR EACH ROW
BEGIN
  UPDATE kalender SET hari_kerja = (WEEKDAY(tanggal) < 5) WHERE tanggal = OLD.tanggal;
  UPDATE kalender SET hari_kerja = FALSE WHERE tanggal = NEW.tanggal;
END $$

CREATE TRIGGER hari_libur_ad AFTER DELETE ON hari_libur FOR EACH ROW
BEGIN
  UPDATE kalender SET hari_kerja = (WEEKDAY(tanggal) < 5) WHERE tanggal = OLD.tanggal;
END $$

CREATE TRIGGER layanan_bi BEFORE INSERT ON layanan FOR EACH ROW
BEGIN
  DECLARE v_periode CHAR(6);
  DECLARE v_urut INT;

  SET v_periode = DATE_FORMAT(NEW.diterima_pada, '%Y%m');
  INSERT INTO nomor_counter (periode, terakhir) VALUES (v_periode, 1)
    ON DUPLICATE KEY UPDATE terakhir = terakhir + 1;
  SELECT terakhir INTO v_urut FROM nomor_counter WHERE periode = v_periode;
  SET NEW.nomor = CONCAT('LK-', v_periode, '-', LPAD(v_urut, 4, '0'));

  CALL validasi_pic(NEW.pic_id, NEW.divisi_tujuan_id);

  IF NEW.selesai_saat_diterima THEN
    SET NEW.status_kode = 'SELESAI';
    SET NEW.selesai_pada = COALESCE(NEW.selesai_pada, NEW.diterima_pada);
  ELSEIF NEW.status_kode = 'SELESAI' AND NEW.selesai_pada IS NULL THEN
    SET NEW.selesai_pada = NOW();
  END IF;

  SET NEW.batas_hk = IF(NEW.bentuk = 'lisan', 5, 10);
  SET NEW.mulai_hitung = IF(NEW.bentuk = 'lisan', DATE(NEW.diterima_pada), NEW.tgl_dokumen_lengkap);
  SET NEW.tenggat = IF(NEW.mulai_hitung IS NULL, NULL, tambah_hari_kerja(NEW.mulai_hitung, NEW.batas_hk));
  SET NEW.dibuat_pada = NOW();
  SET NEW.diubah_pada = NOW();
END $$

CREATE TRIGGER layanan_bu BEFORE UPDATE ON layanan FOR EACH ROW
BEGIN
  DECLARE v_ext INT DEFAULT 0;

  IF NEW.nomor <> OLD.nomor THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nomor layanan tidak boleh diubah';
  END IF;
  IF OLD.status_kode = 'SELESAI' AND NEW.status_kode <> 'SELESAI' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Layanan yang sudah selesai tidak dapat dibuka kembali';
  END IF;

  CALL validasi_pic(NEW.pic_id, NEW.divisi_tujuan_id);

  IF NEW.status_kode = 'SELESAI' AND NEW.selesai_pada IS NULL THEN
    SET NEW.selesai_pada = NOW();
  END IF;

  SET NEW.batas_hk = IF(NEW.bentuk = 'lisan', 5, 10);
  SET NEW.mulai_hitung = IF(NEW.bentuk = 'lisan', DATE(NEW.diterima_pada), NEW.tgl_dokumen_lengkap);
  IF NEW.mulai_hitung IS NULL THEN
    SET NEW.tenggat = NULL;
  ELSE
    SELECT COALESCE(SUM(tambahan_hk), 0) INTO v_ext FROM perpanjangan WHERE layanan_id = NEW.id;
    SET NEW.tenggat = tambah_hari_kerja(NEW.mulai_hitung, NEW.batas_hk + v_ext);
  END IF;
  SET NEW.diubah_pada = NOW();
END $$

CREATE TRIGGER layanan_ai AFTER INSERT ON layanan FOR EACH ROW
BEGIN
  INSERT INTO riwayat_layanan (layanan_id, jenis, status_baru, divisi_baru, pic_baru, catatan, oleh, pada)
  VALUES (NEW.id, 'dibuat', NEW.status_kode, NEW.divisi_tujuan_id, NEW.pic_id,
          NULLIF(@app_catatan, ''), COALESCE(@app_user_id, NEW.dicatat_oleh), NEW.diterima_pada);
END $$

CREATE TRIGGER layanan_au AFTER UPDATE ON layanan FOR EACH ROW
BEGIN
  IF NOT (NEW.status_kode <=> OLD.status_kode)
     OR NOT (NEW.pic_id <=> OLD.pic_id)
     OR NOT (NEW.divisi_tujuan_id <=> OLD.divisi_tujuan_id) THEN
    IF @app_user_id IS NULL THEN
      SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = '@app_user_id belum di-set; perubahan status/penugasan wajib tercatat penggunanya';
    END IF;
  END IF;
  IF NOT (NEW.pic_id <=> OLD.pic_id) OR NOT (NEW.divisi_tujuan_id <=> OLD.divisi_tujuan_id) THEN
    INSERT INTO riwayat_layanan (layanan_id, jenis, divisi_lama, divisi_baru, pic_lama, pic_baru, catatan, oleh)
    VALUES (NEW.id, 'penugasan', OLD.divisi_tujuan_id, NEW.divisi_tujuan_id, OLD.pic_id, NEW.pic_id,
            NULLIF(@app_catatan, ''), @app_user_id);
  END IF;
  IF NOT (NEW.status_kode <=> OLD.status_kode) THEN
    INSERT INTO riwayat_layanan (layanan_id, jenis, status_lama, status_baru, catatan, oleh)
    VALUES (NEW.id, 'status', OLD.status_kode, NEW.status_kode, NULLIF(@app_catatan, ''), @app_user_id);
  END IF;
END $$

CREATE TRIGGER perpanjangan_bi BEFORE INSERT ON perpanjangan FOR EACH ROW
BEGIN
  DECLARE v_status VARCHAR(10);
  DECLARE v_tenggat DATE;
  SELECT status_kode, tenggat INTO v_status, v_tenggat FROM layanan WHERE id = NEW.layanan_id;
  IF v_status = 'SELESAI' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Layanan sudah selesai, tidak dapat diperpanjang';
  END IF;
  IF v_tenggat IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tenggat belum berjalan (dokumen belum lengkap)';
  END IF;
  SET NEW.tenggat_lama = v_tenggat;
  SET NEW.tenggat_baru = tambah_hari_kerja(v_tenggat, NEW.tambahan_hk);
  SET NEW.pada = NOW();
END $$

CREATE TRIGGER perpanjangan_ai AFTER INSERT ON perpanjangan FOR EACH ROW
BEGIN
  UPDATE layanan SET diubah_pada = NOW() WHERE id = NEW.layanan_id;
  INSERT INTO riwayat_layanan (layanan_id, jenis, catatan, oleh)
  VALUES (NEW.layanan_id, 'perpanjangan',
          CONCAT('+', NEW.tambahan_hk, ' HK (', NEW.tenggat_lama, ' -> ', NEW.tenggat_baru, '): ', NEW.alasan),
          NEW.oleh);
END $$

DROP PROCEDURE IF EXISTS mart_jalankan_etl $$
CREATE PROCEDURE mart_jalankan_etl(IN p_layanan_id BIGINT UNSIGNED)
BEGIN
  DECLARE v_mulai DATETIME(3) DEFAULT NOW(3);
  DECLARE v_baris INT DEFAULT 0;

  DELETE FROM mart_dim_divisi;   INSERT INTO mart_dim_divisi   SELECT id, kode, nama, aktif FROM divisi;
  DELETE FROM mart_dim_kanal;    INSERT INTO mart_dim_kanal    SELECT id, kode, nama, aktif FROM kanal;
  DELETE FROM mart_dim_kategori; INSERT INTO mart_dim_kategori SELECT id, kode, nama, aktif FROM kategori;
  DELETE FROM mart_dim_status;   INSERT INTO mart_dim_status   SELECT kode, nama, urutan FROM status;
  DELETE FROM mart_dim_pic;      INSERT INTO mart_dim_pic      SELECT id, nama, divisi_id, aktif FROM pengguna WHERE peran = 'pic';

  DELETE FROM mart_fact_layanan      WHERE p_layanan_id IS NULL OR layanan_id = p_layanan_id;
  DELETE FROM mart_fact_status_tahap WHERE p_layanan_id IS NULL OR layanan_id = p_layanan_id;
  DELETE FROM mart_fact_perpanjangan WHERE p_layanan_id IS NULL OR layanan_id = p_layanan_id;

  INSERT INTO mart_fact_layanan (
    layanan_id, nomor, tgl_diterima, tgl_mulai, tgl_selesai, tenggat,
    kanal_id, kategori_id, divisi_id, pic_id, status_kode, jenis, bentuk,
    selesai_saat_diterima, metode_penyampaian, diterima_pada, selesai_pada, batas_hk,
    jumlah_perpanjangan, total_perpanjangan_hk, jumlah_pengalihan, lama_hk, tepat_waktu, etl_pada)
  SELECT
    l.id, l.nomor, DATE(l.diterima_pada), l.mulai_hitung, DATE(l.selesai_pada), l.tenggat,
    l.kanal_id, l.kategori_id, l.divisi_tujuan_id, l.pic_id, l.status_kode, l.jenis, l.bentuk,
    l.selesai_saat_diterima, l.metode_penyampaian, l.diterima_pada, l.selesai_pada, l.batas_hk,
    COALESCE(p.jml, 0), COALESCE(p.hk, 0), COALESCE(r.alih, 0),
    CASE WHEN l.selesai_pada IS NULL THEN NULL
         WHEN l.selesai_saat_diterima THEN 0
         ELSE hitung_hari_kerja(l.mulai_hitung, DATE(l.selesai_pada)) END,
    CASE WHEN l.selesai_pada IS NULL THEN NULL
         WHEN l.selesai_saat_diterima THEN TRUE
         ELSE DATE(l.selesai_pada) <= l.tenggat END,
    NOW()
  FROM layanan l
  LEFT JOIN (SELECT layanan_id, COUNT(*) jml, SUM(tambahan_hk) hk
             FROM perpanjangan GROUP BY layanan_id) p ON p.layanan_id = l.id
  LEFT JOIN (SELECT layanan_id, COUNT(*) alih
             FROM riwayat_layanan WHERE jenis = 'penugasan' AND pic_lama IS NOT NULL
             GROUP BY layanan_id) r ON r.layanan_id = l.id
  WHERE p_layanan_id IS NULL OR l.id = p_layanan_id;
  SET v_baris = ROW_COUNT();

  INSERT INTO mart_fact_status_tahap (layanan_id, urutan, status_kode, masuk_pada, keluar_pada, durasi_jam)
  SELECT layanan_id, urutan, status_baru, pada, keluar,
         ROUND(TIMESTAMPDIFF(SECOND, pada, keluar) / 3600, 2)
  FROM (
    SELECT layanan_id, status_baru, pada,
           ROW_NUMBER() OVER (PARTITION BY layanan_id ORDER BY pada, id) AS urutan,
           LEAD(pada)   OVER (PARTITION BY layanan_id ORDER BY pada, id) AS keluar
    FROM riwayat_layanan
    WHERE jenis IN ('dibuat', 'status')
      AND (p_layanan_id IS NULL OR layanan_id = p_layanan_id)
  ) s;

  INSERT INTO mart_fact_perpanjangan (perpanjangan_id, layanan_id, tanggal, tambahan_hk, alasan)
  SELECT id, layanan_id, DATE(pada), tambahan_hk, alasan
  FROM perpanjangan WHERE p_layanan_id IS NULL OR layanan_id = p_layanan_id;

  INSERT INTO mart_etl_log (mulai, selesai, mode, layanan_id, baris, durasi_ms, jumlah_app, jumlah_mart, hasil)
  VALUES (v_mulai, NOW(3), IF(p_layanan_id IS NULL, 'penuh', 'inkremental'), p_layanan_id, v_baris,
          TIMESTAMPDIFF(MICROSECOND, v_mulai, NOW(3)) DIV 1000,
          (SELECT COUNT(*) FROM layanan), (SELECT COUNT(*) FROM mart_fact_layanan), 'berhasil');
END $$

DELIMITER ;

CREATE OR REPLACE VIEW mart_dim_tanggal AS
SELECT tanggal,
       YEAR(tanggal)  AS tahun,
       MONTH(tanggal) AS bulan,
       WEEK(tanggal, 3) AS minggu_iso,
       DATE_SUB(tanggal, INTERVAL WEEKDAY(tanggal) DAY) AS awal_minggu,
       hari_kerja
FROM kalender;

CREATE OR REPLACE VIEW mart_v_tenggat_aktif AS
SELECT f.*,
       CASE WHEN f.tenggat IS NULL THEN NULL
            WHEN CURDATE() <= f.tenggat THEN hitung_hari_kerja(CURDATE(), f.tenggat)
            ELSE -hitung_hari_kerja(f.tenggat, CURDATE()) END AS sisa_hk,
       CASE WHEN f.tenggat IS NULL THEN 'menunggu_dokumen'
            WHEN CURDATE() > f.tenggat THEN 'melewati'
            WHEN hitung_hari_kerja(CURDATE(), f.tenggat) <= 2 THEN 'mendekati'
            ELSE 'dalam' END AS penanda
FROM mart_fact_layanan f
WHERE f.status_kode <> 'SELESAI';

CALL isi_kalender('2020-01-01', '2035-12-31');
