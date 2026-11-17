<?php

namespace App\Http\Controllers;

use App\Enums\Peran;
use App\Models\Divisi;
use App\Models\Layanan;
use App\Models\Pengguna;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AkunController extends Controller
{
    public function index(Request $request)
    {
        $f = $request->validate([
            'q' => 'nullable|string|max:100', 'peran' => ['nullable', Rule::enum(Peran::class)],
            'status' => 'nullable|in:aktif,nonaktif', 'ubah' => 'nullable|integer',
        ]);
        $ubah = isset($f['ubah']) ? Pengguna::find($f['ubah']) : null;

        return view('akun', [
            'akun' => Pengguna::with('divisi')
                ->when($f['q'] ?? null, fn ($q, $v) => $q->where(fn ($q) => $q->where('nama', 'like', "%{$v}%")->orWhere('username', 'like', "%{$v}%")))
                ->when($f['peran'] ?? null, fn ($q, $v) => $q->where('peran', $v))
                ->when($f['status'] ?? null, fn ($q, $v) => $q->where('aktif', $v === 'aktif'))
                ->orderByDesc('aktif')->orderBy('nama')
                ->paginate(9)->withQueryString(),
            'ubah' => $ubah,
            'pembuat' => $ubah?->dibuat_oleh ? Pengguna::find($ubah->dibuat_oleh)?->nama : null,
            'tugasAktif' => $ubah ? $this->tugasAktif($ubah) : 0,
            'divisiList' => Divisi::where('aktif', true)->orderBy('nama')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->data($request);
        $akun = new Pengguna($data);
        $akun->dibuat_oleh = $request->user()->id;
        $akun->save();
        Audit::jalankanEtl();

        return redirect()->route('akun.index', ['ubah' => $akun->id])->with('pesan', "Akun {$akun->username} dibuat.");
    }

    public function update(Request $request, Pengguna $pengguna)
    {
        $data = $this->data($request, $pengguna);

        if ($pengguna->is($request->user()) && (! $data['aktif'] || $data['peran'] !== Peran::Admin->value)) {
            return back()->withInput()->withErrors(['aktif' => 'Anda tidak dapat menonaktifkan atau mengubah peran akun sendiri.']);
        }
        $tugas = $this->tugasAktif($pengguna);
        if ($tugas && (! $data['aktif'] || $data['peran'] !== Peran::Pic->value || (int) ($data['divisi_id'] ?? 0) !== (int) $pengguna->divisi_id)) {
            return back()->withInput()->withErrors(['aktif' => "PIC ini masih memegang {$tugas} layanan aktif. Minta PIC mengalihkan tugasnya terlebih dahulu."]);
        }

        $pengguna->update($data);
        if (isset($data['password_hash'])) {
            $pengguna->forceFill(['remember_token' => Str::random(60)])->save();
            DB::table('sessions')->where('user_id', $pengguna->id)->where('id', '!=', $request->session()->getId())->delete();
        }
        Audit::jalankanEtl();

        return redirect()->route('akun.index', ['ubah' => $pengguna->id])->with('pesan', "Akun {$pengguna->username} diperbarui.");
    }

    private function data(Request $request, ?Pengguna $akun = null): array
    {
        $data = $request->validate([
            'nama' => 'required|string|max:150',
            'username' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('pengguna', 'username')->ignore($akun)],
            'email' => ['required', 'email', 'max:150', Rule::unique('pengguna', 'email')->ignore($akun)],
            'peran' => ['required', Rule::enum(Peran::class)],
            'divisi_id' => 'nullable|required_if:peran,pic|exists:divisi,id',
            'aktif' => 'nullable|boolean',
            'password' => [$akun ? 'nullable' : 'required', 'string', 'min:8'],
        ]);
        $data['aktif'] = $akun ? $request->boolean('aktif') : true;
        if (filled($data['password'] ?? null)) {
            $data['password_hash'] = $data['password'];
        }
        unset($data['password']);

        return $data;
    }

    private function tugasAktif(Pengguna $akun): int
    {
        return Layanan::where('pic_id', $akun->id)->where('status_kode', '!=', 'SELESAI')->count();
    }
}