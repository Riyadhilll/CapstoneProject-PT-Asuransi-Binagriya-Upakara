<?php

namespace App\Http\Controllers;

use App\Enums\Peran;
use App\Models\Divisi;
use App\Models\Kanal;
use App\Models\Kategori;
use App\Models\Konsumen;
use App\Models\Layanan;
use App\Models\Pengguna;
use App\Models\Perpanjangan;
use App\Models\Status;
use App\Support\Audit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class LayananController extends Controller
{
    private const METODE = 'in:email,telepon,whatsapp,tatap_muka';

    private const FILTER = [
        'q' => 'nullable|string|max:100', 'status' => 'nullable|in:MENUNGGU,PROSES,SELESAI', 'kategori' => 'nullable|integer',
        'kanal' => 'nullable|integer', 'jenis' => 'nullable|in:pertanyaan,pengaduan', 'divisi' => 'nullable|integer', 'dari' => 'nullable|date', 'sampai' => 'nullable|date',
    ];

    public function index(Request $request)
    {
        $f = $request->validate(self::FILTER);
        $user = $request->user();
        $dasar = fn () => Layanan::query()
            ->when($user->peran === Peran::Pic, fn ($q) => $q->where('layanan.pic_id', $user->id))
            ->tap($this->filter($f));

        $layanan = $dasar()
            ->with(['konsumen', 'kanal', 'status', 'divisiTujuan', 'pic'])
            ->leftJoin('mart_v_tenggat_aktif as t', 't.layanan_id', '=', 'layanan.id')
            ->select('layanan.*', 't.sisa_hk', 't.penanda')
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('layanan.status_kode', $v))
            ->orderByDesc('layanan.diterima_pada')
            ->paginate(10)
            ->withQueryString();

        return view('layanan.index', [
            'layanan' => $layanan,
            'jumlah' => $dasar()->groupBy('status_kode')->selectRaw('status_kode, count(*) as n')->pluck('n', 'status_kode'),
            ...$this->referensi(),
        ]);
    }

    public function tugas(Request $request)
    {
        $f = $request->validate(self::FILTER + ['tab' => 'nullable|in:aktif,tenggat,selesai']);
        $tab = $f['tab'] ?? 'aktif';
        $saya = fn () => Layanan::query()
            ->where('layanan.pic_id', $request->user()->id)
            ->tap($this->filter($f))
            ->leftJoin('mart_v_tenggat_aktif as t', 't.layanan_id', '=', 'layanan.id')
            ->leftJoin('mart_fact_layanan as mf', 'mf.layanan_id', '=', 'layanan.id')
            ->select('layanan.*', 't.sisa_hk', 't.penanda', 'mf.lama_hk', 'mf.tepat_waktu');
        $tabs = [
            'aktif' => fn ($q) => $q->where('layanan.status_kode', '!=', 'SELESAI'),
            'tenggat' => fn ($q) => $q->whereIn('t.penanda', ['mendekati', 'melewati']),
            'selesai' => fn ($q) => $q->where('layanan.status_kode', 'SELESAI')->where('layanan.selesai_pada', '>=', now()->startOfMonth()),
        ];

        return view('layanan.tugas', [
            'tab' => $tab,
            'jumlah' => array_map(fn ($t) => $t($saya())->count(), $tabs),
            'layanan' => $tabs[$tab]($saya())
                ->with(['konsumen', 'kanal', 'kategori', 'status'])
                ->orderByRaw('layanan.tenggat is null, layanan.tenggat, layanan.selesai_pada desc')
                ->paginate(10)
                ->withQueryString(),
            'baruSelesai' => $saya()
                ->with(['konsumen', 'kategori'])
                ->where('layanan.status_kode', 'SELESAI')
                ->where('layanan.selesai_pada', '>=', now()->subDays(7))
                ->orderByDesc('layanan.selesai_pada')
                ->limit(5)
                ->get(),
            ...$this->referensi(),
        ]);
    }

    public function create()
    {
        return view('layanan.create', [
            ...$this->referensi(),
            'tugasAktif' => Layanan::where('status_kode', '!=', 'SELESAI')->whereNotNull('pic_id')
                ->groupBy('pic_id')->selectRaw('pic_id, count(*) as n')->pluck('n', 'pic_id'),
            'hariKerja' => DB::table('kalender')->where('hari_kerja', true)
                ->whereBetween('tanggal', [today()->subDays(400), today()->addDays(120)])
                ->orderBy('tanggal')->pluck('tanggal'),
        ]);
    }

    public function store(Request $request)
    {
        $tglDiterima = preg_match('/^\d{4}-\d{2}-\d{2}/', (string) $request->input('diterima_pada'), $m) ? $m[0] : null;
        $data = $request->validate([
            'nama' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'telepon' => 'required|string|max:20',
            'kanal_id' => 'required|exists:kanal,id',
            'kategori_id' => 'required|exists:kategori,id',
            'jenis' => 'required|in:pertanyaan,pengaduan',
            'bentuk' => 'required|in:lisan,tertulis',
            'uraian' => 'required|string',
            'diterima_pada' => 'required|date|after_or_equal:2020-01-01|before_or_equal:now',
            'tgl_dokumen_lengkap' => ['exclude_unless:bentuk,tertulis', 'nullable', 'date', 'before_or_equal:today', ...($tglDiterima ? ['after_or_equal:'.$tglDiterima] : [])],
            'selesai_saat_diterima' => 'boolean',
            'hasil' => 'exclude_unless:selesai_saat_diterima,1|required|string',
            'metode_penyampaian' => 'exclude_unless:selesai_saat_diterima,1|required|'.self::METODE,
            'divisi_tujuan_id' => 'exclude_if:selesai_saat_diterima,1|required|exists:divisi,id',
            'pic_id' => 'exclude_if:selesai_saat_diterima,1|required|exists:pengguna,id',
        ]);
        $data['selesai_saat_diterima'] = $request->boolean('selesai_saat_diterima');

        $layanan = Audit::run(function () use ($data) {
            $konsumen = Konsumen::create(Arr::only($data, ['nama', 'email', 'telepon']));
            $layanan = Layanan::create([
                ...Arr::except($data, ['nama', 'email', 'telepon']),
                'konsumen_id' => $konsumen->id,
                'dicatat_oleh' => auth()->id(),
            ]);
            Audit::etl($layanan->id);

            return $layanan->refresh();
        });

        return redirect()->route('layanan.show', $layanan)->with('pesan', "Layanan {$layanan->nomor} tersimpan.");
    }

    public function show(Layanan $layanan)
    {
        Gate::authorize('lihat-layanan', $layanan);
        $layanan->load(['konsumen', 'kanal', 'kategori', 'status', 'divisiTujuan', 'pic', 'pencatat', 'riwayat.pengguna', 'perpanjangan']);

        return view('layanan.show', [
            'layanan' => $layanan,
            'tenggat' => DB::table('mart_v_tenggat_aktif')->where('layanan_id', $layanan->id)->first(['sisa_hk', 'penanda']),
            'fakta' => DB::table('mart_fact_layanan')->where('layanan_id', $layanan->id)->first(['lama_hk', 'tepat_waktu']),
            'namaPengguna' => Pengguna::pluck('nama', 'id'),
            'namaDivisi' => Divisi::pluck('nama', 'id'),
            'namaStatus' => Status::pluck('nama', 'kode'),
            ...$this->referensi(),
        ]);
    }

    public function status(Request $request, Layanan $layanan)
    {
        Gate::authorize('tangani-layanan', $layanan);
        $v = $request->validate([
            'status_kode' => ['required', 'in:PROSES,SELESAI', Rule::notIn([$layanan->status_kode])],
            'catatan' => 'exclude_if:status_kode,SELESAI|required|string|max:1000',
            'hasil' => 'exclude_unless:status_kode,SELESAI|required|string',
            'metode_penyampaian' => 'exclude_unless:status_kode,SELESAI|required|'.self::METODE,
        ], ['status_kode.not_in' => 'Status yang dipilih sama dengan status saat ini.']);

        Audit::run(function () use ($layanan, $v) {
            $layanan->update(Arr::except($v, 'catatan'));
            Audit::etl($layanan->id);
        }, $v['catatan'] ?? null);

        return back()->with('pesan', $v['status_kode'] === 'SELESAI' ? 'Penyelesaian layanan tersimpan.' : 'Status diperbarui.');
    }

    public function alihkan(Request $request, Layanan $layanan)
    {
        Gate::authorize('tangani-layanan', $layanan);
        $v = $request->validate([
            'divisi_tujuan_id' => 'required|exists:divisi,id',
            'pic_id' => 'required|exists:pengguna,id|not_in:'.$layanan->pic_id,
            'alasan' => 'required|string|max:1000',
        ], ['pic_id.not_in' => 'Pilih PIC lain.']);

        Audit::run(function () use ($layanan, $v) {
            $layanan->update(Arr::only($v, ['divisi_tujuan_id', 'pic_id']));
            Audit::etl($layanan->id);
        }, $v['alasan']);

        return redirect()->route('tugas')->with('pesan', "Layanan {$layanan->nomor} dialihkan.");
    }

    public function perpanjang(Request $request, Layanan $layanan)
    {
        Gate::authorize('tangani-layanan', $layanan);
        $v = $request->validate(['tambahan_hk' => 'required|integer|min:1|max:255', 'alasan' => 'required|string|max:1000']);

        Audit::run(function () use ($layanan, $v) {
            Perpanjangan::create([...$v, 'layanan_id' => $layanan->id, 'oleh' => auth()->id()]);
            Audit::etl($layanan->id);
        });

        return back()->with('pesan', "Tenggat diperpanjang {$v['tambahan_hk']} hari kerja.");
    }

    private function filter(array $f): Closure
    {
        return fn ($q) => $q
            ->when($f['q'] ?? null, fn ($q, $v) => $q->where(fn ($q) => $q
                ->where('layanan.nomor', 'like', "%{$v}%")
                ->orWhereHas('konsumen', fn ($q) => $q->where('nama', 'like', "%{$v}%"))))
            ->when($f['kategori'] ?? null, fn ($q, $v) => $q->where('layanan.kategori_id', $v))
            ->when($f['kanal'] ?? null, fn ($q, $v) => $q->where('layanan.kanal_id', $v))
            ->when($f['jenis'] ?? null, fn ($q, $v) => $q->where('layanan.jenis', $v))
            ->when($f['divisi'] ?? null, fn ($q, $v) => $q->where('layanan.divisi_tujuan_id', $v))
            ->when($f['dari'] ?? null, fn ($q, $v) => $q->whereDate('layanan.diterima_pada', '>=', $v))
            ->when($f['sampai'] ?? null, fn ($q, $v) => $q->whereDate('layanan.diterima_pada', '<=', $v));
    }

    private function referensi(): array
    {
        return [
            'kanalList' => Kanal::where('aktif', true)->orderBy('id')->get(),
            'kategoriList' => Kategori::where('aktif', true)->orderBy('nama')->get(),
            'divisiList' => Divisi::where('aktif', true)->orderBy('nama')->get(),
            'statusList' => Status::orderBy('urutan')->get(),
            'picList' => Pengguna::where('peran', 'pic')->where('aktif', true)->orderBy('nama')->get(),
        ];
    }
}