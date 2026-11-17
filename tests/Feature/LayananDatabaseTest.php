<?php
namespace Tests\Feature;

use App\Models\Konsumen;
use App\Models\Layanan;
use App\Models\Pengguna;
use App\Support\Audit;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LayananDatabaseTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function buatLayanan(): Layanan
    {
        $this->actingAs(Pengguna::where('username', 'petugas1')->firstOrFail());
        $pic = Pengguna::where('username', 'pic.klm1')->firstOrFail();

        return Audit::run(function () use ($pic) {
            $k = Konsumen::create(['nama' => 'Dummy', 'email' => 'd@contoh.id', 'telepon' => '0800']);
            $l = Layanan::create([
                'konsumen_id' => $k->id, 'kanal_id' => 1, 'kategori_id' => 1,
                'jenis' => 'pengaduan', 'bentuk' => 'lisan', 'uraian' => 'uji',
                'diterima_pada' => '2026-11-16 09:00:00',
                'divisi_tujuan_id' => $pic->divisi_id, 'pic_id' => $pic->id,
                'dicatat_oleh' => auth()->id(),
            ]);
            Audit::etl($l->id);

            return $l->refresh();
        });
    }

    public function test_nomor_dan_tenggat_lisan(): void
    {
        DB::table('hari_libur')->insert(['tanggal' => '2026-11-20', 'nama' => 'Libur uji']);
        $l = $this->buatLayanan();

        $this->assertMatchesRegularExpression('/^LK-\d{6}-\d{4}$/', $l->nomor);
        $this->assertSame('2026-11-24', $l->tenggat->toDateString());
    }

    public function test_ubah_status_tanpa_audit_ditolak(): void
    {
        $l = $this->buatLayanan();
        $this->expectException(QueryException::class);
        $l->update(['status_kode' => 'PROSES']);
    }

    public function test_etl_rekonsiliasi(): void
    {
        $this->buatLayanan();
        $log = DB::table('mart_etl_log')->orderByDesc('id')->first();

        $this->assertSame('berhasil', $log->hasil);
        $this->assertSame($log->jumlah_app, $log->jumlah_mart);
    }
}
