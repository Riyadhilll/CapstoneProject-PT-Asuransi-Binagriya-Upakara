<?php

namespace Tests\Feature;

use App\Models\Konsumen;
use App\Models\Layanan;
use App\Models\Pengguna;
use App\Support\Audit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FiturLanjutanTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function akun(string $username): Pengguna
    {
        return Pengguna::where('username', $username)->firstOrFail();
    }

    private function form(array $ubah = []): array
    {
        $pic = $this->akun('pic.klm1');

        return [
            'nama' => 'Dummy', 'email' => 'd@contoh.id', 'telepon' => '0800', 'kanal_id' => 1, 'kategori_id' => 1,
            'jenis' => 'pengaduan', 'bentuk' => 'lisan', 'uraian' => 'uji', 'diterima_pada' => now()->format('Y-m-d\TH:i'),
            'divisi_tujuan_id' => $pic->divisi_id, 'pic_id' => $pic->id, ...$ubah,
        ];
    }

    private function catatLewatWeb(array $ubah = []): Layanan
    {
        $this->actingAs($this->akun('petugas1'))->post('/layanan', $this->form($ubah))->assertSessionHasNoErrors();

        return Layanan::latest('id')->firstOrFail();
    }

    private function catatLama(string $diterima, array $ubah = []): Layanan
    {
        Auth::setUser($this->akun('petugas1'));
        $pic = $this->akun('pic.klm1');

        return Audit::run(function () use ($diterima, $pic, $ubah) {
            $l = Layanan::create([
                'konsumen_id' => Konsumen::create(['nama' => 'Lama', 'email' => 'l@contoh.id', 'telepon' => '0801'])->id,
                'kanal_id' => 2, 'kategori_id' => 2, 'jenis' => 'pertanyaan', 'bentuk' => 'lisan', 'uraian' => 'lama',
                'diterima_pada' => $diterima, 'divisi_tujuan_id' => $pic->divisi_id, 'pic_id' => $pic->id,
                'dicatat_oleh' => auth()->id(), ...$ubah,
            ]);
            Audit::etl($l->id);

            return $l->refresh();
        });
    }

    public function test_kf05_selesai_saat_diterima_lewat_form(): void
    {
        $l = $this->catatLewatWeb(['selesai_saat_diterima' => '1', 'hasil' => 'Dijawab langsung', 'metode_penyampaian' => 'telepon']);

        $this->assertSame('SELESAI', $l->status_kode);
        $this->assertNull($l->pic_id);
        $this->assertNull($l->divisi_tujuan_id);
        $this->assertNotNull($l->selesai_pada);
        $this->assertTrue($l->tepatWaktu());
        $this->assertSame(1, (int) DB::table('mart_fact_layanan')->where('layanan_id', $l->id)->value('tepat_waktu'));

        $this->post('/layanan', $this->form(['selesai_saat_diterima' => '1']))->assertSessionHasErrors(['hasil', 'metode_penyampaian']);
    }

    public function test_kf12_alihkan_pic(): void
    {
        $l = $this->catatLewatWeb();
        $lama = $this->akun('pic.klm1');
        $baru = $this->akun('pic.klm2');

        $this->actingAs($lama)->post("/layanan/{$l->id}/alihkan", ['divisi_tujuan_id' => $lama->divisi_id, 'pic_id' => $lama->id, 'alasan' => 'x'])
            ->assertSessionHasErrors('pic_id');
        $this->post("/layanan/{$l->id}/alihkan", ['divisi_tujuan_id' => $baru->divisi_id, 'pic_id' => $baru->id, 'alasan' => 'Beban kerja'])
            ->assertRedirect('/tugas')->assertSessionHasNoErrors();

        $this->assertSame($baru->id, $l->refresh()->pic_id);
        $r = $l->riwayat->firstWhere('jenis', 'penugasan');
        $this->assertSame([$lama->id, $baru->id, 'Beban kerja', $lama->id], [$r->pic_lama, $r->pic_baru, $r->catatan, $r->oleh]);
        $this->assertSame(1, (int) DB::table('mart_fact_layanan')->where('layanan_id', $l->id)->value('jumlah_pengalihan'));

        $this->get("/layanan/{$l->id}")->assertForbidden();
        $this->actingAs($baru)->get("/layanan/{$l->id}")->assertOk()->assertSee('PIC dialihkan');
    }

    public function test_tertulis_tanpa_dokumen_tenggat_belum_berjalan(): void
    {
        $l = $this->catatLewatWeb(['bentuk' => 'tertulis']);
        $this->assertNull($l->tenggat);
        $this->assertSame('menunggu_dokumen', DB::table('mart_v_tenggat_aktif')->where('layanan_id', $l->id)->value('penanda'));

        $this->actingAs($this->akun('pic.klm1'))->get("/layanan/{$l->id}")->assertOk()->assertSee('Belum berjalan')->assertDontSee('Catat Perpanjangan');
        $this->post("/layanan/{$l->id}/perpanjang", ['tambahan_hk' => 2, 'alasan' => 'x'])
            ->assertSessionHasErrors(['db' => 'Tenggat belum berjalan (dokumen belum lengkap)']);

        $this->assertSame(10, $this->catatLewatWeb(['bentuk' => 'tertulis', 'tgl_dokumen_lengkap' => today()->toDateString()])->batas_hk);
        $this->assertNull($this->catatLewatWeb(['tgl_dokumen_lengkap' => today()->toDateString()])->tgl_dokumen_lengkap);
    }

    public function test_filter_daftar_tugas_dashboard_etl(): void
    {
        $baru = $this->catatLewatWeb();
        $lama = $this->catatLama(now()->subDays(20)->format('Y-m-d 09:00:00'));
        $nomor = fn ($res) => collect($res->viewData('layanan')->items())->pluck('nomor')->all();

        $this->actingAs($this->akun('pimpinan'));
        $this->assertSame([$baru->nomor], $nomor($this->get('/layanan?kategori=1')));
        $this->assertSame([$lama->nomor], $nomor($this->get('/layanan?kanal=2')));
        $this->assertSame([$baru->nomor], $nomor($this->get('/layanan?jenis=pengaduan')->assertSeeInOrder(['Jenis', $baru->nomor, 'Pengaduan'])));
        $this->assertSame([$lama->nomor], $nomor($this->get('/layanan?jenis=pertanyaan')));
        $this->assertSame([], $nomor($this->get('/layanan?jenis=pertanyaan&kategori=1')));
        $this->get('/layanan?jenis=lainnya')->assertSessionHasErrors('jenis');
        $this->assertSame([$lama->nomor], $nomor($this->get('/layanan?q='.$lama->nomor)->assertSee('name="q"', false)));
        $this->assertSame([$baru->nomor], $nomor($this->get('/layanan?q=Dummy')));
        $this->assertSame([$lama->nomor], $nomor($this->get('/layanan?sampai='.now()->subDays(10)->toDateString())));
        $this->assertSame([$baru->nomor], $nomor($this->get('/layanan?dari='.today()->toDateString())));
        $this->assertSame([], $nomor($this->get('/layanan?status=SELESAI')));
        $this->get('/layanan?kategori=999')->assertSee('Layanan tidak ditemukan')->assertDontSee('btn-garis mt-4');
        $this->get('/layanan?dari=bukan-tanggal')->assertSessionHasErrors('dari');

        $tenggat = $this->actingAs($this->akun('pic.klm1'))->get('/tugas?tab=tenggat');
        $this->assertSame([$lama->nomor], $nomor($tenggat));
        $this->assertSame([$baru->nomor], $nomor($this->get('/tugas?q='.$baru->nomor)));
        $this->assertSame([$lama->nomor], $nomor($this->get('/tugas?jenis=pertanyaan')));
        $this->assertSame(['aktif' => 2, 'tenggat' => 1, 'selesai' => 0], $tenggat->viewData('jumlah'));

        $dari = now()->subDays(30)->toDateString();
        $dash = $this->actingAs($this->akun('pimpinan'))->get("/dashboard?dari={$dari}&kategori=2")->assertOk();
        $this->assertSame(1, (int) $dash->viewData('ringkas')->masuk);
        $this->assertSame([$lama->nomor], $dash->viewData('tenggat')->pluck('nomor')->all());
        $this->assertSame(2, (int) $this->get("/dashboard?dari={$dari}")->viewData('ringkas')->masuk);
        $jenis = $this->get("/dashboard?dari={$dari}&jenis=pertanyaan");
        $this->assertSame(1, (int) $jenis->viewData('ringkas')->masuk);
        $this->assertSame([$lama->nomor], $jenis->viewData('tenggat')->pluck('nomor')->all());
        $this->assertSame([], $this->get("/dashboard?dari={$dari}&jenis=pengaduan")->viewData('tenggat')->all());
        $this->assertSame([$lama->nomor], $this->get("/dashboard?dari={$dari}&q=".substr($lama->nomor, -4))->viewData('tenggat')->pluck('nomor')->all());
        $this->assertSame([], $this->get("/dashboard?dari={$dari}&q=TIDAKADA")->assertSee('Semua layanan aktif masih dalam tenggat')->viewData('tenggat')->all());

        Audit::jalankanEtl();
        $this->actingAs($this->akun('admin'));
        $log = $this->get('/etl?mode=penuh')->assertOk()->viewData('log');
        $this->assertNotEmpty($log);
        $this->assertTrue($log->every(fn ($r) => $r->mode === 'penuh' && $r->hasil === 'berhasil' && $r->jumlah_app === $r->jumlah_mart));
    }

    public function test_admin_reset_sandi_dan_referensi_nonaktif(): void
    {
        $admin = $this->akun('admin');
        $petugas = $this->akun('petugas1');
        $data = [...$petugas->only(['nama', 'username', 'email', 'divisi_id']), 'peran' => 'petugas', 'aktif' => '1'];

        $this->actingAs($admin)->put("/pengaturan/akun/{$petugas->id}", $data)->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('password123', $petugas->refresh()->password_hash), 'tanpa sandi baru, sandi lama tetap');

        $this->put("/pengaturan/akun/{$petugas->id}", [...$data, 'password' => 'pendek'])->assertSessionHasErrors('password');
        $this->put("/pengaturan/akun/{$petugas->id}", [...$data, 'password' => 'sandiBaru123'])->assertSessionHasNoErrors();
        $this->post('/logout');
        $this->post('/login', ['username' => 'petugas1', 'password' => 'password123'])->assertSessionHasErrors('username');
        $this->post('/login', ['username' => 'petugas1', 'password' => 'sandiBaru123'])->assertRedirect('/');
        $this->post('/logout');

        $surveyor = DB::table('kanal')->where('kode', 'SVY')->first();
        $this->actingAs($admin)->put("/pengaturan/referensi/kanal/{$surveyor->id}", ['kode' => 'SVY', 'nama' => 'Surveyor'])->assertSessionHasNoErrors();
        $this->assertFalse((bool) DB::table('kanal')->where('id', $surveyor->id)->value('aktif'));
        $this->assertSame(0, (int) DB::table('mart_dim_kanal')->where('kanal_id', $surveyor->id)->value('aktif'));
        $this->actingAs($this->akun('petugas1'))->get('/layanan/baru')->assertOk()->assertDontSee('Surveyor');

        $this->actingAs($admin)->put('/pengaturan/referensi/status/PROSES', ['nama' => 'Sedang Ditangani', 'urutan' => 2])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('status', ['kode' => 'PROSES', 'nama' => 'Sedang Ditangani']);
        $this->put('/pengaturan/referensi/status/SELESAI', ['nama' => 'Beres', 'urutan' => 2])->assertSessionHasErrors('urutan');
    }

    public function test_tanggal_di_luar_kalender_atau_dokumen_sebelum_diterima_ditolak(): void
    {
        $this->actingAs($this->akun('petugas1'))
            ->post('/layanan', $this->form(['diterima_pada' => '2016-11-10T09:00']))
            ->assertSessionHasErrors('diterima_pada');

        $tertulis = ['bentuk' => 'tertulis', 'diterima_pada' => '2026-11-10T09:00'];
        $this->post('/layanan', $this->form([...$tertulis, 'tgl_dokumen_lengkap' => '2026-11-01']))
            ->assertSessionHasErrors('tgl_dokumen_lengkap');
        $this->assertSame(0, Layanan::count());

        $l = $this->catatLewatWeb([...$tertulis, 'tgl_dokumen_lengkap' => '2026-11-10']);
        $this->assertSame('2026-11-10', $l->mulai_hitung->toDateString());
    }

    public function test_reset_sandi_mengakhiri_sesi_lain(): void
    {
        $petugas = $this->akun('petugas1');
        $pic = $this->akun('pic.klm1');
        $tokenLama = $petugas->remember_token;
        foreach ([['sesi-petugas', $petugas->id], ['sesi-pic', $pic->id]] as [$id, $user]) {
            DB::table('sessions')->insert(['id' => $id, 'user_id' => $user, 'payload' => '', 'last_activity' => time()]);
        }
        $data = [...$petugas->only(['nama', 'username', 'email', 'divisi_id']), 'peran' => 'petugas', 'aktif' => '1'];

        $this->actingAs($this->akun('admin'))->put("/pengaturan/akun/{$petugas->id}", $data)->assertSessionHasNoErrors();
        $this->assertTrue(DB::table('sessions')->where('id', 'sesi-petugas')->exists(), 'tanpa sandi baru, sesi tetap');

        $this->put("/pengaturan/akun/{$petugas->id}", [...$data, 'password' => 'sandiBaru123'])->assertSessionHasNoErrors();
        $this->assertFalse(DB::table('sessions')->where('id', 'sesi-petugas')->exists());
        $this->assertTrue(DB::table('sessions')->where('id', 'sesi-pic')->exists());
        $this->assertNotSame($tokenLama, $petugas->refresh()->remember_token);
    }

    public function test_etl_gagal_dicatat_tanpa_membatalkan_simpan(): void
    {
        config(['database.connections.kedua' => config('database.connections.mysql')]);
        $kedua = DB::connection('kedua');
        $kedua->unprepared('LOCK TABLES mart_dim_divisi WRITE');
        DB::statement('SET SESSION lock_wait_timeout = 1');
        try {
            $l = $this->catatLewatWeb();
        } finally {
            DB::statement('SET SESSION lock_wait_timeout = DEFAULT');
            $kedua->unprepared('UNLOCK TABLES');
            DB::purge('kedua');
        }

        $this->assertMatchesRegularExpression('/^LK-\d{6}-\d{4}$/', $l->nomor);
        $log = DB::table('mart_etl_log')->orderByDesc('id')->first();
        $this->assertSame(['gagal', $l->id], [$log->hasil, (int) $log->layanan_id]);
        $this->assertStringContainsString('Lock wait timeout', $log->pesan);
        $this->assertFalse(DB::table('mart_fact_layanan')->where('layanan_id', $l->id)->exists());
    }

    public function test_status_sama_ditolak_dan_libur_batal_bila_hitung_ulang_gagal(): void
    {
        $l = $this->catatLewatWeb();
        $pic = $this->akun('pic.klm1');
        $this->actingAs($pic)->post("/layanan/{$l->id}/status", ['status_kode' => 'PROSES', 'catatan' => 'mulai'])->assertSessionHasNoErrors();
        $this->post("/layanan/{$l->id}/status", ['status_kode' => 'PROSES', 'catatan' => 'catatan kedua'])->assertSessionHasErrors('status_kode');

        DB::table('pengguna')->where('id', $pic->id)->update(['aktif' => false]);
        $sebelum = DB::table('hari_libur')->count();
        $this->actingAs($this->akun('admin'))->post('/pengaturan/referensi/libur', ['tanggal' => '2030-01-02', 'nama' => 'Uji'])
            ->assertSessionHasErrors(['db' => 'PIC harus akun aktif berperan pic']);
        $this->assertSame($sebelum, DB::table('hari_libur')->count());
        $this->assertTrue((bool) DB::table('kalender')->where('tanggal', '2030-01-02')->value('hari_kerja'));
    }}
