@php $pilih = 'max-w-52 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-700 focus:border-primer focus:ring-2 focus:ring-primer/20 focus:outline-none'; @endphp
<form method="get" action="{{ url()->current() }}" class="flex flex-wrap items-center gap-3 border-b border-slate-200 px-4 py-4 last:border-b-0">
    @foreach ($tetap ?? [] as $k)@if (request($k))<input type="hidden" name="{{ $k }}" value="{{ request($k) }}">@endif @endforeach
    @if ($cari ?? true)
    <label class="relative w-full sm:w-64">
        <span class="sr-only">Cari nomor layanan atau nama konsumen</span>
        <svg class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="9" cy="9" r="6"/><path d="m14 14 4 4" stroke-linecap="round"/></svg>
        <input name="q" value="{{ request('q') }}" maxlength="100" placeholder="Cari nomor layanan atau nama konsumen" class="w-full rounded-lg border border-slate-300 bg-white py-2 pr-3 pl-9 text-xs placeholder:text-slate-500 focus:border-primer focus:ring-2 focus:ring-primer/20 focus:outline-none">
    </label>
    @endif
    <span class="text-xs font-semibold text-slate-700">Filter</span>
    <span class="flex max-w-full flex-wrap items-center gap-x-1.5 rounded-lg border border-slate-300 bg-white px-2 text-xs text-slate-700">
        <span class="pl-1 text-slate-500">Periode</span>
        <label class="sr-only" for="f-dari">Dari tanggal</label><input id="f-dari" type="date" name="dari" value="{{ $dari ?? request('dari') }}" class="w-[110px] border-0 bg-transparent px-1 py-2 text-xs focus:ring-0">
        <span aria-hidden="true">–</span>
        <label class="sr-only" for="f-sampai">Sampai tanggal</label><input id="f-sampai" type="date" name="sampai" value="{{ $sampai ?? request('sampai') }}" class="w-[110px] border-0 bg-transparent px-1 py-2 text-xs focus:ring-0">
    </span>
    <label class="sr-only" for="f-kategori">Kategori</label>
    <select id="f-kategori" name="kategori" class="{{ $pilih }}"><option value="">Kategori: Semua</option>
        @foreach ($kategoriList as $k)<option value="{{ $k->id }}" @selected(request('kategori') == $k->id)>Kategori: {{ $k->nama }}</option>@endforeach
    </select>
    <label class="sr-only" for="f-jenis">Jenis layanan</label>
    <select id="f-jenis" name="jenis" class="{{ $pilih }}"><option value="">Jenis: Semua</option>
        @foreach (['pertanyaan' => 'Pertanyaan', 'pengaduan' => 'Pengaduan'] as $v => $n)<option value="{{ $v }}" @selected(request('jenis') === $v)>Jenis: {{ $n }}</option>@endforeach
    </select>
    <label class="sr-only" for="f-kanal">Kanal</label>
    <select id="f-kanal" name="kanal" class="{{ $pilih }}"><option value="">Kanal: Semua</option>
        @foreach ($kanalList as $k)<option value="{{ $k->id }}" @selected(request('kanal') == $k->id)>Kanal: {{ $k->nama }}</option>@endforeach
    </select>
    @if ($divisi ?? true)
        <label class="sr-only" for="f-divisi">Divisi tujuan</label>
        <select id="f-divisi" name="divisi" class="{{ $pilih }}"><option value="">Divisi: Semua</option>
            @foreach ($divisiList as $d)<option value="{{ $d->id }}" @selected(request('divisi') == $d->id)>{{ $d->nama }}</option>@endforeach
        </select>
    @endif
    <button class="btn-primer px-3 py-2 text-xs">Terapkan</button>
    @if (request()->hasAny(['q', 'dari', 'sampai', 'kategori', 'jenis', 'kanal', 'divisi']))
        <a href="{{ url()->current() }}{{ collect($tetap ?? [])->filter(fn ($k) => request($k))->isNotEmpty() ? '?'.http_build_query(request()->only($tetap)) : '' }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">Hapus filter</a>
    @endif
</form>