<?php

use App\Models\HariLibur;
use App\Support\Audit;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schedule;

Artisan::command('libur:impor {tahun?* : Tahun yang diambil (bawaan: tahun ini dan tahun depan)}', function () {
    $daftarTahun = $this->argument('tahun') ?: [now()->year, now()->year + 1];
    $calon = [];

    foreach ($daftarTahun as $tahun) {
        try {
            $respon = Http::timeout(15)->retry(2, 500, throw: false)->acceptJson()
                ->get(config('services.hari_libur.url'), ['year' => $tahun]);
        } catch (ConnectionException $e) {
            $this->warn("{$tahun}: API tidak dapat dihubungi.");

            continue;
        }
        if (! $respon->successful() || ! is_array($respon->json())) {
            $this->warn("{$tahun}: API membalas HTTP {$respon->status()}.");

            continue;
        }

        foreach ($respon->json() as $h) {
            $tanggal = is_array($h) ? ($h['date'] ?? null) : null;
            $nama = is_array($h) ? trim((string) ($h['name'] ?? '')) : '';
            if (! is_string($tanggal) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)
                || date('Y-m-d', strtotime($tanggal)) !== $tanggal || ! str_starts_with($tanggal, "{$tahun}-")
                || $tanggal < '2020-01-01' || $tanggal > '2035-12-31' || $nama === '') {
                continue;
            }
            $calon[$tanggal] = mb_substr($nama, 0, 150);
        }
        $this->info("{$tahun}: ".count($respon->json())." tanggal dari API.");
    }

    $totalBaru = DB::transaction(function () use ($calon) {
        $baru = 0;
        foreach ($calon as $tanggal => $nama) {
            $baru += (int) HariLibur::firstOrCreate(['tanggal' => $tanggal], ['nama' => $nama])->wasRecentlyCreated;
        }
        if ($baru > 0) {
            DB::statement('CALL hitung_ulang_tenggat()');
        }

        return $baru;
    });
    if ($totalBaru > 0) {
        Audit::jalankanEtl();
    }
    $this->info("Selesai: {$totalBaru} hari libur baru.");
})->purpose('Ambil hari libur nasional dan cuti bersama dari API publik');

Schedule::call(fn () => Audit::jalankanEtl())
    ->name('etl-penuh')
    ->dailyAt('01:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();

Schedule::command('libur:impor')
    ->weeklyOn(1, '02:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();