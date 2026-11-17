<?php

namespace Tests\Feature;

use App\Models\Konsumen;
use App\Models\Layanan;
use App\Models\Pengguna;
use App\Support\Audit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function akun(string $username): Pengguna
    {
        return Pengguna::where('username', $username)->firstOrFail();
    }

    private function dataAkun(Pengguna $p, array $ubah = []): array
    {
        return [...$p->only(['nama', 'username', 'email', 'divisi_id']), 'peran' => $p->peran->value, 'aktif' => $p->aktif ? '1' : null, ...$ubah];
    }

    private function tugasUntuk(Pengguna $pic): Layanan
    {
        Auth::setUser($this->akun('petugas1'));

        return Audit::run(fn () => Layanan::create([
            'konsumen_id' => Konsumen::create(['nama' => 'D', 'email' => 'd@contoh.id', 'telepon' => '0800'])->id,
            'kanal_id' => 1, 'kategori_id' => 1, 'jenis' => 'pengaduan', 'bentuk' => 'lisan', 'uraian' => 'uji',
            'divisi_tujuan_id' => $pic->divisi_id, 'pic_id' => $pic->id, 'dicatat_oleh' => auth()->id(),
        ])->refresh());
    }

    public function test_hanya_admin(): void
    {
        $this->actingAs($this->akun('pimpinan'))->get('/pengaturan/akun')->assertForbidden();
        $this->get('/pengaturan/referensi')->assertForbidden();
        $this->actingAs($this->akun('admin'))->get('/pengaturan/akun')->assertOk()->assertSee('pic.klm1');
        $this->get('/pengaturan/referensi?tab=libur')->assertOk();
    }

    public function test_tambah_akun_lalu_login(): void
    {
        $admin = $this->akun('admin');
        $this->actingAs($admin)->post('/pengaturan/akun', [
            'nama' => 'PIC Baru', 'username' => 'pic.baru', 'email' => 'pic.baru@contoh.id',
            'peran' => 'pic', 'divisi_id' => $this->akun('pic.klm1')->divisi_id, 'password' => 'rahasia123',
        ])->assertSessionHasNoErrors();

        $baru = $this->akun('pic.baru');
        $this->assertSame($admin->id, $baru->dibuat_oleh);
        $this->assertNotSame('rahasia123', $baru->password_hash);
        $this->post('/logout');
        $this->post('/login', ['username' => 'pic.baru', 'password' => 'rahasia123'])->assertRedirect('/');

        $this->actingAs($admin)->post('/pengaturan/akun', [
            'nama' => 'X', 'username' => 'x', 'email' => 'x@contoh.id', 'peran' => 'pic', 'password' => 'rahasia123',
        ])->assertSessionHasErrors('divisi_id');
    }

    public function test_admin_tidak_bisa_mengunci_diri(): void
    {
        $admin = $this->akun('admin');
        $this->actingAs($admin)->put("/pengaturan/akun/{$admin->id}", $this->dataAkun($admin, ['aktif' => null]))->assertSessionHasErrors('aktif');
        $this->assertTrue($admin->refresh()->aktif);
    }

    public function test_pic_dengan_tugas_aktif_tidak_bisa_dinonaktifkan(): void
    {
        $pic = $this->akun('pic.klm1');
        $this->tugasUntuk($pic);

        $this->actingAs($this->akun('admin'))->put("/pengaturan/akun/{$pic->id}", $this->dataAkun($pic, ['aktif' => null]))->assertSessionHasErrors('aktif');
        $this->assertTrue($pic->refresh()->aktif);

        $lain = $this->akun('pic.klm2');
        $this->put("/pengaturan/akun/{$lain->id}", $this->dataAkun($lain, ['aktif' => null]))->assertSessionHasNoErrors();
        $this->assertFalse($lain->refresh()->aktif);

        $this->actingAs($lain)->get('/tugas')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_hari_libur_menggeser_tenggat_dan_status_terkunci(): void
    {
        $layanan = $this->tugasUntuk($this->akun('pic.klm1'));
        $lama = $layanan->tenggat->toDateString();
        $hariKerja = DB::table('kalender')->where('hari_kerja', true)->where('tanggal', '>', $layanan->mulai_hitung->toDateString())->orderBy('tanggal')->value('tanggal');

        $this->actingAs($this->akun('admin'))->post('/pengaturan/referensi/libur', ['tanggal' => $hariKerja, 'nama' => 'Libur uji'])->assertSessionHasNoErrors();
        $this->assertGreaterThan($lama, $layanan->refresh()->tenggat->toDateString());

        $this->delete("/pengaturan/referensi/libur/{$hariKerja}")->assertSessionHasNoErrors();
        $this->assertSame($lama, $layanan->refresh()->tenggat->toDateString());

        $this->post('/pengaturan/referensi/status', ['nama' => 'Baru', 'urutan' => 9])->assertNotFound();
        $this->post('/pengaturan/referensi/kategori', ['kode' => 'ras', 'nama' => 'Reasuransi', 'divisi_usulan_id' => 1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('kategori', ['kode' => 'RAS', 'aktif' => true]);
    }
}