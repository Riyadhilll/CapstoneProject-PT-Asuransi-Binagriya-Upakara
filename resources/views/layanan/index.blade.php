@extends('layouts.app', ['judul' => 'Daftar Layanan'])
@section('remah', 'Layanan / Daftar Layanan')
@section('isi')
@php
    $tabs = ['' => ['Semua', $jumlah->sum()]];
    foreach ($statusList as $s) { $tabs[$s->kode] = [$s->nama, $jumlah[$s->kode] ?? 0]; }
@endphp
<div class="flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="judul text-[22px]">Daftar Layanan Konsumen</h1>
        <p class="mt-1 text-xs text-slate-500">Seluruh layanan yang tercatat beserta status dan tenggatnya</p>
    </div>
    @if (auth()->user()->peran->value === 'petugas')
        <a href="{{ route('layanan.create') }}" class="btn-primer">+ Catat Layanan</a>
    @endif
</div>

<div class="kartu mt-5 overflow-hidden">
    @include('partials.tabs', ['tabs' => $tabs, 'param' => 'status', 'aktif' => request('status', '')])
    @include('partials.filter', ['tetap' => ['status']])
    <div class="relative overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-slate-50"><tr>
                <th class="th">No. Layanan</th><th class="th">Tgl Masuk</th><th class="th">Konsumen</th><th class="th">Jenis</th><th class="th">Kanal</th>
                <th class="th">Divisi Tujuan</th><th class="th">PIC</th><th class="th">Status</th><th class="th">Tenggat</th><th class="th">Aksi</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse ($layanan as $l)
                <tr class="hover:bg-slate-50">
                    <td class="td whitespace-nowrap"><a href="{{ route('layanan.show', $l) }}" class="tautan">{{ $l->nomor }}</a></td>
                    <td class="td whitespace-nowrap">{{ $l->diterima_pada->format('d-m-Y') }}</td>
                    <td class="td min-w-28 whitespace-normal">{{ $l->konsumen->nama }}</td>
                    <td class="td">{{ ucfirst($l->jenis) }}</td>
                    <td class="td min-w-24 whitespace-normal"><span class="pil-abu">{{ $l->kanal->nama }}</span></td>
                    <td class="td min-w-28 whitespace-normal">{{ $l->divisiTujuan?->nama ?? '–' }}</td>
                    <td class="td min-w-24 whitespace-normal">{!! $l->pic ? e($l->pic->nama) : '<span class="text-slate-500">Dijawab langsung</span>' !!}</td>
                    <td class="td"><x-status :kode="$l->status_kode" :nama="$l->status->nama" /></td>
                    <td class="td"><x-penanda :penanda="$l->penanda" :sisa="$l->sisa_hk" :tepat="$l->tepatWaktu()" /></td>
                    <td class="td"><a href="{{ route('layanan.show', $l) }}" class="btn-garis px-3 py-1.5 text-xs">Detail<span class="sr-only"> {{ $l->nomor }}</span></a></td>
                </tr>
            @empty
                <tr><td colspan="10">
                    @if (request()->hasAny(['q', 'dari', 'sampai', 'kategori', 'jenis', 'kanal', 'divisi', 'status']))
                        <x-kosong />
                    @else
                        <x-kosong judul="Belum ada layanan tercatat" />
                    @endif
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($layanan->total())
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-4 py-3">
            <p class="text-xs text-slate-500">Menampilkan {{ $layanan->firstItem() }}–{{ $layanan->lastItem() }} dari {{ $layanan->total() }} layanan</p>
            {{ $layanan->onEachSide(1)->links('partials.halaman') }}
        </div>
    @endif
</div>
<x-keterangan-tenggat />
@endsection