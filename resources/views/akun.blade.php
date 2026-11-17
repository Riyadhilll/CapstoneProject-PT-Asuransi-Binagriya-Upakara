@extends('layouts.app', ['judul' => 'Kelola Akun'])
@section('remah', 'Pengaturan / Kelola Akun')
@section('isi')
@php
    $pilih = 'max-w-44 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-700 focus:border-primer focus:ring-2 focus:ring-primer/20 focus:outline-none';
    $inisial = fn ($nama) => collect(explode(' ', $nama))->take(2)->map(fn ($k) => mb_substr($k, 0, 1))->join('');
    $baru = ! $ubah;
    $nilai = fn ($kolom, $bawaan = null) => old($kolom, $ubah?->$kolom ?? $bawaan);
@endphp
<div class="flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="judul text-[22px]">Kelola Akun Pengguna</h1>
        <p class="mt-1 text-xs text-slate-500">Tambah, ubah, dan nonaktifkan akun serta atur peran dan divisi pengguna</p>
    </div>
    <a href="{{ route('akun.index', request()->except('ubah', 'page')) }}#form-akun" class="btn-primer">+ Tambah Akun</a>
</div>

<div class="mt-5 grid items-start gap-4 xl:grid-cols-[1fr_380px]">
    <div class="kartu min-w-0 overflow-hidden">
        <form method="get" class="flex flex-wrap items-center gap-3 px-4 py-4">
            <label class="relative min-w-52 flex-1 sm:max-w-60">
                <span class="sr-only">Cari nama atau username</span>
                <svg class="pointer-events-none absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-slate-400" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="9" cy="9" r="6"/><path d="m14 14 4 4" stroke-linecap="round"/></svg>
                <input name="q" value="{{ request('q') }}" placeholder="Cari nama atau username" class="w-full rounded-lg border border-slate-300 py-2 pr-3 pl-8 text-xs focus:border-primer focus:ring-2 focus:ring-primer/20 focus:outline-none">
            </label>
            <label class="sr-only" for="a-peran">Peran</label>
            <select id="a-peran" name="peran" class="{{ $pilih }}" onchange="this.form.requestSubmit()"><option value="">Peran: Semua</option>
                @foreach (\App\Enums\Peran::cases() as $p)<option value="{{ $p->value }}" @selected(request('peran') === $p->value)>Peran: {{ $p->label() }}</option>@endforeach
            </select>
            <label class="sr-only" for="a-status">Status</label>
            <select id="a-status" name="status" class="{{ $pilih }}" onchange="this.form.requestSubmit()">
                <option value="">Status: Semua</option>
                <option value="aktif" @selected(request('status') === 'aktif')>Status: Aktif</option>
                <option value="nonaktif" @selected(request('status') === 'nonaktif')>Status: Nonaktif</option>
            </select>
            <span class="ml-auto text-xs text-slate-500">{{ $akun->total() }} akun</span>
        </form>
        <div class="relative overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50"><tr><th class="th">Nama</th><th class="th">Peran</th><th class="th">Divisi</th><th class="th">Status</th><th class="th">Aksi</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($akun as $a)
                    @php $dipilih = $ubah?->id === $a->id; @endphp
                    <tr @class(['relative', 'bg-primer-muda/40' => $dipilih, 'hover:bg-slate-50' => ! $dipilih])>
                        <td class="td">
                            @if ($dipilih)<span class="absolute inset-y-0 left-0 w-[3px] bg-primer" aria-hidden="true"></span>@endif
                            <span class="flex items-center gap-3">
                                <span @class(['grid size-8 shrink-0 place-items-center rounded-full text-[10px] font-bold', 'bg-primer-muda text-primer' => $a->aktif, 'bg-slate-100 text-slate-400' => ! $a->aktif]) aria-hidden="true">{{ $inisial($a->nama) }}</span>
                                <span><span @class(['block font-semibold', 'text-slate-900' => $a->aktif, 'text-slate-500' => ! $a->aktif])>{{ $a->nama }}</span><span class="block text-[11px] text-slate-500">{{ $a->username }}</span></span>
                            </span>
                        </td>
                        <td class="td"><span class="pil-abu font-semibold text-slate-700">{{ $a->peran->label() }}</span></td>
                        <td @class(['td', 'text-slate-500' => ! $a->aktif])>{{ $a->divisi?->nama ?? ($a->peran->value === 'pimpinan' ? 'Manajemen' : '–') }}</td>
                        <td class="td"><x-aktif :aktif="$a->aktif" /></td>
                        <td class="td"><a href="{{ request()->fullUrlWithQuery(['ubah' => $a->id]) }}#form-akun" class="tautan">Ubah<span class="sr-only"> {{ $a->nama }}</span></a></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-kosong judul="Tidak ada akun yang cocok" /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($akun->total())
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-4 py-3">
                <p class="text-xs text-slate-500">Menampilkan {{ $akun->firstItem() }}–{{ $akun->lastItem() }} dari {{ $akun->total() }} akun</p>
                {{ $akun->onEachSide(1)->links('partials.halaman') }}
            </div>
        @endif
    </div>

    <form id="form-akun" method="post" action="{{ $baru ? route('akun.store') : route('akun.update', $ubah) }}" class="kartu scroll-mt-6 border-primer/60 shadow-[0_0_0_1px_color-mix(in_srgb,var(--color-primer)_25%,transparent)]">
        @csrf
        @unless ($baru) @method('PUT') @endunless
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="judul text-[15px]">{{ $baru ? 'Tambah Akun' : 'Ubah Akun' }}</h2>
            <p class="mt-0.5 text-[11px] text-slate-500">{{ $baru ? 'Akun baru langsung aktif dan dapat login' : $ubah->nama.' · dibuat '.$ubah->dibuat_pada->format('d-m-Y').($pembuat ? ' oleh '.$pembuat : '') }}</p>
        </div>
        <div class="space-y-4 px-5 py-4">
            <div><label for="nama" class="label">Nama lengkap *</label><input id="nama" name="nama" value="{{ $nilai('nama') }}" required maxlength="150" class="input"></div>
            <div><label for="username" class="label">Username *</label><input id="username" name="username" value="{{ $nilai('username') }}" required maxlength="50" pattern="[A-Za-z0-9._\-]+" class="input"></div>
            <div><label for="email-akun" class="label">Email *</label><input id="email-akun" type="email" name="email" value="{{ $nilai('email') }}" required maxlength="150" class="input"></div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="peran" class="label">Peran *</label>
                    <select id="peran" name="peran" required class="input">
                        @foreach (\App\Enums\Peran::cases() as $p)<option value="{{ $p->value }}" @selected(old('peran', $ubah?->peran->value ?? 'petugas') === $p->value)>{{ $p->label() }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label for="divisi" class="label">Divisi <span id="wajib-divisi">*</span></label>
                    <select id="divisi" name="divisi_id" class="input"><option value="">Tanpa divisi</option>
                        @foreach ($divisiList as $d)<option value="{{ $d->id }}" @selected((string) $nilai('divisi_id') === (string) $d->id)>{{ $d->nama }}</option>@endforeach
                    </select>
                </div>
            </div>
            <p class="-mt-2 text-[11px] text-slate-500">Divisi wajib diisi untuk peran PIC agar layanan dapat ditugaskan.</p>

            @unless ($baru)
                <div>
                    <p class="label">Status akun</p>
                    <x-saklar name="aktif" :checked="(bool) old('aktif', $ubah->aktif)" label="Aktif, dapat login ke aplikasi" />
                    @if ($tugasAktif)<p class="mt-2 text-[11px] text-amber-700">Memegang {{ $tugasAktif }} layanan aktif: tidak dapat dinonaktifkan atau dipindah sebelum tugasnya dialihkan.</p>@endif
                </div>
            @endunless
            <p class="rounded-md border border-primer/30 bg-primer-muda/50 px-3 py-2.5 text-[11px] text-primer-tua">Akun tidak dihapus, cukup dinonaktifkan, agar riwayat layanan dan penugasan tetap utuh.</p>

            <div>
                <p class="label">Kata sandi{{ $baru ? ' *' : '' }}</p>
                @if ($baru)
                    <input type="password" name="password" required minlength="8" autocomplete="new-password" placeholder="Minimal 8 karakter" class="input" aria-label="Kata sandi awal">
                @else
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" id="tombol-sandi" class="btn-garis text-xs" onclick="this.hidden = true; const i = document.getElementById('sandi-baru'); i.hidden = false; i.disabled = false; i.focus();">Atur ulang kata sandi</button>
                        <input type="password" id="sandi-baru" name="password" minlength="8" autocomplete="new-password" placeholder="Kata sandi baru, minimal 8 karakter" class="input" hidden disabled aria-label="Kata sandi baru">
                        <span class="text-[11px] text-slate-500">Disimpan dalam bentuk hash</span>
                    </div>
                @endif
            </div>
        </div>
        <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-4">
            <a href="{{ route('akun.index', request()->except('ubah')) }}" class="btn-garis">Batal</a>
            <button class="btn-primer">{{ $baru ? 'Simpan Akun' : 'Simpan Perubahan' }}</button>
        </div>
    </form>
</div>
@push('script')
<script>
    const peran = document.getElementById('peran'), divisiAkun = document.getElementById('divisi');
    const aturDivisi = () => { const pic = peran.value === 'pic'; divisiAkun.required = pic; document.getElementById('wajib-divisi').hidden = !pic; };
    peran.addEventListener('change', aturDivisi);
    aturDivisi();
</script>
@endpush
@endsection