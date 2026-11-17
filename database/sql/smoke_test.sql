INSERT INTO pengguna (nama, username, email, password_hash, peran, divisi_id) VALUES
  ('Admin TI',       'admin',    'admin@contoh.id',    'x', 'admin',    5),
  ('Petugas Satu',   'petugas1', 'petugas1@contoh.id', 'x', 'petugas',  2),
  ('PIC Klaim Satu', 'pic.klm1', 'pic.klm1@contoh.id', 'x', 'pic',      1),
  ('PIC Klaim Dua',  'pic.klm2', 'pic.klm2@contoh.id', 'x', 'pic',      1),
  ('PIC Pemasaran',  'pic.pms1', 'pic.pms1@contoh.id', 'x', 'pic',      2),
  ('Pimpinan',       'pimpinan', 'pimpinan@contoh.id', 'x', 'pimpinan', NULL);

INSERT INTO hari_libur VALUES ('2026-11-20', 'Libur uji');

SELECT tambah_hari_kerja('2026-11-16', 5) AS tenggat_lisan, hitung_hari_kerja('2026-11-16', '2026-11-24') AS hk;

SET @app_user_id = 2, @app_catatan = NULL;
START TRANSACTION;
INSERT INTO konsumen (nama, email, telepon) VALUES ('Dummy', 'd@contoh.id', '0800');
INSERT INTO layanan (konsumen_id, kanal_id, kategori_id, jenis, bentuk, uraian, diterima_pada, divisi_tujuan_id, pic_id, dicatat_oleh)
VALUES (1, 1, 1, 'pengaduan', 'lisan', 'uji', '2026-11-16 09:00:00', 1, 3, 2);
INSERT INTO konsumen (nama, email, telepon) VALUES ('Dummy2', 'd2@contoh.id', '0801');
INSERT INTO layanan (konsumen_id, kanal_id, kategori_id, jenis, bentuk, uraian, diterima_pada, selesai_saat_diterima, hasil, metode_penyampaian, dicatat_oleh)
VALUES (2, 2, 4, 'pertanyaan', 'lisan', 'uji2', '2026-11-16 10:00:00', TRUE, 'jawab', 'telepon', 2);
INSERT INTO konsumen (nama, email, telepon) VALUES ('Dummy3', 'd3@contoh.id', '0802');
INSERT INTO layanan (konsumen_id, kanal_id, kategori_id, jenis, bentuk, uraian, diterima_pada, divisi_tujuan_id, pic_id, dicatat_oleh)
VALUES (3, 2, 2, 'pengaduan', 'tertulis', 'uji3', '2026-11-16 11:00:00', 2, 5, 2);
COMMIT;
SELECT id, nomor, mulai_hitung, batas_hk, tenggat, status_kode, selesai_pada FROM layanan ORDER BY id;

SET @app_user_id = 3, @app_catatan = 'mulai ditangani';
UPDATE layanan SET status_kode = 'PROSES' WHERE id = 1;
SET @app_catatan = NULL;
UPDATE layanan SET pic_id = 4 WHERE id = 1;
INSERT INTO perpanjangan (layanan_id, tambahan_hk, alasan, oleh) VALUES (1, 3, 'menunggu dokumen klaim', 4);
SET @app_user_id = 4;
UPDATE layanan SET status_kode = 'SELESAI', hasil = 'selesai', metode_penyampaian = 'email', selesai_pada = '2026-11-26 15:00:00' WHERE id = 1;
UPDATE layanan SET tgl_dokumen_lengkap = '2026-11-18' WHERE id = 3;
SET @app_user_id = NULL, @app_catatan = NULL;
SELECT id, nomor, tenggat, status_kode FROM layanan ORDER BY id;
SELECT layanan_id, jenis, status_lama, status_baru, pic_lama, pic_baru, catatan, oleh FROM riwayat_layanan ORDER BY id;

START TRANSACTION; CALL mart_jalankan_etl(NULL); COMMIT;
START TRANSACTION; CALL mart_jalankan_etl(1); COMMIT;
SELECT id, mode, layanan_id, baris, jumlah_app, jumlah_mart, hasil FROM mart_etl_log;
SELECT layanan_id, nomor, status_kode, lama_hk, tepat_waktu, jumlah_pengalihan, jumlah_perpanjangan, total_perpanjangan_hk FROM mart_fact_layanan ORDER BY 1;
SELECT * FROM mart_fact_status_tahap ORDER BY 1, 2;

DELIMITER $$
DROP PROCEDURE IF EXISTS uji_harus_gagal $$
CREATE PROCEDURE uji_harus_gagal(IN p_nama VARCHAR(100), IN p_sql TEXT)
BEGIN
  DECLARE v_pesan TEXT;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    GET DIAGNOSTICS CONDITION 1 v_pesan = MESSAGE_TEXT;
    SELECT CONCAT('OK: ', p_nama, ' -> ', v_pesan) AS hasil;
  END;
  SET @uji_sql = p_sql;
  PREPARE s FROM @uji_sql;
  EXECUTE s;
  DEALLOCATE PREPARE s;
  SELECT CONCAT('GAGAL: ', p_nama, ' diterima') AS hasil;
END $$
DELIMITER ;

SET @app_user_id = NULL;
CALL uji_harus_gagal('ubah status tanpa @app_user_id', "UPDATE layanan SET status_kode = 'PROSES' WHERE id = 3");
SET @app_user_id = 2;
CALL uji_harus_gagal('PIC beda divisi', "UPDATE layanan SET pic_id = 5, divisi_tujuan_id = 1 WHERE id = 3");
CALL uji_harus_gagal('buka kembali layanan selesai', "UPDATE layanan SET status_kode = 'PROSES' WHERE id = 1");
CALL uji_harus_gagal('perpanjang layanan selesai', "INSERT INTO perpanjangan (layanan_id, tambahan_hk, alasan, oleh) VALUES (1, 2, 'x', 4)");
CALL uji_harus_gagal('diteruskan tanpa PIC', "INSERT INTO layanan (konsumen_id, kanal_id, kategori_id, jenis, bentuk, uraian, dicatat_oleh) VALUES (1, 1, 1, 'pertanyaan', 'lisan', 'x', 2)");
CALL uji_harus_gagal('langsung selesai dengan PIC', "INSERT INTO layanan (konsumen_id, kanal_id, kategori_id, jenis, bentuk, uraian, selesai_saat_diterima, hasil, metode_penyampaian, divisi_tujuan_id, pic_id, dicatat_oleh) VALUES (1, 1, 1, 'pertanyaan', 'lisan', 'x', TRUE, 'j', 'email', 1, 3, 2)");
CALL uji_harus_gagal('akun PIC tanpa divisi', "INSERT INTO pengguna (nama, username, email, password_hash, peran) VALUES ('x', 'x', 'x@x', 'h', 'pic')");
CALL uji_harus_gagal('ubah nomor layanan', "UPDATE layanan SET nomor = 'LK-1' WHERE id = 3");
SET @app_user_id = NULL;
DROP PROCEDURE uji_harus_gagal;

SELECT table_name, column_name FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name LIKE 'mart\_%' AND table_name <> 'mart_etl_log'
  AND column_name REGEXP 'email|telepon|konsumen|uraian|hasil';
