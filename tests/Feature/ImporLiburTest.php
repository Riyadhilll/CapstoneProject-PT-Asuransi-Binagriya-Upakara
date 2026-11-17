<?php

namespace Tests\Feature;

use App\Models\HariLibur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImporLiburTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_impor_hanya_tanggal_valid_dan_tidak_menimpa(): void
    {
        HariLibur::create(['tanggal' => '2026-12-25', 'nama' => 'Natal (manual)']);
        Http::fake(['*' => Http::response([
            ['date' => '2026-12-24', 'name' => 'Cuti Bersama Hari Raya Natal'],
            ['date' => '2026-12-25', 'name' => 'Hari Raya Natal'],
            ['date' => '2027-01-01', 'name' => 'Tahun lain, diabaikan'],
            ['date' => '2026-02-30', 'name' => 'Tanggal tidak valid'],
            ['date' => '2026-05-01', 'name' => ''],
            'bukan objek',
        ])]);

        $this->artisan('libur:impor', ['tahun' => [2026]])->expectsOutputToContain('Selesai: 1 hari libur baru')->assertSuccessful();

        $this->assertSame(['2026-12-24', '2026-12-25'], HariLibur::orderBy('tanggal')->pluck('tanggal')->all());
        $this->assertSame('Natal (manual)', HariLibur::find('2026-12-25')->nama);
        $this->assertDatabaseHas('kalender', ['tanggal' => '2026-12-24', 'hari_kerja' => false]);
    }

    public function test_api_gagal_tidak_merusak(): void
    {
        Http::fake(['*' => Http::response('down', 500)]);

        $this->artisan('libur:impor', ['tahun' => [2026]])->expectsOutputToContain('HTTP 500')->assertSuccessful();
        $this->assertSame(0, HariLibur::count());
    }
}