<?php
namespace Database\Seeders;

use App\Models\Divisi;
use App\Models\Pengguna;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::unprepared(file_get_contents(database_path('sql/referensi.sql')));

        $divisi = Divisi::pluck('id', 'kode');
        $akun = [
            ['Admin TI',       'admin',    'admin',    'TI'],
            ['Petugas Satu',   'petugas1', 'petugas',  'PMS'],
            ['PIC Klaim Satu', 'pic.klm1', 'pic',      'KLM'],
            ['PIC Klaim Dua',  'pic.klm2', 'pic',      'KLM'],
            ['PIC Pemasaran',  'pic.pms1', 'pic',      'PMS'],
            ['Pimpinan',       'pimpinan', 'pimpinan', null],
        ];

        foreach ($akun as [$nama, $username, $peran, $kodeDivisi]) {
            Pengguna::create([
                'nama' => $nama,
                'username' => $username,
                'email' => "{$username}@contoh.id",
                'password_hash' => 'password123',
                'peran' => $peran,
                'divisi_id' => $kodeDivisi ? $divisi[$kodeDivisi] : null,
            ]);
        }
    }
}
