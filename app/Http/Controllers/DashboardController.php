<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $f = $request->validate([
            'dari' => 'nullable|date', 'sampai' => 'nullable|date',
            'divisi' => 'nullable|integer', 'kategori' => 'nullable|integer', 'kanal' => 'nullable|integer', 'jenis' => 'nullable|in:pertanyaan,pengaduan', 'q' => 'nullable|string|max:20',
        ]);
        $dari = $f['dari'] ?? now()->subWeeks(12)->toDateString();
        $sampai = $f['sampai'] ?? today()->toDateString();
        $filter = fn ($q) => $q
            ->when($f['divisi'] ?? null, fn ($q, $v) => $q->where('f.divisi_id', $v))
            ->when($f['kategori'] ?? null, fn ($q, $v) => $q->where('f.kategori_id', $v))
            ->when($f['kanal'] ?? null, fn ($q, $v) => $q->where('f.kanal_id', $v))
            ->when($f['jenis'] ?? null, fn ($q, $v) => $q->where('f.jenis', $v));
        $periode = fn () => DB::table('mart_fact_layanan as f')->tap($filter)->whereBetween('f.tgl_diterima', [$dari, $sampai]);

        return view('dashboard', [
            'dari' => $dari,
            'sampai' => $sampai,
            'diperbarui' => DB::table('mart_etl_log')->where('hasil', 'berhasil')->max('selesai'),
            'ringkas' => $periode()->selectRaw("
                count(*) as masuk,
                sum(f.status_kode = 'MENUNGGU') as menunggu,
                sum(f.status_kode = 'PROSES') as proses,
                sum(f.status_kode = 'SELESAI') as selesai,
                sum(f.tepat_waktu) as jml_tepat,
                count(f.tepat_waktu) as jml_jatuh_tempo,
                round(100 * avg(f.tepat_waktu), 1) as pct_tepat,
                round(100 * avg(f.selesai_saat_diterima), 1) as pct_langsung,
                round(avg(case when not f.selesai_saat_diterima then f.lama_hk end), 1) as rata_hk")->first(),
            'perKanal' => $periode()->join('mart_dim_kanal as k', 'k.kanal_id', '=', 'f.kanal_id')
                ->groupBy('k.kanal_id', 'k.nama')->selectRaw('k.nama, count(*) as n')->orderByDesc('n')->get(),
            'perJenis' => $periode()->groupBy('f.jenis')->selectRaw('f.jenis, count(*) as n')->orderByDesc('n')->pluck('n', 'jenis'),
            'tenggat' => DB::table('mart_v_tenggat_aktif as f')->tap($filter)
                ->leftJoin('mart_dim_pic as p', 'p.pic_id', '=', 'f.pic_id')
                ->leftJoin('mart_dim_divisi as d', 'd.divisi_id', '=', 'f.divisi_id')
                ->join('mart_dim_status as s', 's.status_kode', '=', 'f.status_kode')
                ->whereIn('f.penanda', ['mendekati', 'melewati'])
                ->when($f['q'] ?? null, fn ($q, $v) => $q->where('f.nomor', 'like', "%{$v}%"))
                ->orderBy('f.sisa_hk')
                ->get(['f.layanan_id', 'f.nomor', 'f.tgl_diterima', 'f.jenis', 'f.status_kode', 'f.sisa_hk', 'f.penanda', 's.nama as status', 'd.nama as divisi', 'p.nama as pic']),
            'divisiList' => DB::table('mart_dim_divisi')->orderBy('nama')->get(['divisi_id as id', 'nama']),
            'kategoriList' => DB::table('mart_dim_kategori')->orderBy('nama')->get(['kategori_id as id', 'nama']),
            'kanalList' => DB::table('mart_dim_kanal')->orderBy('nama')->get(['kanal_id as id', 'nama']),
        ]);
    }

    public function etl(Request $request)
    {
        $f = $request->validate(['tanggal' => 'nullable|date', 'mode' => 'nullable|in:inkremental,penuh']);
        $log = fn () => DB::table('mart_etl_log');

        return view('etl', [
            'log' => DB::table('mart_etl_log as e')
                ->leftJoin('layanan as l', 'l.id', '=', 'e.layanan_id')
                ->when($f['tanggal'] ?? null, fn ($q, $v) => $q->whereDate('e.mulai', $v))
                ->when($f['mode'] ?? null, fn ($q, $v) => $q->where('e.mode', $v))
                ->orderByDesc('e.id')->limit(100)
                ->get(['e.*', 'l.nomor']),
            'penuh' => $log()->where('mode', 'penuh')->where('hasil', 'berhasil')->orderByDesc('id')->first(),
            'terakhir' => $log()->where('hasil', 'berhasil')->orderByDesc('id')->first(),
            'hariIni' => $log()->whereDate('mulai', today())->selectRaw("count(*) as n, sum(mode = 'inkremental') as inkremental, sum(mode = 'penuh') as penuh")->first(),
            'gagal' => $log()->where('hasil', 'gagal')->where('mulai', '>=', now()->subDays(7))->count(),
        ]);
    }
}