@extends('layouts.app', ['judul' => 'Catat Layanan'])
@section('remah', 'Layanan / Catat Layanan Baru')
@section('isi')
@php $metode = ['email' => 'Email', 'telepon' => 'Telepon', 'whatsapp' => 'WhatsApp', 'tatap_muka' => 'Tatap muka']; @endphp
<div class="flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="judul text-[22px]">Catat Layanan Baru</h1>
        <p class="mt-1 text-xs text-slate-500">Formulir terstandar untuk pertanyaan dan pengaduan dari seluruh kanal</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('layanan.index') }}" class="btn-garis">Batal</a>
        <button form="form-layanan" class="btn-primer">Simpan Layanan</button>
    </div>
</div>

<div class="mt-5 grid items-start gap-4 xl:grid-cols-[1fr_330px]">
    <form id="form-layanan" method="post" action="{{ route('layanan.store') }}" class="space-y-4">
        @csrf
        <section class="kartu p-5">
            <h2 class="text-sm font-semibold text-slate-900">Data Konsumen</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <div><label for="nama" class="label">Nama Konsumen *</label><input id="nama" name="nama" value="{{ old('nama') }}" required maxlength="150" class="input"></div>
                <div><label for="email" class="label">Alamat Email *</label><input id="email" type="email" name="email" value="{{ old('email') }}" required maxlength="150" class="input"></div>
                <div><label for="telepon" class="label">Nomor Telepon *</label><input id="telepon" type="tel" name="telepon" value="{{ old('telepon') }}" required maxlength="20" class="input"></div>
            </div>
            <p class="mt-3 text-[11px] text-slate-500">Data pribadi dibatasi pada nama, email, dan nomor telepon (UU No. 27 Tahun 2022)</p>
        </section>

        <section class="kartu p-5">
            <h2 class="text-sm font-semibold text-slate-900">Informasi Layanan</h2>
            <div class="mt-4 grid gap-x-4 gap-y-5 md:grid-cols-2">
                <fieldset>
                    <legend class="label">Kanal Penerimaan *</legend>
                    <div class="segmen-grup">
                        @foreach ($kanalList as $k)
                            <label class="segmen"><input type="radio" name="kanal_id" value="{{ $k->id }}" class="sr-only" required @checked(old('kanal_id') == $k->id)>{{ $k->nama === 'Kunjungan langsung' ? 'Kunjungan' : $k->nama }}</label>
                        @endforeach
                    </div>
                </fieldset>
                <fieldset>
                    <legend class="label">Jenis Layanan *</legend>
                    <div class="segmen-grup">
                        @foreach (['pertanyaan' => 'Pertanyaan', 'pengaduan' => 'Pengaduan'] as $v => $n)
                            <label class="segmen"><input type="radio" name="jenis" value="{{ $v }}" class="sr-only" required @checked(old('jenis', 'pertanyaan') === $v)>{{ $n }}</label>
                        @endforeach
                    </div>
                </fieldset>
                <div>
                    <label for="kategori" class="label">Kategori *</label>
                    <select id="kategori" name="kategori_id" required class="input"><option value="">Pilih kategori</option>
                        @foreach ($kategoriList as $k)<option value="{{ $k->id }}" data-divisi="{{ $k->divisi_usulan_id }}" @selected(old('kategori_id') == $k->id)>{{ $k->nama }}</option>@endforeach
                    </select>
                </div>
                <fieldset>
                    <legend class="label">Bentuk Penyampaian *</legend>
                    <div class="segmen-grup">
                        @foreach (['lisan' => 'Lisan · 5 hari kerja', 'tertulis' => 'Tertulis · 10 hari kerja'] as $v => $n)
                            <label class="segmen"><input type="radio" name="bentuk" value="{{ $v }}" class="sr-only" required @checked(old('bentuk', 'lisan') === $v)>{{ $n }}</label>
                        @endforeach
                    </div>
                </fieldset>
                <div>
                    <label for="diterima" class="label">Tanggal &amp; Waktu Diterima *</label>
                    <input id="diterima" type="datetime-local" name="diterima_pada" value="{{ old('diterima_pada', now()->format('Y-m-d\TH:i')) }}" min="2020-01-01T00:00" max="{{ now()->format('Y-m-d\TH:i') }}" required class="input">
                </div>
                <div id="kotak-dokumen">
                    <label for="dokumen" class="label">Tanggal Dokumen Lengkap (tertulis)</label>
                    <input id="dokumen" type="date" name="tgl_dokumen_lengkap" value="{{ old('tgl_dokumen_lengkap') }}" max="{{ today()->toDateString() }}" class="input">
                    <p class="mt-1 text-[11px] text-slate-500">Kosongkan bila belum lengkap; tenggat belum berjalan.</p>
                </div>
                <div class="md:col-span-2">
                    <label for="uraian" class="label">Uraian Layanan *</label>
                    <textarea id="uraian" name="uraian" rows="3" required class="input">{{ old('uraian') }}</textarea>
                </div>
            </div>
        </section>

        <section class="kartu p-5">
            <h2 class="text-sm font-semibold text-slate-900">Penanganan</h2>
            <label class="mt-4 flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3.5">
                <input type="checkbox" id="langsung" name="selesai_saat_diterima" value="1" class="peer sr-only" @checked(old('selesai_saat_diterima'))>
                <span class="relative mt-0.5 h-5 w-9 shrink-0 rounded-full bg-slate-300 transition peer-checked:bg-primer peer-focus-visible:ring-2 peer-focus-visible:ring-primer/40 after:absolute after:top-0.5 after:left-0.5 after:size-4 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-4" aria-hidden="true"></span>
                <span><span class="block text-[13px] font-semibold text-slate-900">Selesai saat diterima</span><span class="block text-[11px] text-slate-500">Aktifkan jika sudah dijawab langsung oleh petugas (tidak perlu diteruskan ke PIC)</span></span>
            </label>
            <div id="kotak-teruskan" class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <label for="divisi" class="label">Divisi Tujuan *</label>
                    <select id="divisi" name="divisi_tujuan_id" data-pic="pic" class="input"><option value="">Pilih divisi</option>
                        @foreach ($divisiList as $d)<option value="{{ $d->id }}" @selected(old('divisi_tujuan_id') == $d->id)>{{ $d->nama }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label for="pic" class="label">PIC Penanggung Jawab *</label>
                    <select id="pic" name="pic_id" class="input"><option value="">Pilih PIC</option>
                        @foreach ($picList as $p)<option value="{{ $p->id }}" data-divisi="{{ $p->divisi_id }}" data-nama="{{ $p->nama }}" @selected(old('pic_id') == $p->id)>{{ $p->nama }} · {{ $tugasAktif[$p->id] ?? 0 }} tugas aktif</option>@endforeach
                    </select>
                </div>
            </div>
            <div id="kotak-langsung" class="mt-4 space-y-4">
                <div>
                    <label for="hasil" class="label">Jawaban yang disampaikan *</label>
                    <textarea id="hasil" name="hasil" rows="3" class="input">{{ old('hasil') }}</textarea>
                </div>
                <fieldset>
                    <legend class="label">Metode penyampaian *</legend>
                    <div class="segmen-grup max-w-lg">
                        @foreach ($metode as $v => $n)
                            <label class="segmen"><input type="radio" name="metode_penyampaian" value="{{ $v }}" class="sr-only" @checked(old('metode_penyampaian') === $v)>{{ $n }}</label>
                        @endforeach
                    </div>
                </fieldset>
            </div>
        </section>
    </form>

    <aside class="space-y-4 xl:sticky xl:top-6">
        <div class="rounded-[10px] bg-navy p-5 text-white">
            <p class="text-[10px] tracking-wider text-navy-ink uppercase">Nomor layanan (otomatis)</p>
            <p class="mt-1 text-sm font-semibold">Diberikan otomatis saat disimpan</p>
            <dl class="mt-4 space-y-2 border-t border-navy-2 pt-3 text-[11px]">
                <div class="flex justify-between gap-3"><dt class="text-navy-ink">Status awal</dt><dd id="pratinjau-status" class="text-right font-semibold">Menunggu Penanganan</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-navy-ink">Unit tujuan</dt><dd id="pratinjau-unit" class="text-right font-semibold">–</dd></div>
            </dl>
        </div>
        <div class="kartu p-5">
            <h2 class="judul text-[15px]">Perhitungan Tenggat</h2>
            <dl class="mt-3 space-y-2 text-xs">
                <div class="grid grid-cols-[100px_1fr]"><dt class="text-slate-500">Dasar</dt><dd id="t-dasar" class="font-semibold text-slate-900"></dd></div>
                <div class="grid grid-cols-[100px_1fr]"><dt class="text-slate-500">Mulai dihitung</dt><dd id="t-mulai" class="font-semibold text-slate-900"></dd></div>
                <div class="grid grid-cols-[100px_1fr]"><dt class="text-slate-500">Batas tenggat</dt><dd id="t-batas" class="font-semibold text-slate-900"></dd></div>
                <div class="grid grid-cols-[100px_1fr]"><dt class="text-slate-500">Hari libur</dt><dd class="font-semibold text-slate-900">Sabtu–Minggu &amp; libur nasional dikecualikan</dd></div>
            </dl>
            <p id="t-chip" class="mt-3 flex items-center gap-1.5 rounded-md border border-dashed border-primer/40 bg-primer-muda/60 px-2.5 py-1.5 text-[11px] font-semibold text-primer"></p>
        </div>
    </aside>
</div>

@push('script')
<script>
    const hk = @json($hariKerja), hariIni = @json(today()->toDateString());
    const form = document.getElementById('form-layanan'), langsung = document.getElementById('langsung');
    const kategori = document.getElementById('kategori'), divisi = document.getElementById('divisi'), pic = document.getElementById('pic');
    const isi = (id, teks) => { document.getElementById(id).textContent = teks; };
    const fmt = (d) => d ? d.split('-').reverse().join('-') : '–';
    const tambahHk = (mulai, n) => { const i = hk.findIndex((d) => d > mulai); return i < 0 ? null : (hk[i + n - 1] ?? null); };
    const hitungHk = (dari, sampai) => hk.filter((d) => d > dari && d <= sampai).length;

    function perbarui() {
        const tertulis = form.bentuk.value === 'tertulis', cepat = langsung.checked, batas = tertulis ? 10 : 5;
        document.getElementById('kotak-dokumen').hidden = !tertulis;
        form.tgl_dokumen_lengkap.min = form.diterima_pada.value.slice(0, 10);
        document.getElementById('kotak-teruskan').hidden = cepat;
        document.getElementById('kotak-langsung').hidden = !cepat;

        const mulai = tertulis ? (form.tgl_dokumen_lengkap.value || null) : (form.diterima_pada.value.slice(0, 10) || null);
        const tenggat = mulai ? tambahHk(mulai, batas) : null;
        isi('t-dasar', (tertulis ? 'Tertulis' : 'Lisan') + ' · ' + batas + ' hari kerja');
        isi('t-mulai', mulai ? fmt(mulai) : 'Menunggu dokumen lengkap');
        isi('t-batas', fmt(tenggat));
        isi('t-chip', cepat ? 'Selesai saat diterima · tidak ada tenggat'
            : !mulai ? 'Tenggat belum berjalan'
            : !tenggat ? 'Kalender kerja belum mencakup tanggal ini'
            : tenggat < hariIni ? 'Melewati tenggat · ' + hitungHk(tenggat, hariIni) + ' hari kerja'
            : 'Dalam tenggat · sisa ' + hitungHk(hariIni, tenggat) + ' hari kerja');

        const namaPic = pic.selectedOptions[0]?.dataset.nama;
        isi('pratinjau-status', cepat ? 'Selesai' : 'Menunggu Penanganan');
        isi('pratinjau-unit', cepat ? 'Dijawab langsung' : divisi.value ? divisi.selectedOptions[0].text + (namaPic ? ' · ' + namaPic : '') : '–');
    }
    kategori.addEventListener('change', () => {
        const usulan = kategori.selectedOptions[0]?.dataset.divisi;
        if (usulan) { divisi.value = usulan; divisi.dispatchEvent(new Event('change')); }
    });
    form.addEventListener('change', perbarui);
    perbarui();
</script>
@endpush
@endsection