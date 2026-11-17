<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $judul ?? 'Layanan Konsumen' }} · Binagriya</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
@php
    $u = auth()->user();
    $peran = $u->peran->value;
    $labelPeran = $u->peran->label().(in_array($peran, ['pic', 'admin']) && $u->divisi ? ' · '.$u->divisi->nama : '');

    $inisial = collect(explode(' ', $u->nama))->take(2)->map(fn ($k) => mb_substr($k, 0, 1))->join('');
    $grup = array_filter([
        'Utama' => array_filter([
            ['dashboard', 'Dashboard', $peran !== 'pic', ['dashboard']],
            ['layanan.create', 'Catat Layanan', $peran === 'petugas', ['layanan.create']],
            ['tugas', 'Tugas Saya', $peran === 'pic', ['tugas']],
            ['layanan.index', 'Daftar Layanan', true, ['layanan.index', 'layanan.show']],
        ], fn ($m) => $m[2]),
        'Pengaturan' => $peran === 'admin' ? [['akun.index', 'Kelola Akun', true, ['akun.*']], ['referensi.index', 'Data Referensi', true, ['referensi.*']], ['etl', 'Log ETL', true, ['etl']]] : [],
    ]);
@endphp
<aside class="bg-navy lg:fixed lg:inset-y-0 lg:left-0 lg:w-60">
    <div class="flex items-center gap-4 px-4 py-3 lg:block lg:px-6 lg:pt-3 lg:pb-0">
        <a href="{{ route('beranda') }}" class="block shrink-0 lg:border-b lg:border-navy-2 lg:pb-3">
            <img src="{{ asset('img/logo-binagriya.png') }}" alt="Binagriya General Insurance" class="h-10 w-auto lg:h-[54px]">
        </a>
        <nav class="flex min-w-0 gap-1 overflow-x-auto lg:mt-5 lg:block lg:space-y-6 lg:overflow-visible" aria-label="Menu utama">
            @foreach ($grup as $namaGrup => $menu)
                <div class="flex gap-1 lg:block lg:space-y-1">
                    <p class="hidden px-0 pb-2 text-[10px] tracking-wider text-navy-muted uppercase lg:block">{{ $namaGrup }}</p>
                    @foreach ($menu as [$rute, $label, , $aktifDi])
                        @php $aktif = request()->routeIs(...$aktifDi); @endphp
                        <a href="{{ route($rute) }}" @if ($aktif) aria-current="page" @endif @class([
                            'relative block rounded-lg px-4 py-2.5 text-[13px] whitespace-nowrap lg:-mx-3 lg:px-7',
                            'bg-navy-2 font-semibold text-white' => $aktif,
                            'text-navy-ink hover:bg-navy-2/60' => ! $aktif,
                        ])>
                            @if ($aktif)<span class="absolute top-2 bottom-2 left-0 hidden w-[3px] rounded-r bg-gold lg:block"></span>@endif
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>
    </div>
</aside>

<div class="lg:pl-60">
    <header class="flex flex-wrap items-center gap-x-6 gap-y-3 border-b border-slate-200 bg-white px-4 py-3 sm:px-7">
        <p class="text-xs text-slate-500">@yield('remah', $judul ?? '')</p>
        <div class="ml-auto flex items-center gap-3">
            <span class="grid size-9 place-items-center rounded-full bg-primer-muda text-xs font-bold text-primer" aria-hidden="true">{{ $inisial }}</span>
            <div class="leading-tight">
                <p class="text-xs font-semibold text-slate-900">{{ $u->nama }}</p>
                <p class="text-[11px] text-slate-500">{{ $labelPeran }}</p>
            </div>
            <form method="post" action="{{ route('logout') }}">@csrf<button class="rounded-md px-2 py-1 text-xs text-slate-500 hover:bg-slate-100 hover:text-slate-800">Keluar</button></form>
        </div>
    </header>

    <main class="px-4 py-6 sm:px-7">
        @if (session('pesan'))
            <div class="mb-4 rounded-[10px] border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('pesan') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-[10px] border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                <p class="font-semibold">Data belum bisa disimpan</p>
                <ul class="mt-1 list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('isi')
    </main>
</div>
<script>
    document.querySelectorAll('select[data-pic]').forEach((divisi) => {
        const pic = document.getElementById(divisi.dataset.pic);
        const sinkron = () => {
            for (const o of pic.options) if (o.value) o.hidden = o.dataset.divisi !== divisi.value;
            if (pic.selectedOptions[0]?.hidden) pic.value = '';
            pic.dispatchEvent(new Event('change'));
        };
        divisi.addEventListener('change', sinkron);
        sinkron();
    });
    document.addEventListener('submit', (e) => {
        if (e.defaultPrevented || e.target.method !== 'post' || !e.submitter) return;
        e.submitter.disabled = true;
        e.submitter.dataset.terkirim = '1';
    });
    window.addEventListener('pageshow', (e) => {
        if (e.persisted) document.querySelectorAll('[data-terkirim]').forEach((b) => { b.disabled = false; delete b.dataset.terkirim; });
    });
</script>
@stack('script')
</body>
</html>