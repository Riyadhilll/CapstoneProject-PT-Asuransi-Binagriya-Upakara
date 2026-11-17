@extends('layouts.app', ['judul' => $layanan->nomor])
@section('remah', 'Daftar Layanan / Detail Layanan')
@section('isi')
@php
    $metode = ['email' => 'Email', 'telepon' => 'Telepon', 'whatsapp' => 'WhatsApp', 'tatap_muka' => 'Tatap muka'];
    $selesai = $layanan->status_kode === 'SELESAI';
    $tambahan = $layanan->perpanjangan->sum('tambahan_hk');
    $total = $layanan->batas_hk + $tambahan;
    $sisa = $tenggat?->sisa_hk;
    $lamaBerjalan = $sisa === null ? null : $total - $sisa;
    $tepat = $layanan->tepatWaktu();
    $masuk = $layanan->riwayat->whereIn('jenis', ['dibuat', 'status'])->groupBy('status_baru')->map(fn ($r) => $r->first()->pada);
    $urutan = ['MENUNGGU' => 0, 'PROSES' => 1, 'SELESAI' => 2][$layanan->status_kode];
    $opsi = array_slice(['PROSES' => 'Dalam Proses', 'SELESAI' => 'Selesai'], $urutan === 0 ? 0 : 1, null, true);
    $pilihStatus = old('status_kode', array_key_first($opsi));
@endphp

<div class="flex flex-wrap items-start justify-between gap-4">
    <div class="flex items-start gap-4">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('layanan.index') }}" class="btn-garis size-9 p-0" aria-label="Kembali">&larr;</a>
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="judul text-[22px]">{{ $layanan->nomor }}</h1>
                <x-status :kode="$layanan->status_kode" :nama="$layanan->status->nama" />
                <span class="rounded-md border border-slate-300 px-2 py-0.5 text-[11px] text-slate-600">{{ ucfirst($layanan->jenis) }} · {{ ucfirst($layanan->bentuk) }}</span>
            </div>
            <p class="mt-1 text-xs text-slate-500">Diterima {{ $layanan->diterima_pada->format('d-m-Y, H:i') }} melalui {{ $layanan->kanal->nama }} · dicatat oleh {{ $layanan->pencatat->nama }}</p>
        </div>
    </div>
    @can('tangani-layanan', $layanan)
        @if ($layanan->tenggat)<a href="#perpanjang" class="btn-garis">Catat Perpanjangan</a>@endif
    @endcan
</div>

<ol class="kartu mt-5 flex items-center gap-3 overflow-x-auto px-5 py-4">
    @foreach (['MENUNGGU' => 'Menunggu Penanganan', 'PROSES' => 'Dalam Proses', 'SELESAI' => 'Selesai'] as $kode => $nama)
        @php $i = $loop->index; $lewat = $i < $urutan; $kini = $i === $urutan; @endphp
        <li class="flex shrink-0 items-center gap-2.5" @if ($kini) aria-current="step" @endif>
            <span @class(['grid size-6 shrink-0 place-items-center rounded-full', 'bg-primer' => $lewat, 'border-[5px] border-primer bg-white' => $kini, 'border-2 border-slate-300 bg-white' => $i > $urutan])></span>
            <span class="text-[11px] leading-tight">
                <span @class(['block', 'font-semibold text-primer' => $kini, 'text-slate-800' => $lewat, 'text-slate-500' => $i > $urutan])>{{ $nama }}</span>
                <span @class(['block', 'font-semibold text-primer' => $kini, 'text-slate-600' => ! $kini])>{{ isset($masuk[$kode]) ? $masuk[$kode]->format('d-m, H:i') : '-' }}</span>
            </span>
        </li>
        @unless ($loop->last)<li aria-hidden="true" @class(['h-0.5 min-w-10 flex-1 rounded', 'bg-primer' => $i < $urutan, 'bg-slate-200' => $i >= $urutan])></li>@endunless
    @endforeach
</ol>

<div class="mt-4 grid items-start gap-4 xl:grid-cols-[1fr_306px]">
    <div class="space-y-4">
        <div class="grid gap-4 md:grid-cols-2">
            <section class="kartu p-5">
                <h2 class="judul text-[15px]">Informasi Layanan</h2>
                <dl class="mt-4 space-y-2.5 text-xs">
                    @foreach (['Kanal' => $layanan->kanal->nama, 'Jenis' => ucfirst($layanan->jenis), 'Kategori' => $layanan->kategori->nama, 'Divisi Tujuan' => $layanan->divisiTujuan?->nama ?? '–', 'PIC' => $layanan->pic?->nama ?? 'Dijawab langsung'] as $k => $v)
                        <div class="grid grid-cols-[120px_1fr]"><dt class="text-slate-500">{{ $k }}</dt><dd class="font-semibold text-slate-900">{{ $v }}</dd></div>
                    @endforeach
                </dl>
            </section>
            <section class="kartu p-5">
                <h2 class="judul text-[15px]">Data Konsumen</h2>
                <dl class="mt-4 space-y-2.5 text-xs">
                    @foreach (['Nama' => $layanan->konsumen->nama, 'Email' => $layanan->konsumen->email, 'Telepon' => $layanan->konsumen->telepon] as $k => $v)
                        <div class="grid grid-cols-[80px_1fr]"><dt class="text-slate-500">{{ $k }}</dt><dd class="font-semibold break-all text-slate-900">{{ $v }}</dd></div>
                    @endforeach
                </dl>
                <p class="mt-4 rounded-md border border-dashed border-primer/40 bg-primer-muda/50 px-2.5 py-2 text-[11px] text-primer-tua">Akses data konsumen dibatasi sesuai peran pengguna (UU PDP)</p>
            </section>
        </div>

        <section class="kartu p-5">
            <h2 class="judul text-[15px]">Uraian Layanan</h2>
            <p class="mt-3 text-[13px] leading-relaxed whitespace-pre-line text-slate-700">{{ $layanan->uraian }}</p>
            @if ($selesai)
                <div class="mt-4 rounded-lg bg-emerald-50 p-3.5">
                    <p class="text-[11px] font-semibold text-emerald-800">Hasil penyelesaian · disampaikan lewat {{ $metode[$layanan->metode_penyampaian] ?? $layanan->metode_penyampaian }}, {{ $layanan->selesai_pada->format('d-m-Y H:i') }}</p>
                    <p class="mt-1 text-[13px] whitespace-pre-line text-slate-800">{{ $layanan->hasil }}</p>
                </div>
            @endif
        </section>

        @can('tangani-layanan', $layanan)
            <form method="post" action="{{ route('layanan.status', $layanan) }}" id="form-status" class="kartu border-primer p-5 shadow-[0_0_0_1px_var(--color-primer)]">
                @csrf
                <h2 id="judul-status" class="text-sm font-semibold text-slate-900">Perbarui Status &amp; Tindak Lanjut</h2>
                <label for="status_kode" class="label mt-4">Status baru *</label>
                <select id="status_kode" name="status_kode" class="input">
                    @foreach ($opsi as $v => $n)<option value="{{ $v }}" @selected($pilihStatus === $v)>{{ $n }}</option>@endforeach
                </select>

                <div id="kotak-catatan" class="mt-4">
                    <label for="catatan" class="label">Catatan tindak lanjut *</label>
                    <textarea id="catatan" name="catatan" rows="3" class="input" placeholder="Contoh: dokumen klaim sudah diverifikasi, menunggu hasil survei">{{ old('catatan') }}</textarea>
                </div>

                <div id="kotak-selesai" class="mt-4 space-y-4">
                    <div>
                        <label for="hasil" class="label">Hasil penyelesaian *</label>
                        <textarea id="hasil" name="hasil" rows="3" class="input">{{ old('hasil') }}</textarea>
                    </div>
                    <div>
                        <p class="label">Waktu penyelesaian <span class="font-normal text-slate-500">· dicatat otomatis oleh sistem saat disimpan, tidak dapat diubah</span></p>
                        <dl class="grid grid-cols-2 gap-px overflow-hidden rounded-lg border border-slate-200 bg-slate-200 text-xs sm:grid-cols-4">
                            <div class="bg-slate-50 p-3"><dt class="text-[11px] text-slate-500">Waktu selesai</dt><dd class="mt-0.5 font-semibold text-slate-900">{{ now()->format('d-m-Y, H:i') }}</dd></div>
                            <div class="bg-slate-50 p-3"><dt class="text-[11px] text-slate-500">Lama penyelesaian</dt><dd class="mt-0.5 font-semibold text-slate-900">{{ $lamaBerjalan === null ? '–' : $lamaBerjalan.' hari kerja' }}</dd></div>
                            <div class="bg-slate-50 p-3"><dt class="text-[11px] text-slate-500">Tenggat ({{ $total }} hari kerja)</dt><dd class="mt-0.5 font-semibold text-slate-900">{{ $layanan->tenggat?->format('d-m-Y') ?? '–' }}</dd></div>
                            <div class="bg-slate-50 p-3"><dt class="text-[11px] text-slate-500">Ketepatan</dt><dd class="mt-0.5"><x-penanda :tepat="$sisa === null ? null : $sisa >= 0" /></dd></div>
                        </dl>
                    </div>
                    <fieldset>
                        <legend class="label">Metode penyampaian hasil *</legend>
                        <div class="segmen-grup max-w-lg">
                            @foreach ($metode as $v => $n)
                                <label class="segmen"><input type="radio" name="metode_penyampaian" value="{{ $v }}" class="sr-only" @checked(old('metode_penyampaian') === $v)>{{ $n }}</label>
                            @endforeach
                        </div>
                        <p class="mt-1.5 text-[11px] text-slate-500">Hasil disampaikan kepada konsumen di luar sistem; sistem mencatat metodenya untuk dashboard.</p>
                    </fieldset>
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <button type="reset" class="btn-garis">Batal</button>
                    <button id="tombol-status" class="btn-primer">Simpan Pembaruan</button>
                </div>
            </form>

            <div class="grid gap-4 md:grid-cols-2">
                @if ($layanan->tenggat)
                <form method="post" action="{{ route('layanan.perpanjang', $layanan) }}" id="perpanjang" class="kartu scroll-mt-6 p-5">
                    @csrf
                    <h2 class="text-sm font-semibold text-slate-900">Catat Perpanjangan</h2>
                    <label for="tambahan_hk" class="label mt-4">Tambahan hari kerja *</label>
                    <input id="tambahan_hk" type="number" name="tambahan_hk" min="1" max="255" required class="input">
                    <label for="alasan-p" class="label mt-3">Alasan *</label>
                    <textarea id="alasan-p" name="alasan" rows="2" required class="input"></textarea>
                    <button class="btn-garis mt-4">Simpan Perpanjangan</button>
                </form>
                @endif
                <form method="post" action="{{ route('layanan.alihkan', $layanan) }}" class="kartu p-5">
                    @csrf
                    <h2 class="text-sm font-semibold text-slate-900">Alihkan ke PIC Lain</h2>
                    <label for="divisi-alih" class="label mt-4">Divisi *</label>
                    <select id="divisi-alih" name="divisi_tujuan_id" data-pic="pic-alih" required class="input">
                        @foreach ($divisiList as $d)<option value="{{ $d->id }}" @selected($layanan->divisi_tujuan_id === $d->id)>{{ $d->nama }}</option>@endforeach
                    </select>
                    <label for="pic-alih" class="label mt-3">PIC *</label>
                    <select id="pic-alih" name="pic_id" required class="input"><option value="">Pilih PIC</option>
                        @foreach ($picList->where('id', '!=', $layanan->pic_id) as $p)<option value="{{ $p->id }}" data-divisi="{{ $p->divisi_id }}">{{ $p->nama }}</option>@endforeach
                    </select>
                    <label for="alasan-a" class="label mt-3">Alasan *</label>
                    <textarea id="alasan-a" name="alasan" rows="2" required class="input"></textarea>
                    <button class="btn-garis mt-4">Alihkan</button>
                </form>
            </div>
        @endcan
    </div>

    <div class="space-y-4">
        <section class="kartu p-5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="judul text-[15px] whitespace-nowrap">Tenggat Penyelesaian</h2>
                @if ($selesai)<x-penanda :tepat="$tepat" />@else<x-penanda :penanda="$tenggat?->penanda" :sisa="$sisa" />@endif
            </div>
            <p class="mt-3 text-2xl font-bold text-slate-900">{{ $layanan->tenggat?->format('d-m-Y') ?? 'Belum berjalan' }}</p>
            <p class="mt-1 text-[11px] text-slate-500">
                @if ($selesai)
                    Selesai {{ $layanan->selesai_pada->format('d-m-Y') }}{{ $fakta?->lama_hk !== null ? ' · '.$fakta->lama_hk.' hari kerja' : '' }}
                @elseif ($layanan->tenggat)
                    {{ $sisa >= 0 ? 'Sisa '.$sisa : 'Lewat '.abs($sisa) }} hari kerja · dihitung sejak {{ $layanan->bentuk === 'tertulis' ? 'dokumen lengkap' : 'diterima' }}
                @else
                    Dokumen tertulis belum lengkap
                @endif
            </p>
            @if (! $selesai && $lamaBerjalan !== null)
                <div class="mt-4 h-2 rounded-full bg-slate-100"><div @class(['h-2 rounded-full', 'bg-primer' => $sisa > 2, 'bg-amber-500' => $sisa >= 0 && $sisa <= 2, 'bg-red-600' => $sisa < 0]) style="width: {{ min(100, max(4, round(100 * $lamaBerjalan / max(1, $total)))) }}%"></div></div>
                <p class="mt-1.5 flex justify-between text-[11px] text-slate-500"><span>Hari kerja ke-{{ $lamaBerjalan }}</span><span>dari {{ $total }} (POJK 22/2023)</span></p>
            @endif
            <dl class="mt-4 space-y-2 border-t border-slate-200 pt-4 text-xs">
                <div class="grid grid-cols-[110px_1fr]"><dt class="text-slate-500">{{ $selesai ? 'Lama penyelesaian' : 'Lama berjalan' }}</dt><dd class="font-semibold text-slate-900">{{ $selesai ? ($fakta?->lama_hk ?? '–') : ($lamaBerjalan ?? '–') }}{{ ($selesai ? $fakta?->lama_hk : $lamaBerjalan) !== null ? ' hari kerja' : '' }}</dd></div>
                <div class="grid grid-cols-[110px_1fr]"><dt class="text-slate-500">Perpanjangan</dt><dd class="font-semibold text-slate-900">{{ $tambahan ? '+'.$tambahan.' hari kerja ('.$layanan->perpanjangan->count().'×)' : 'Tidak ada' }}</dd></div>
                <div class="grid grid-cols-[110px_1fr]"><dt class="text-slate-500">Alasan perpanjangan</dt><dd class="font-semibold text-slate-900">{{ $layanan->perpanjangan->pluck('alasan')->join('; ') ?: '-' }}</dd></div>
            </dl>
        </section>

        <section class="kartu p-5">
            <h2 class="judul text-[15px]">Riwayat Status &amp; Penugasan</h2>
            <ol class="mt-4">
                @foreach ($layanan->riwayat->reverse() as $r)
                    <li class="relative pb-5 pl-6 last:pb-0">
                        @unless ($loop->last)<span class="absolute top-3 bottom-0 left-[5px] w-px bg-slate-200" aria-hidden="true"></span>@endunless
                        <span @class(['absolute top-1 left-0 size-[11px] rounded-full border-2 border-primer', 'bg-primer ring-2 ring-primer/20' => $loop->first, 'bg-white' => ! $loop->first]) aria-hidden="true"></span>
                        <p class="text-xs font-semibold text-slate-900">
                            @switch($r->jenis)
                                @case('dibuat') Layanan dicatat melalui kanal {{ $layanan->kanal->nama }} @break
                                @case('status') Status diubah: {{ $namaStatus[$r->status_lama] ?? $r->status_lama }} &rarr; {{ $namaStatus[$r->status_baru] ?? $r->status_baru }} @break
                                @case('penugasan') PIC dialihkan: {{ $namaPengguna[$r->pic_lama] ?? '–' }} &rarr; {{ $namaPengguna[$r->pic_baru] ?? '–' }} ({{ $namaDivisi[$r->divisi_baru] ?? '–' }}) @break
                                @default Tenggat diperpanjang
                            @endswitch
                        </p>
                        <p class="mt-0.5 text-[11px] text-slate-500">{{ $r->pada->format('d-m-Y, H:i') }} · {{ $r->pengguna->nama }} ({{ $r->pengguna->peran->label() }})</p>
                        @if ($r->jenis === 'dibuat' && $r->pic_baru)
                            <p class="mt-1 text-[11px] text-slate-700">PIC ditetapkan: {{ $namaPengguna[$r->pic_baru] ?? '–' }} ({{ $namaDivisi[$r->divisi_baru] ?? '–' }})</p>
                        @endif
                        @if ($r->catatan)<p class="mt-2 rounded-md bg-slate-50 px-2.5 py-2 text-[11px] text-slate-700">{{ $r->catatan }}</p>@endif
                    </li>
                @endforeach
            </ol>
        </section>
    </div>
</div>

@can('tangani-layanan', $layanan)
@push('script')
<script>
    const st = document.getElementById('status_kode');
    const aturStatus = () => {
        const selesai = st.value === 'SELESAI';
        document.getElementById('kotak-catatan').hidden = selesai;
        document.getElementById('kotak-selesai').hidden = !selesai;
        document.getElementById('judul-status').textContent = selesai ? 'Catat Penyelesaian Layanan' : 'Perbarui Status & Tindak Lanjut';
        document.getElementById('tombol-status').textContent = selesai ? 'Simpan Penyelesaian' : 'Simpan Pembaruan';
    };
    st.addEventListener('change', aturStatus);
    document.getElementById('form-status').addEventListener('reset', () => setTimeout(aturStatus));
    aturStatus();
</script>
@endpush
@endcan
@endsection