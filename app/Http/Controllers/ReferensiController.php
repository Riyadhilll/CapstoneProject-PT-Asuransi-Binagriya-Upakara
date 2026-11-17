<?php

namespace App\Http\Controllers;

use App\Models\Divisi;
use App\Models\HariLibur;
use App\Models\Kanal;
use App\Models\Kategori;
use App\Models\Status;
use App\Support\Audit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReferensiController extends Controller
{
    private const TAB = [
        'divisi' => [Divisi::class, 'divisi_tujuan_id', 'Divisi'],
        'kategori' => [Kategori::class, 'kategori_id', 'Kategori'],
        'kanal' => [Kanal::class, 'kanal_id', 'Kanal'],
        'status' => [Status::class, 'status_kode', 'Status'],
        'libur' => [HariLibur::class, null, 'Hari Libur'],
    ];

    public function index(Request $request)
    {
        $v = $request->validate(['tab' => 'nullable|in:'.implode(',', array_keys(self::TAB)), 'q' => 'nullable|string|max:100', 'ubah' => 'nullable|string|max:20']);
        $tab = $v['tab'] ?? 'kategori';
        [$model, $fk] = self::TAB[$tab];
        $contoh = new $model;
        $tabel = $contoh->getTable();

        $baris = $model::query()
            ->when($fk, fn ($q) => $q->select("{$tabel}.*")->selectSub(
                DB::table('layanan')->selectRaw('count(*)')->whereColumn("layanan.{$fk}", "{$tabel}.{$contoh->getKeyName()}"),
                'jumlah_layanan'
            ))
            ->when($v['q'] ?? null, fn ($q, $s) => $q->where(fn ($q) => $q->where('nama', 'like', "%{$s}%")
                ->when($tab !== 'libur', fn ($q) => $q->orWhere('kode', 'like', "%{$s}%"))))
            ->when($tab === 'kategori', fn ($q) => $q->with('divisiUsulan'))
            ->tap(fn ($q) => match ($tab) {
                'status' => $q->orderBy('urutan'),
                'libur' => $q->orderBy('tanggal'),
                default => $q->orderByDesc('aktif')->orderByDesc('jumlah_layanan')->orderBy('nama'),
            })
            ->get();

        return view('referensi', [
            'tab' => $tab,
            'label' => self::TAB[$tab][2],
            'baris' => $baris,
            'jumlah' => array_map(fn ($t) => $t[0]::count(), self::TAB),
            'ubah' => isset($v['ubah']) ? $model::find($v['ubah']) : null,
            'divisiList' => Divisi::where('aktif', true)->orderBy('nama')->get(),
            'liburTerdekat' => HariLibur::where('tanggal', '>=', today())->orderBy('tanggal')->limit(3)->get(),
            'liburTahunIni' => HariLibur::whereYear('tanggal', now()->year)->count(),
        ]);
    }

    public function simpan(Request $request, string $jenis, ?string $id = null)
    {
        abort_unless(isset(self::TAB[$jenis]) && ($id !== null || $jenis !== 'status'), 404);
        $model = self::TAB[$jenis][0];
        $item = $id === null ? new $model : $model::findOrFail($id);

        $data = $request->validate($this->aturan($jenis, $item));
        if (isset($data['kode'])) {
            $data['kode'] = strtoupper($data['kode']);
        }
        if (! in_array($jenis, ['status', 'libur'])) {
            $data['aktif'] = $id === null || $request->boolean('aktif');
        }

        $this->simpanLalu($jenis, fn () => $item->fill($data)->save());

        return redirect()->route('referensi.index', ['tab' => $jenis])->with('pesan', self::TAB[$jenis][2].' tersimpan.');
    }

    public function hapusLibur(string $tanggal)
    {
        $libur = HariLibur::findOrFail($tanggal);
        $this->simpanLalu('libur', fn () => $libur->delete());

        return redirect()->route('referensi.index', ['tab' => 'libur'])->with('pesan', 'Hari libur dihapus; tenggat layanan aktif dihitung ulang.');
    }

    private function simpanLalu(string $jenis, callable $ubah): void
    {
        DB::transaction(function () use ($jenis, $ubah) {
            $ubah();
            if ($jenis === 'libur') {
                DB::statement('CALL hitung_ulang_tenggat()');
            }
        });
        Audit::jalankanEtl();
    }

    private function aturan(string $jenis, Model $item): array
    {
        $unik = fn (string $kolom) => Rule::unique($item->getTable(), $kolom)->ignore($item->getKey(), $item->getKeyName());

        return match ($jenis) {
            'status' => [
                'nama' => ['required', 'string', 'max:50', $unik('nama')],
                'urutan' => ['required', 'integer', 'min:1', 'max:255', $unik('urutan')],
            ],
            'libur' => [
                'tanggal' => ['required', 'date', 'after_or_equal:2020-01-01', 'before_or_equal:2035-12-31', $unik('tanggal')],
                'nama' => 'required|string|max:150',
            ],
            default => [
                'kode' => ['required', 'string', 'max:10', 'alpha_dash:ascii', $unik('kode')],
                'nama' => ['required', 'string', 'max:100', $unik('nama')],
                ...($jenis === 'kategori' ? ['divisi_usulan_id' => 'required|exists:divisi,id'] : []),
            ],
        };
    }
}