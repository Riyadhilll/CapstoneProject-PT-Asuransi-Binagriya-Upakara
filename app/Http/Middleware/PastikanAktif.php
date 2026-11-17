<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PastikanAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->aktif) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['username' => 'Akun Anda telah dinonaktifkan. Hubungi Admin Divisi TI.']);
        }

        return $next($request);
    }
}