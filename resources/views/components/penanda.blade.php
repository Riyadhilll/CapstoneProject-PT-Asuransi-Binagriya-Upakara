@props(['penanda' => null, 'sisa' => null, 'tepat' => null])
@php
    [$teks, $warna] = match (true) {
        $tepat === true => ['Selesai tepat waktu', 'text-emerald-600'],
        $tepat === false => ['Selesai terlambat', 'text-red-600'],
        $penanda === 'menunggu_dokumen' => ['Menunggu dokumen', 'text-slate-500'],
        $penanda === 'melewati' => ['Lewat '.abs($sisa).' hari kerja', 'text-red-600'],
        $penanda === 'mendekati' && (int) $sisa === 0 => ['Tenggat hari ini', 'text-amber-600'],
        $penanda === 'mendekati' => ['Sisa '.$sisa.' hari kerja', 'text-amber-600'],
        $penanda === 'dalam' => ['Sisa '.$sisa.' hari kerja', 'text-sky-600'],
        default => [null, null],
    };
@endphp
@if ($teks)
    <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold whitespace-nowrap {{ $warna }}"><span class="size-2 rounded-full bg-current" aria-hidden="true"></span>{{ $teks }}</span>
@else
    <span class="text-slate-400">–</span>
@endif