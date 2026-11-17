<?php
namespace App\Support;

use Illuminate\Support\Facades\DB;
use Throwable;

final class Audit
{
    public static function run(callable $fn, ?string $catatan = null): mixed
    {
        DB::statement('SET @app_user_id = ?, @app_catatan = ?', [auth()->id(), $catatan]);

        try {
            return DB::transaction($fn);
        } finally {
            DB::statement('SET @app_user_id = NULL, @app_catatan = NULL');
        }
    }

    public static function etl(int $layananId): void
    {
        DB::afterCommit(fn () => self::jalankanEtl($layananId));
    }

    public static function jalankanEtl(?int $layananId = null): void
    {
        $mulai = now();

        try {
            DB::transaction(fn () => DB::statement('CALL mart_jalankan_etl(?)', [$layananId]));
        } catch (Throwable $e) {
            DB::table('mart_etl_log')->insert([
                'mulai' => $mulai,
                'selesai' => now(),
                'mode' => $layananId ? 'inkremental' : 'penuh',
                'layanan_id' => $layananId,
                'hasil' => 'gagal',
                'pesan' => $e->getMessage(),
            ]);
            report($e);
        }
    }
}
