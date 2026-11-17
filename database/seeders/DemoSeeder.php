<?php

namespace Database\Seeders;

use App\Models\Kanal;
use App\Models\Kategori;
use App\Models\Konsumen;
use App\Models\Layanan;
use App\Models\Pengguna;
use App\Support\Audit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(7);
        $petugas = Pengguna::where('username', 'petugas1')->firstOrFail();
        $pics = Pengguna::where('peran', 'pic')->where('aktif', true)->get();
        $kategori = Kategori::all();
        $kanal = Kanal::pluck('id');

        for ($i = 1; $i <= 60; $i++) {
            $diterima = now()->subDays(mt_rand(0, 70))->setTime(mt_rand(8, 15), mt_rand(0, 59));
            $diterima = $diterima->min(now());
            $kat = $kategori->random();
            $pic = $pics->where('divisi_id', $kat->divisi_usulan_id)->random();
            $langsung = mt_rand(1, 100) <= 25;
            $bentuk = mt_rand(1, 100) <= 70 ? 'lisan' : 'tertulis';

            Auth::setUser($petugas);
            $layanan = Audit::run(fn () => Layanan::create([
                'konsumen_id' => Konsumen::create(['nama' => "Konsumen Dummy {$i}", 'email' => "dummy{$i}@contoh.id", 'telepon' => '0800'.$i])->id,
                'kanal_id' => $kanal->random(),
                'kategori_id' => $kat->id,
                'jenis' => mt_rand(0, 1) ? 'pertanyaan' : 'pengaduan',
                'bentuk' => $bentuk,
                'uraian' => 'Data dummy demo',
                'diterima_pada' => $diterima,
                'tgl_dokumen_lengkap' => $bentuk === 'tertulis' ? $diterima->copy()->addDays(mt_rand(0, 2))->min(now())->toDateString() : null,
                'selesai_saat_diterima' => $langsung,
                'hasil' => $langsung ? 'Dijawab langsung' : null,
                'metode_penyampaian' => $langsung ? 'telepon' : null,
                'divisi_tujuan_id' => $langsung ? null : $pic->divisi_id,
                'pic_id' => $langsung ? null : $pic->id,
                'dicatat_oleh' => $petugas->id,
            ])->refresh());

            $umur = $diterima->diffInDays(now());
            if ($langsung || $umur < 3) {
                continue;
            }

            Auth::setUser($pic);
            Audit::run(fn () => $layanan->update(['status_kode' => 'PROSES']), 'Mulai ditangani');
            if ($umur >= 8 && mt_rand(1, 100) <= 75) {
                Audit::run(fn () => $layanan->forceFill([
                    'status_kode' => 'SELESAI',
                    'hasil' => 'Selesai ditangani',
                    'metode_penyampaian' => 'email',
                    'selesai_pada' => $diterima->copy()->addDays(mt_rand(2, 14))->min(now()),
                ])->save());
            }
        }

        Auth::forgetUser();
        Audit::jalankanEtl();
    }
}