<?php

namespace Tests\Feature;

use App\Models\Layanan;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function akun(string $username): Pengguna
    {
        return Pengguna::where('username', $username)->firstOrFail();
    }

    private function catat(): Layanan
    {
        $pic = $this->akun('pic.klm1');
        $this->actingAs($this->akun('petugas1'))->post('/layanan', [
            'nama' => 'Dummy', 'email' => 'd@contoh.id', 'telepon' => '0800',
            'kanal_id' => 1, 'kategori_id' => 1, 'jenis' => 'pengaduan', 'bentuk' => 'lisan',
            'uraian' => 'uji web', 'diterima_pada' => now()->subHour()->format('Y-m-d\TH:i'),
            'divisi_tujuan_id' => $pic->divisi_id, 'pic_id' => $pic->id,
        ])->assertRedirect()->assertSessionHasNoErrors();

        return Layanan::latest('id')->firstOrFail();
    }

    public function test_login_username_dan_akun_nonaktif_ditolak(): void
    {
        $this->post('/login', ['username' => 'petugas1', 'password' => 'password123'])->assertRedirect('/');
        $this->post('/logout');
        $this->post('/login', ['username' => 'pimpinan@contoh.id', 'password' => 'password123'])->assertRedirect('/');
        $this->post('/logout');

        $this->akun('pic.klm2')->update(['aktif' => false]);
        $this->post('/login', ['username' => 'pic.klm2', 'password' => 'password123'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_alur_catat_tangani_selesai(): void
    {
        $layanan = $this->catat();
        $this->assertNotNull($layanan->tenggat);

        $pic = $this->akun('pic.klm1');
        $this->actingAs($pic)->get('/tugas')->assertOk()->assertSee($layanan->nomor);
        $this->post("/layanan/{$layanan->id}/status", ['status_kode' => 'PROSES', 'catatan' => 'mulai'])->assertSessionHasNoErrors();
        $this->post("/layanan/{$layanan->id}/perpanjang", ['tambahan_hk' => 2, 'alasan' => 'menunggu dokumen'])->assertSessionHasNoErrors();
        $this->post("/layanan/{$layanan->id}/status", ['status_kode' => 'SELESAI', 'hasil' => 'beres', 'metode_penyampaian' => 'email'])->assertSessionHasNoErrors();

        $this->assertSame('SELESAI', $layanan->refresh()->status_kode);
        $this->assertSame(['dibuat', 'status', 'perpanjangan', 'status'], $layanan->riwayat->pluck('jenis')->all());
        $this->get("/layanan/{$layanan->id}")->assertOk()->assertSee('beres');
        $this->get('/tugas?tab=selesai')->assertOk()->assertSee($layanan->nomor);
    }

    public function test_pic_salah_divisi_ditolak_database(): void
    {
        $this->actingAs($this->akun('petugas1'))->post('/layanan', [
            'nama' => 'Dummy', 'email' => 'd@contoh.id', 'telepon' => '0800',
            'kanal_id' => 1, 'kategori_id' => 1, 'jenis' => 'pengaduan', 'bentuk' => 'lisan',
            'uraian' => 'x', 'diterima_pada' => now()->subHour()->format('Y-m-d\TH:i'),
            'divisi_tujuan_id' => $this->akun('pic.pms1')->divisi_id, 'pic_id' => $this->akun('pic.klm1')->id,
        ])->assertSessionHasErrors(['db' => 'PIC harus berasal dari divisi tujuan']);
    }

    public function test_hak_akses(): void
    {
        $layanan = $this->catat();

        $this->actingAs($this->akun('pic.pms1'))->get("/layanan/{$layanan->id}")->assertForbidden();
        $this->post("/layanan/{$layanan->id}/status", ['status_kode' => 'PROSES', 'catatan' => 'x'])->assertForbidden();
        $this->get('/dashboard')->assertForbidden();
        $this->actingAs($this->akun('petugas1'))->get('/dashboard')->assertOk();
        $this->get('/layanan/baru')->assertOk();
        $this->get('/layanan?q=Dummy')->assertOk()->assertSee($layanan->nomor);
        $this->actingAs($this->akun('pimpinan'))->get('/dashboard')->assertOk();
        $this->post("/layanan/{$layanan->id}/status", ['status_kode' => 'SELESAI', 'hasil' => 'x', 'metode_penyampaian' => 'email'])->assertForbidden();
        $this->actingAs($this->akun('admin'))->get('/etl')->assertOk();
    }
}