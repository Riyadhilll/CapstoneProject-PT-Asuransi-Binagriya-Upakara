@extends('layouts.app', ['judul' => 'Log ETL'])
@section('remah', 'Data Mart / Log ETL')
@section('isi')
@php
    $waktu = fn ($d, $f = 'd-m-Y H:i:s') => $d ? date($f, strtotime($d)) : '–';
    $pilih = 'rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-700 focus:border-primer focus:ring-2 focus:ring-primer/20 focus:outline-none';
    $cocok = $terakhir && $terakhir->jumlah_app === $terakhir->jumlah_mart;
@endphp
<div class="flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="judul text-[22px]">Log Proses ETL Data Mart</h1>
        <p class="mt-1 text-xs text-slate-500">Pemantauan proses ETL dari basis data operasional ke data mart</p>
    </div>
</div>

<form method="get" class="kartu mt-5 flex flex-wrap items-center gap-3 px-5 py-3.5">
    <span class="text-xs font-semibold text-slate-700">Filter</span>
    <label class="sr-only" for="e-tanggal">Tanggal</label>
    <input id="e-tanggal" type="date" name="tanggal" value="{{ request('tanggal') }}" class="{{ $pilih }}">
    <label class="sr-only" for="e-mode">Mode</label>
    <select id="e-mode" name="mode" class="{{ $pilih }}">
        <option value="">Mode: Semua</option>
        @foreach (['inkremental' => 'Inkremental', 'penuh' => 'Penuh'] as $v => $n)<option value="{{ $v }}" @selected(request('mode') === $v)>Mode: {{ $n }}</option>@endforeach
    </select>
    <button class="btn-primer px-3 py-2 text-xs">Terapkan</button>
    <span class="ml-auto inline-flex items-center gap-1.5 text-[11px] text-slate-500"><span class="size-2 rounded-full bg-emerald-500"></span>Data mart diperbarui {{ $waktu($terakhir?->selesai, 'd-m-Y H:i:s') }}</span>
</form>

<div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="kartu p-4">
        <p class="text-xs font-semibold text-slate-900">ETL penuh terakhir</p>
        <p class="mt-3 text-[28px] leading-none font-bold text-slate-900">{{ $waktu($penuh?->mulai, 'H:i') }}<span class="ml-2 text-xs font-normal text-slate-500">{{ $waktu($penuh?->mulai, 'd-m-Y') }}</span></p>
        <p class="mt-3 text-[11px] text-slate-500">{{ $penuh ? $penuh->baris.' layanan diproses dalam '.$penuh->durasi_ms.' ms' : 'Belum pernah dijalankan' }}</p>
    </div>
    <div class="kartu p-4">
        <p class="text-xs font-semibold text-slate-900">Rekonsiliasi terakhir</p>
        <p class="mt-3 text-[28px] leading-none font-bold text-slate-900">{{ $terakhir ? $terakhir->jumlah_app.' / '.$terakhir->jumlah_mart : '–' }}
            @if ($terakhir)<span @class(['ml-2 text-xs font-semibold', 'text-emerald-600' => $cocok, 'text-red-600' => ! $cocok])>{{ $cocok ? 'cocok' : 'selisih' }}</span>@endif
        </p>
        <p class="mt-3 text-[11px] text-slate-500">Jumlah layanan di aplikasi / data mart</p>
    </div>
    <div class="kartu p-4">
        <p class="text-xs font-semibold text-slate-900">Proses ETL hari ini</p>
        <p class="mt-3 text-[28px] leading-none font-bold text-slate-900">{{ (int) $hariIni->n }}<span class="ml-2 text-xs font-normal text-slate-500">proses</span></p>
        <p class="mt-3 text-[11px] text-slate-500">{{ (int) $hariIni->inkremental }} inkremental · {{ (int) $hariIni->penuh }} penuh</p>
    </div>
    <div class="kartu p-4">
        <p class="text-xs font-semibold text-slate-900">Proses gagal</p>
        <p @class(['mt-3 text-[28px] leading-none font-bold', 'text-emerald-600' => ! $gagal, 'text-red-600' => $gagal])>{{ $gagal }}<span class="ml-2 text-xs font-normal text-slate-500">proses</span></p>
        <p class="mt-3 text-[11px] text-slate-500">Dalam 7 hari terakhir</p>
    </div>
</div>

<section class="kartu mt-4 overflow-hidden">
    <div class="px-5 pt-5 pb-3">
        <h2 class="judul text-[15px]">Riwayat Proses ETL</h2>
        <p class="mt-0.5 text-[11px] text-slate-500">Setiap penyimpanan layanan memicu ETL inkremental; ETL penuh berjalan setiap hari pukul 01.00 untuk rekonsiliasi</p>
    </div>
    <div class="relative overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-slate-50"><tr><th class="th">Waktu Mulai</th><th class="th">Mode</th><th class="th">Layanan</th><th class="th text-right">Baris</th><th class="th text-right">Durasi</th><th class="th text-center">Aplikasi / Data Mart</th><th class="th">Hasil</th></tr></thead>
            <tbody class="divide-y divide-slate-100 tabular-nums">
            @forelse ($log as $l)
                @php $sama = $l->jumlah_app === $l->jumlah_mart; @endphp
                <tr>
                    <td class="td whitespace-nowrap">{{ $waktu($l->mulai) }}</td>
                    <td @class(['td', 'font-semibold text-slate-900' => $l->mode === 'penuh'])>{{ ucfirst($l->mode) }}</td>
                    <td class="td whitespace-nowrap">@if ($l->nomor)<a href="{{ route('layanan.show', $l->layanan_id) }}" class="tautan font-normal">{{ $l->nomor }}</a>@else Semua layanan @endif</td>
                    <td class="td text-right">{{ $l->baris ?? '–' }}</td>
                    <td class="td text-right">{{ $l->durasi_ms === null ? '–' : $l->durasi_ms.' ms' }}</td>
                    <td class="td text-center"><span class="inline-block min-w-24 rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700">{{ $l->jumlah_app ?? '–' }} / {{ $l->jumlah_mart ?? '–' }}</span></td>
                    <td class="td">
                        @if ($l->hasil === 'berhasil')
                            <span @class(['inline-flex items-center gap-1.5 text-[11px] font-semibold', 'text-emerald-600' => $sama, 'text-amber-600' => ! $sama])><span class="size-2 rounded-full bg-current"></span>Berhasil · {{ $sama ? 'cocok' : 'selisih' }}</span>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-red-600"><span class="size-2 rounded-full bg-current"></span>Gagal</span>
                            <p class="max-w-xs truncate text-[11px] text-slate-500" title="{{ $l->pesan }}">{{ $l->pesan }}</p>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7"><x-kosong judul="Belum ada proses ETL" /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection