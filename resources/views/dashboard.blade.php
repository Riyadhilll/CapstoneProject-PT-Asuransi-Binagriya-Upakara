@extends('layouts.app', ['judul' => 'Dashboard'])
@section('remah', 'Beranda / Dashboard')
@section('isi')
@php
    $tgl = fn ($d) => $d ? date('d-m-Y', strtotime($d)) : '–';
    $angka = fn ($v, $des = 1) => $v === null ? '–' : number_format((float) $v, $des, ',', '.');
    $bar = fn ($nilai, $maks, $warna = 'bg-primer') => '<div class="h-2 w-full rounded-full bg-slate-100"><div class="h-2 rounded-full '.$warna.'" style="width:'.round(100 * (float) $nilai / max(1, (float) $maks)).'%"></div></div>';
    $belum = (int) $ringkas->menunggu + (int) $ringkas->proses;
    $komposisi = [['Selesai', (int) $ringkas->selesai, '#1BAF7A'], ['Dalam Proses', (int) $ringkas->proses, '#1E65FF'], ['Menunggu Penanganan', (int) $ringkas->menunggu, '#EDA100']];
    $stop = 0;
    $donut = collect($komposisi)->map(function ($k) use (&$stop, $ringkas) {
        $dari = $stop; $stop += 100 * $k[1] / max(1, $ringkas->masuk);
        return "{$k[2]} {$dari}% {$stop}%";
    })->join(', ');
    $totalJenis = max(1, $perJenis->sum());
@endphp
<h1 class="judul text-[22px]">Dashboard Pemantauan Layanan</h1>
<p class="mt-1 text-xs text-slate-500">Ringkasan kinerja layanan konsumen · diperbarui otomatis dari data mart</p>

<div class="kartu mt-5 overflow-hidden">
    @include('partials.filter', ['cari' => false])
</div>

<div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-[10px] bg-navy p-4 text-white">
        <p class="text-xs font-semibold text-navy-ink">Selesai sesuai tenggat</p>
        <p class="mt-3 text-[28px] leading-none font-bold">{{ $angka($ringkas->pct_tepat) }}<span class="ml-1 text-xs font-normal text-navy-ink">%</span></p>
        <p class="mt-3 text-[11px] text-navy-ink">{{ (int) $ringkas->jml_tepat }} dari {{ (int) $ringkas->jml_jatuh_tempo }} layanan selesai</p>
    </div>
    @foreach ([
        ['Belum selesai', $belum, 'layanan', (int) $ringkas->menunggu.' menunggu penanganan · '.(int) $ringkas->proses.' dalam proses'],
        ['Rata-rata lama penyelesaian', $angka($ringkas->rata_hk), 'hari kerja', 'Dihitung dari layanan diteruskan yang telah selesai'],
        ['Layanan diterima', (int) $ringkas->masuk, 'layanan', $angka($ringkas->pct_langsung).'% selesai saat diterima'],
    ] as [$label, $nilai, $satuan, $ket])
        <div class="kartu p-4">
            <p class="text-xs font-semibold text-slate-900">{{ $label }}</p>
            <p class="mt-3 text-[28px] leading-none font-bold text-slate-900">{{ $nilai }}<span class="ml-2 text-xs font-normal text-slate-500">{{ $satuan }}</span></p>
            <p class="mt-3 text-[11px] text-slate-500">{{ $ket }}</p>
        </div>
    @endforeach
</div>

<section class="kartu mt-4 overflow-hidden">
    <div class="flex flex-wrap items-start justify-between gap-3 px-5 pt-5 pb-3">
        <div>
            <h2 class="judul text-[15px]">Layanan yang Melewati atau Mendekati Tenggat</h2>
            <p class="mt-0.5 text-[11px] text-slate-500">Diurutkan dari yang paling lama lewat tenggat. Klik Detail untuk membuka layanan.</p>
            <p class="mt-1.5 inline-flex items-center gap-1.5 text-[11px] text-slate-500"><span class="size-2 rounded-full bg-emerald-500"></span>Data mart diperbarui {{ $diperbarui ? date('d-m-Y H:i:s', strtotime($diperbarui)) : 'belum pernah' }}</p>
        </div>
        <form method="get" role="search" class="w-full sm:w-64">
            @foreach (request()->only(['dari', 'sampai', 'kategori', 'divisi', 'kanal', 'jenis']) as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
            <label class="relative block">
                <span class="sr-only">Cari nomor layanan</span>
                <svg class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="9" cy="9" r="6"/><path d="m14 14 4 4" stroke-linecap="round"/></svg>
                <input name="q" value="{{ request('q') }}" placeholder="Cari nomor layanan" class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2 pr-3 pl-9 text-xs placeholder:text-slate-500 focus:border-primer focus:ring-2 focus:ring-primer/20 focus:outline-none">
            </label>
        </form>
    </div>
    <div class="relative overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-slate-50"><tr><th class="th">No. Layanan</th><th class="th">Tgl Masuk</th><th class="th">Jenis</th><th class="th">Unit Terkait</th><th class="th">PIC</th><th class="th">Status</th><th class="th">Tenggat</th><th class="th">Aksi</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse ($tenggat as $t)
                <tr>
                    <td class="td whitespace-nowrap"><a href="{{ route('layanan.show', $t->layanan_id) }}" class="tautan">{{ $t->nomor }}</a></td>
                    <td class="td whitespace-nowrap">{{ $tgl($t->tgl_diterima) }}</td>
                    <td class="td">{{ ucfirst($t->jenis) }}</td>
                    <td class="td">{{ $t->divisi ?? '–' }}</td>
                    <td class="td">{{ $t->pic ?? '–' }}</td>
                    <td class="td"><x-status :kode="$t->status_kode" :nama="$t->status" /></td>
                    <td class="td"><x-penanda :penanda="$t->penanda" :sisa="$t->sisa_hk" /></td>
                    <td class="td"><a href="{{ route('layanan.show', $t->layanan_id) }}" class="btn-garis px-3 py-1.5 text-xs">Detail<span class="sr-only"> {{ $t->nomor }}</span></a></td>
                </tr>
            @empty
                <tr><td colspan="8"><x-kosong judul="Semua layanan aktif masih dalam tenggat" /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>

<div class="mt-4 grid gap-4 lg:grid-cols-3">
    <section class="kartu p-5">
        <h2 class="judul text-[15px]">Bagaimana status layanan periode ini?</h2>
        <p class="mt-0.5 text-[11px] text-slate-500">Komposisi status, {{ $tgl($dari) }} – {{ $tgl($sampai) }}</p>
        <div class="mt-5 flex flex-wrap items-center gap-6">
            <div class="relative size-36 shrink-0 rounded-full" style="background: {{ $ringkas->masuk ? "conic-gradient($donut)" : '#F1F5F9' }}" role="img" aria-label="Komposisi status layanan">
                <div class="absolute inset-5 grid place-items-center rounded-full bg-white text-center text-sm leading-tight font-bold text-slate-900">{{ (int) $ringkas->masuk }}<br>total</div>
            </div>
            <ul class="min-w-40 flex-1 space-y-3 text-xs">
                @foreach ($komposisi as [$nama, $n, $warna])
                    <li class="flex items-center gap-2"><span class="size-2.5 rounded-sm" style="background: {{ $warna }}"></span><span class="flex-1 text-slate-700">{{ $nama }}</span><span class="font-semibold text-slate-900">{{ $n }}</span></li>
                @endforeach
            </ul>
        </div>
    </section>

    <section class="kartu p-5">
        <h2 class="judul text-[15px]">Lewat kanal apa layanan masuk?</h2>
        <p class="mt-0.5 text-[11px] text-slate-500">Jumlah layanan per kanal, periode terpilih</p>
        <ul class="mt-5 space-y-4 text-xs">
            @forelse ($perKanal as $k)
                <li class="grid grid-cols-[96px_1fr_32px] items-center gap-3"><span class="text-slate-700">{{ $k->nama }}</span>{!! $bar($k->n, $perKanal->max('n')) !!}<span class="text-right font-semibold text-slate-900">{{ $k->n }}</span></li>
            @empty
                <li class="text-slate-500">Belum ada data.</li>
            @endforelse
        </ul>
    </section>

    <section class="kartu p-5">
        <h2 class="judul text-[15px]">Pertanyaan atau pengaduan?</h2>
        <p class="mt-0.5 text-[11px] text-slate-500">Jumlah layanan per jenis, periode terpilih</p>
        <ul class="mt-5 space-y-4 text-xs">
            @foreach (['pertanyaan' => 'Pertanyaan', 'pengaduan' => 'Pengaduan'] as $j => $nama)
                <li class="grid grid-cols-[96px_1fr_32px] items-center gap-3"><span class="text-slate-700">{{ $nama }}</span>{!! $bar($perJenis[$j] ?? 0, $perJenis->max()) !!}<span class="text-right font-semibold text-slate-900">{{ $perJenis[$j] ?? 0 }}</span></li>
            @endforeach
        </ul>
        <p class="mt-5 text-[11px] text-slate-500">{{ round(100 * ($perJenis['pertanyaan'] ?? 0) / $totalJenis) }}% pertanyaan · {{ round(100 * ($perJenis['pengaduan'] ?? 0) / $totalJenis) }}% pengaduan</p>
    </section>
</div>

@endsection