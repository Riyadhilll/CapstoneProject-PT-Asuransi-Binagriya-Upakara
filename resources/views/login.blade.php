<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · Binagriya</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-white text-slate-800 antialiased">
<div class="grid min-h-screen lg:grid-cols-[760fr_680fr]">
    <section class="flex flex-col bg-navy px-6 py-10 text-white sm:px-16 lg:py-14">
        <img src="{{ asset('img/logo-binagriya.png') }}" alt="Binagriya General Insurance" class="h-16 w-auto self-start lg:h-[86px]">
        <div class="my-10 max-w-xl lg:my-auto">
            <span class="block h-[3px] w-14 bg-gold" aria-hidden="true"></span>
            <h1 class="mt-6 font-serif text-3xl leading-tight font-bold lg:text-[34px]">Sistem Informasi Pengelolaan Layanan Konsumen</h1>
            <p class="mt-5 text-sm leading-relaxed text-navy-ink">Satu tempat untuk mencatat pertanyaan dan pengaduan konsumen, meneruskannya kepada PIC, dan memantau tenggat penyelesaian sesuai POJK 22/2023.</p>
        </div>
        <p class="border-t border-navy-2 pt-6 text-xs leading-relaxed text-[#BFD4FF]">Khusus karyawan Kantor Pusat PT Asuransi Binagriya Upakara.<br>Belum punya akun? Hubungi Admin Divisi TI.</p>
    </section>

    <section class="flex flex-col px-6 py-10 sm:px-16">
        <div class="my-auto w-full max-w-[400px] self-center">
            <h2 class="judul text-[26px]">Masuk</h2>
            <p class="mt-1 text-xs text-slate-500">Gunakan akun internal yang diberikan oleh Admin</p>
            @if ($errors->any())
                <div class="mt-5 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-800" role="alert">{{ $errors->first() }}</div>
            @endif
            <form method="post" action="{{ route('login') }}" class="mt-6 space-y-5">
                @csrf
                <div>
                    <label for="username" class="label">Username atau email kantor</label>
                    <input id="username" name="username" value="{{ old('username') }}" required autofocus autocomplete="username" class="input h-11">
                </div>
                <div>
                    <label for="password" class="label">Kata sandi</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password" class="input h-11">
                </div>
                <label class="flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" name="ingat" value="1" class="rounded border-slate-300"> Ingat saya di perangkat ini</label>
                <button class="btn-primer h-[46px] w-full">Masuk ke aplikasi</button>
            </form>
            <p class="mt-5 text-xs text-slate-500">Lupa kata sandi? Minta Admin Divisi TI mengatur ulang akun Anda.</p>
        </div>
        <p class="mt-10 text-[11px] text-slate-500 lg:mx-auto lg:w-[400px]">© 2026 PT Asuransi Binagriya Upakara · Prototipe Capstone Design Project</p>
    </section>
</div>
</body>
</html>