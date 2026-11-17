<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function form()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['username' => 'required|string', 'password' => 'required|string']);

        $kolom = str_contains($data['username'], '@') ? 'email' : 'username';

        if (! Auth::attempt([$kolom => $data['username'], 'password' => $data['password'], 'aktif' => true], $request->boolean('ingat'))) {
            return back()->withErrors(['username' => 'Username atau password salah, atau akun nonaktif.'])->onlyInput('username');
        }

        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}