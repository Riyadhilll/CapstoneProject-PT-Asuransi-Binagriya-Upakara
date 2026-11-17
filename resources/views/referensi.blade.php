@extends('layouts.app', ['judul' => 'Data Referensi'])
@section('remah', 'Pengaturan / Data Referensi')
@section('isi')
@php
    $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $tgl = fn ($d) => date('d-m-Y', strtotime($d));
    $namaHari = fn ($d) => $hari[(int) date('w', strtotime($d))];
    $punyaAktif = ! in_array($tab, ['status', 'libur']);
    $bisaTambah = $tab !== 'status';
    $kunci = $ubah?->getKey();
    $nilai = fn ($kolom) => old($kolom, $ubah?->$kolom);
    $judulForm = $ubah ? 'Ubah '.$label : ($bisaTambah ? 'Tambah '.$label : null);
@endphp
<h1 class="judul text-[22px]">Data Referensi</h1>
<p class="mt-1 text-xs text-slate-500">Pilihan seragam yang dipakai pada formulir layanan, perhitungan tenggat, dan dashboard</p>

<div class="mt-5 grid items-start gap-4 xl:grid-cols-[1fr_380px]">
    <div class="kartu min-w-0 overflow-hidden">
        @include('partials.tabs', ['param' => 'tab', 'aktif' => $tab, 'tabs' => [
            'divisi' => ['Divisi', $jumlah['divisi']], 'kategori' => ['Kategori', $jumlah['kategori']], 'kanal' => ['Kanal', $jumlah['kanal']],
            'status' => ['Status', $jumlah['status']], 'libur' => ['Hari Libur', $jumlah['libur']],
        ]])
        <form method="get" class="flex flex-wrap items-center gap-3 px-4 py-4">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <label class="relative min-w-52 flex-1 sm:max-w-60">
                <span class="sr-only">Cari {{ strtolower($label) }}</span>
                <svg class="pointer-events-none absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-slate-400" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="9" cy="9" r="6"/><path d="m14 14 4 4" stroke-linecap="round"/></svg>
                <input name="q" value="{{ request('q') }}" placeholder="Cari {{ strtolower($label) }}" class="w-full rounded-lg border border-slate-300 py-2 pr-3 pl-8 text-xs focus:border-primer focus:ring-2 focus:ring-primer/20 focus:outline-none">
            </label>
            <span class="ml-auto text-xs text-slate-500">Urut: {{ match ($tab) { 'status' => 'Urutan tahap', 'libur' => 'Tanggal', default => 'Jumlah layanan' } }}</span>
        </form>
        <div class="relative overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50"><tr>
                    @if ($tab === 'libur')
                        <th class="th">Tanggal</th><th class="th">Hari</th><th class="th">Nama Hari Libur</th><th class="th">Aksi</th>
                    @else
                        <th class="th">Kode</th><th class="th">Nama {{ $label }}</th>
                        @if ($tab === 'kategori')<th class="th">Divisi Usulan</th>@endif
                        @if ($tab === 'status')<th class="th">Urutan</th>@endif
                        <th class="th">Layanan</th>
                        @if ($punyaAktif)<th class="th">Status</th>@endif
                        <th class="th">Aksi</th>
                    @endif
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($baris as $b)
                    @php $redup = $punyaAktif && ! $b->aktif; $dipilih = $kunci !== null && (string) $kunci === (string) $b->getKey(); @endphp
                    <tr @class(['bg-primer-muda/40' => $dipilih, 'hover:bg-slate-50' => ! $dipilih])>
                        @if ($tab === 'libur')
                            <td @class(['td font-semibold text-slate-900', 'text-slate-400' => $b->tanggal < today()->toDateString()])>{{ $tgl($b->tanggal) }}</td>
                            <td class="td">{{ $namaHari($b->tanggal) }}</td>
                            <td class="td">{{ $b->nama }}</td>
                            <td class="td">
                                <span class="flex items-center gap-4">
                                    <a href="{{ request()->fullUrlWithQuery(['ubah' => $b->tanggal]) }}" class="tautan">Ubah</a>
                                    <form method="post" action="{{ route('referensi.libur.hapus', $b->tanggal) }}" onsubmit="return confirm('Hapus hari libur {{ $tgl($b->tanggal) }}? Tenggat layanan aktif akan dihitung ulang.')">
                                        @csrf @method('DELETE')
                                        <button class="text-[13px] font-semibold text-red-600 hover:underline">Hapus</button>
                                    </form>
                                </span>
                            </td>
                        @else
                            <td class="td"><span @class(['inline-flex rounded px-1.5 py-0.5 text-[10px] font-bold', 'bg-primer-muda text-primer-tua' => ! $redup, 'bg-slate-100 text-slate-500' => $redup])>{{ $b->kode }}</span></td>
                            <td @class(['td font-semibold', 'text-slate-900' => ! $redup, 'text-slate-500' => $redup])>{{ $b->nama }}</td>
                            @if ($tab === 'kategori')<td @class(['td', 'text-slate-500' => $redup])>{{ $b->divisiUsulan?->nama ?? '–' }}</td>@endif
                            @if ($tab === 'status')<td class="td">{{ $b->urutan }}</td>@endif
                            <td class="td tabular-nums">{{ $b->jumlah_layanan }}</td>
                            @if ($punyaAktif)<td class="td"><x-aktif :aktif="$b->aktif" /></td>@endif
                            <td class="td"><a href="{{ request()->fullUrlWithQuery(['ubah' => $b->getKey()]) }}" class="tautan">Ubah<span class="sr-only"> {{ $b->nama }}</span></a></td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="7"><x-kosong :judul="'Belum ada data '.strtolower($label)" /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <p class="m-4 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-[11px] text-slate-600">
            @switch($tab)
                @case('status') Kode status (MENUNGGU, PROSES, SELESAI) dikunci karena dipakai aturan tenggat; hanya nama tampilan dan urutan yang dapat diubah. @break
                @case('libur') Menambah, mengubah, atau menghapus hari libur langsung menghitung ulang tenggat semua layanan yang belum selesai. Data otomatis diambil setiap Senin dari api-hari-libur.pages.dev (SKB 3 Menteri); tanggal yang sudah ada tidak ditimpa. Cocokkan dengan SKB resmi bila ada revisi. @break
                @default {{ $label }} yang sudah dipakai pada layanan tidak dapat dihapus, hanya dinonaktifkan, agar data aplikasi dan data mart tetap konsisten.
            @endswitch
        </p>
    </div>

    <div class="space-y-4">
        @if ($judulForm)
        <form method="post" action="{{ $ubah ? route('referensi.update', [$tab, $kunci]) : route('referensi.store', $tab) }}" class="kartu p-5">
            @csrf
            @if ($ubah) @method('PUT') @endif
            <h2 class="judul text-[15px]">{{ $judulForm }}</h2>
            <p class="mt-0.5 text-[11px] text-slate-500">{{ $tab === 'libur' ? 'Dikecualikan dari perhitungan tenggat hari kerja' : 'Langsung tersedia di formulir Catat Layanan' }}</p>
            <div class="mt-4 space-y-4">
                @if ($tab === 'libur')
                    <div><label for="tanggal" class="label">Tanggal *</label><input id="tanggal" type="date" name="tanggal" value="{{ $nilai('tanggal') }}" min="2020-01-01" max="2035-12-31" required class="input"></div>
                    <div><label for="nama-ref" class="label">Nama hari libur *</label><input id="nama-ref" name="nama" value="{{ $nilai('nama') }}" required maxlength="150" placeholder="mis. Hari Raya Natal" class="input"></div>
                @elseif ($tab === 'status')
                    <div><p class="label">Kode</p><p class="input bg-slate-100 text-slate-500">{{ $ubah->kode }}</p></div>
                    <div class="grid grid-cols-[1fr_96px] gap-3">
                        <div><label for="nama-ref" class="label">Nama tampilan *</label><input id="nama-ref" name="nama" value="{{ $nilai('nama') }}" required maxlength="50" class="input"></div>
                        <div><label for="urutan" class="label">Urutan *</label><input id="urutan" type="number" name="urutan" value="{{ $nilai('urutan') }}" min="1" max="255" required class="input"></div>
                    </div>
                @else
                    <div class="grid grid-cols-[96px_1fr] gap-3">
                        <div><label for="kode" class="label">Kode *</label><input id="kode" name="kode" value="{{ $nilai('kode') }}" required maxlength="10" placeholder="mis. KLM" class="input uppercase"></div>
                        <div><label for="nama-ref" class="label">Nama {{ strtolower($label) }} *</label><input id="nama-ref" name="nama" value="{{ $nilai('nama') }}" required maxlength="100" class="input"></div>
                    </div>
                    @if ($tab === 'kategori')
                        <div>
                            <label for="divisi-usulan" class="label">Divisi usulan *</label>
                            <select id="divisi-usulan" name="divisi_usulan_id" required class="input"><option value="">Pilih divisi</option>
                                @foreach ($divisiList as $d)<option value="{{ $d->id }}" @selected((string) $nilai('divisi_usulan_id') === (string) $d->id)>{{ $d->nama }}</option>@endforeach
                            </select>
                            <p class="mt-1.5 text-[11px] text-slate-500">Jadi usulan divisi tujuan saat kategori ini dipilih petugas.</p>
                        </div>
                    @endif
                    @if ($ubah)
                        <x-saklar name="aktif" :checked="(bool) old('aktif', $ubah->aktif)" label="Aktif, muncul sebagai pilihan di formulir" />
                    @endif
                @endif
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <a href="{{ route('referensi.index', ['tab' => $tab]) }}" class="btn-garis">Batal</a>
                <button class="btn-primer">Simpan {{ $label }}</button>
            </div>
        </form>
        @else
            <div class="kartu p-5 text-xs text-slate-500">Pilih <strong class="text-slate-700">Ubah</strong> pada salah satu status untuk mengganti nama tampilan atau urutannya.</div>
        @endif

        <section class="kartu p-5">
            <div class="flex items-center justify-between">
                <h2 class="judul text-[15px]">Hari Libur Terdekat</h2>
                @if ($tab !== 'libur')<a href="{{ route('referensi.index', ['tab' => 'libur']) }}" class="tautan text-xs">Kelola &rsaquo;</a>@endif
            </div>
            <p class="mt-0.5 text-[11px] text-slate-500">Dikecualikan dari perhitungan tenggat hari kerja</p>
            <ul class="mt-3 divide-y divide-slate-100 border-t border-slate-100 text-xs">
                @forelse ($liburTerdekat as $l)
                    <li class="grid grid-cols-[92px_1fr] py-3"><span class="font-semibold text-slate-900">{{ $tgl($l->tanggal) }}</span><span class="text-slate-700">{{ $l->nama }}</span></li>
                @empty
                    <li class="py-3 text-amber-700">Belum ada hari libur mendatang. Isi dari SKB libur nasional dan cuti bersama resmi.</li>
                @endforelse
            </ul>
            <p class="mt-2 text-[11px] text-slate-500">{{ $liburTahunIni }} hari libur tercatat untuk tahun {{ now()->year }}</p>
        </section>
    </div>
</div>
@endsection