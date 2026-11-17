<?php

use App\Http\Middleware\CekPeran;
use App\Http\Middleware\PastikanAktif;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['peran' => CekPeran::class]);
        $middleware->web(append: [PastikanAktif::class]);
        $middleware->redirectUsersTo('/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (QueryException $e, Request $request) {
            $cek = [
                'langsung_tanpa_pic' => 'Layanan yang selesai saat diterima tidak boleh punya divisi tujuan atau PIC.',
                'diteruskan_wajib_pic' => 'Layanan yang diteruskan wajib punya divisi tujuan dan PIC.',
                'selesai_lengkap' => 'Layanan selesai wajib mencatat hasil dan metode penyampaian.',
                'dokumen_hanya_tertulis' => 'Tanggal dokumen lengkap hanya untuk layanan tertulis.',
                'pic_wajib_divisi' => 'Akun PIC wajib punya divisi.',
            ];
            $errno = (int) ($e->errorInfo[1] ?? 0);
            $pesan = match (true) {
                $e->getCode() === '45000' => $e->errorInfo[2] ?? $e->getMessage(),
                $errno === 3819 => preg_match("/constraint '([^']+)'/", $e->getMessage(), $m) && isset($cek[$m[1]])
                    ? $cek[$m[1]] : 'Data melanggar aturan bisnis.',
                $errno === 1062 => 'Data tersebut sudah dipakai.',
                default => null,
            };

            if ($pesan === null || $request->expectsJson()) {
                return null;
            }

            return back()->withInput()->withErrors(['db' => $pesan]);
        });
    })->create();