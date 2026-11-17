<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->jalankanSql(database_path('sql/schema.sql'));
    }

    public function down(): void
    {
        DB::unprepared('DROP VIEW IF EXISTS mart_v_tenggat_aktif, mart_dim_tanggal');
        foreach (['tambah_hari_kerja', 'hitung_hari_kerja'] as $f) {
            DB::unprepared("DROP FUNCTION IF EXISTS {$f}");
        }
        foreach (['isi_kalender', 'validasi_pic', 'hitung_ulang_tenggat', 'mart_jalankan_etl'] as $p) {
            DB::unprepared("DROP PROCEDURE IF EXISTS {$p}");
        }
        Schema::disableForeignKeyConstraints();
        foreach ([
            'mart_etl_log', 'mart_fact_perpanjangan', 'mart_fact_status_tahap', 'mart_fact_layanan',
            'mart_dim_pic', 'mart_dim_status', 'mart_dim_kategori', 'mart_dim_kanal', 'mart_dim_divisi',
            'riwayat_layanan', 'perpanjangan', 'layanan', 'nomor_counter', 'konsumen', 'pengguna',
            'kalender', 'hari_libur', 'status', 'kategori', 'kanal', 'divisi',
        ] as $tabel) {
            Schema::dropIfExists($tabel);
        }
        Schema::enableForeignKeyConstraints();
    }

    private function jalankanSql(string $path): void
    {
        $delimiter = ';';
        $buffer = '';

        foreach (preg_split('/\R/', file_get_contents($path)) as $baris) {
            if (preg_match('/^\s*DELIMITER\s+(\S+)\s*$/i', $baris, $m)) {
                $delimiter = $m[1];
                continue;
            }
            if ($buffer === '' && (trim($baris) === '' || str_starts_with(ltrim($baris), '--'))) {
                continue;
            }
            $buffer .= $baris."\n";
            if (str_ends_with(rtrim($baris), $delimiter)) {
                $sql = trim(substr(rtrim($buffer), 0, -strlen($delimiter)));
                if ($sql !== '') {
                    DB::unprepared($sql);
                }
                $buffer = '';
            }
        }
    }
};
