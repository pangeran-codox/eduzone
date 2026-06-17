<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Tampilkan halaman login.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Proses login.
     *
     * Coba auth dengan email terlebih dahulu.
     * Jika gagal (input bukan format email atau tidak ditemukan), fallback ke username.
     */
    public function login(Request $request)
    {
        $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ], [
            'login.required'    => 'Email atau username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $login    = $request->input('login');
        $password = $request->input('password');
        $remember = $request->boolean('remember');

        // Coba login berdasarkan email, kemudian fallback ke username
        $authenticated = Auth::attempt(['email' => $login, 'password' => $password], $remember)
            || Auth::attempt(['username' => $login, 'password' => $password], $remember);

        if (! $authenticated) {
            return back()
                ->withInput($request->only('login', 'remember'))
                ->withErrors(['login' => 'Email/username atau password salah.']);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Cek apakah akun aktif
        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withInput($request->only('login', 'remember'))
                ->withErrors(['login' => 'Akun kamu tidak aktif. Hubungi administrator sekolah.']);
        }

        // Update last_login_at — set search_path dulu untuk PostgreSQL
        \Illuminate\Support\Facades\DB::statement('SET search_path TO public');
        \Illuminate\Support\Facades\DB::table('users')
            ->where('id', $user->id)
            ->update(['last_login_at' => now()]);

        // Regenerate session untuk mencegah session fixation
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Logout user.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
