<?php

use App\Enums\Peran;
use App\Http\Controllers\AkunController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LayananController;
use App\Http\Controllers\ReferensiController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'form'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', fn () => redirect(match (auth()->user()->peran) {
        Peran::Pic => route('tugas'),
        Peran::Petugas => route('layanan.index'),
        default => route('dashboard'),
    }))->name('beranda');

    Route::get('/layanan', [LayananController::class, 'index'])->name('layanan.index');

    Route::middleware('peran:petugas')->group(function () {
        Route::get('/layanan/baru', [LayananController::class, 'create'])->name('layanan.create');
        Route::post('/layanan', [LayananController::class, 'store'])->name('layanan.store');
    });

    Route::get('/layanan/{layanan}', [LayananController::class, 'show'])->whereNumber('layanan')->name('layanan.show');

    Route::middleware('peran:pic')->group(function () {
        Route::get('/tugas', [LayananController::class, 'tugas'])->name('tugas');
        Route::post('/layanan/{layanan}/status', [LayananController::class, 'status'])->name('layanan.status');
        Route::post('/layanan/{layanan}/alihkan', [LayananController::class, 'alihkan'])->name('layanan.alihkan');
        Route::post('/layanan/{layanan}/perpanjang', [LayananController::class, 'perpanjang'])->name('layanan.perpanjang');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('peran:petugas,pimpinan,admin')->name('dashboard');

    Route::middleware('peran:admin')->prefix('pengaturan')->group(function () {
        Route::get('/akun', [AkunController::class, 'index'])->name('akun.index');
        Route::post('/akun', [AkunController::class, 'store'])->name('akun.store');
        Route::put('/akun/{pengguna}', [AkunController::class, 'update'])->whereNumber('pengguna')->name('akun.update');
        Route::get('/referensi', [ReferensiController::class, 'index'])->name('referensi.index');
        Route::post('/referensi/{jenis}', [ReferensiController::class, 'simpan'])->name('referensi.store');
        Route::put('/referensi/{jenis}/{id}', [ReferensiController::class, 'simpan'])->name('referensi.update');
        Route::delete('/referensi/libur/{tanggal}', [ReferensiController::class, 'hapusLibur'])->name('referensi.libur.hapus');
    });

    Route::middleware('peran:admin')->group(function () {
        Route::get('/etl', [DashboardController::class, 'etl'])->name('etl');
    });
});