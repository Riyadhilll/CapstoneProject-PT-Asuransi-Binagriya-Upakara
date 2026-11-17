@props(['kode', 'nama'])
<span @class([
    'inline-flex min-w-[7.5rem] justify-center rounded-md px-2.5 py-1 text-[11px] font-semibold whitespace-nowrap',
    'bg-amber-100 text-amber-700' => $kode === 'MENUNGGU',
    'bg-sky-100 text-sky-700' => $kode === 'PROSES',
    'bg-emerald-100 text-emerald-700' => $kode === 'SELESAI',
])>{{ $nama }}</span>