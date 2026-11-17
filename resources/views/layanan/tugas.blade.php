@extends('layouts.app', ['judul' => 'Tugas Saya'])
@section('remah', 'Layanan / Tugas Saya')
@section('isi')
@php $metode = ['email' => 'Email', 'telepon' => 'Telepon', 'whatsapp' => 'WhatsApp', 'tatap_muka' => 'Tatap muka']; @endphp
<h1 class="judul text-[22px]">Tugas Saya</h1>
<p class="mt-1 text-xs text-slate-500">Layanan yang ditugaskan kepada Anda, diurutkan dari tenggat terdekat</p>

<div class="kartu mt-5 overflow-hidden">
    @include('partials.tabs', ['param' => 'tab', 'aktif' => $tab === 'aktif' ? '' : $tab, 'tabs' => [
        '' => ['Aktif', $jumlah['aktif']],
        'tenggat' => ['Mendekati / lewat tenggat', $jumlah['tenggat']],
        'selesai' => ['Selesai bulan ini', $jumlah['selesai']],
    ]])
    @include('partials.filter', ['tetap' => ['tab'], 'divisi' => false])
    <div class="relative overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-slate-50"><tr>
                <th class="th">No. Layanan</th><th class="th">Tgl Masuk</th><th class="th">Konsumen</th><th class="th">Kanal</th>
                <th class="th">Kategori · Jenis</th><th class="th">Uraian Singkat</th><th class="th">Status</th><th class="th">Tenggat</th><th class="th">Aksi</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse ($layanan as $l)
                <tr class="hover:bg-slate-50">
                    <td class="td whitespace-nowrap"><a href="{{ route('layanan.show', $l) }}" class="tautan">{{ $l->nomor }}</a></td>
                    <td class="td whitespace-nowrap">{{ $l->diterima_pada->format('d-m-Y') }}</td>
                    <td class="td whitespace-normal">{{ $l->konsumen->nama }}</td>
                    <td class="td"><span class="pil-abu">{{ $l->kanal->nama }}</span></td>
                    <td class="td min-w-32 whitespace-normal">{{ $l->kategori->nama }} · {{ ucfirst($l->jenis) }}</td>
                    <td class="td max-w-36 truncate" title="{{ $l->uraian }}">{{ $l->uraian }}</td>
                    <td class="td"><x-status :kode="$l->status_kode" :nama="$l->status->nama" /></td>
                    <td class="td"><x-penanda :penanda="$l->penanda" :sisa="$l->sisa_hk" :tepat="$l->tepatWaktu()" /></td>
                    <td class="td"><a href="{{ route('layanan.show', $l) }}" class="btn-garis px-3 py-1.5 text-xs">Detail<span class="sr-only"> {{ $l->nomor }}</span></a></td>
                </tr>
            @empty
                <tr><td colspan="9"><x-kosong judul="Tidak ada tugas di sini" /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($layanan->hasPages())
        <div class="flex justify-end border-t border-slate-200 px-4 py-3">{{ $layanan->onEachSide(1)->links('partials.halaman') }}</div>
    @endif
</div>

<section class="kartu mt-5 overflow-hidden">
    <div class="px-5 pt-5 pb-3">
        <h2 class="judul text-[15px]">Baru Saja Diselesaikan</h2>
        <p class="mt-0.5 text-[11px] text-slate-500">Layanan yang Anda selesaikan dalam 7 hari terakhir</p>
    </div>
    <div class="relative overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-slate-50"><tr>
                <th class="th">No. Layanan</th><th class="th">Konsumen</th><th class="th">Kategori · Jenis</th><th class="th">Selesai</th>
                <th class="th">Lama</th><th class="th">Metode</th><th class="th">Ketepatan</th><th class="th">Aksi</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse ($baruSelesai as $l)
                <tr>
                    <td class="td whitespace-nowrap"><a href="{{ route('layanan.show', $l) }}" class="tautan">{{ $l->nomor }}</a></td>
                    <td class="td">{{ $l->konsumen->nama }}</td>
                    <td class="td whitespace-nowrap">{{ $l->kategori->nama }} · {{ ucfirst($l->jenis) }}</td>
                    <td class="td whitespace-nowrap">{{ $l->selesai_pada->format('d-m-Y H:i') }}</td>
                    <td class="td whitespace-nowrap">{{ $l->lama_hk === null ? '–' : $l->lama_hk.' hari kerja' }}</td>
                    <td class="td">{{ $metode[$l->metode_penyampaian] ?? '–' }}</td>
                    <td class="td"><x-penanda :tepat="$l->tepatWaktu()" /></td>
                    <td class="td"><a href="{{ route('layanan.show', $l) }}" class="btn-garis px-3 py-1.5 text-xs">Detail<span class="sr-only"> {{ $l->nomor }}</span></a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="td py-8 text-center text-slate-500">Belum ada layanan yang Anda selesaikan minggu ini.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
<x-keterangan-tenggat />
@endsection