<?php

namespace App\Providers;

use App\Enums\Peran;
use App\Models\Layanan;
use App\Models\Pengguna;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Gate::define('lihat-layanan', fn (Pengguna $u, Layanan $l) => $u->peran !== Peran::Pic || (int) $l->pic_id === (int) $u->id);

        Gate::define('tangani-layanan', fn (Pengguna $u, Layanan $l) => $u->peran === Peran::Pic
            && (int) $l->pic_id === (int) $u->id
            && $l->status_kode !== 'SELESAI');
    }
}