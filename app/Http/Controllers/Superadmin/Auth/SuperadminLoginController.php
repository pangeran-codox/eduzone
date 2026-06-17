<?php

namespace App\Http\Controllers\Superadmin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class SuperadminLoginController extends Controller
{
    /**
     * Tampilkan halaman login superadmin.
     */
    public function showLoginForm()
    {
        if (Auth::check() && Auth::user()->role === 'superadmin') {
            return redirect()->route('superadmin.dashboard');
        }

        return view('superadmin.auth.login');
    }

    /**
     * Proses login superadmin.
     * - Hanya role superadmin yang bisa masuk lewat sini
     * - Rate limiting ketat: 5 percobaan per menit per IP
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'email.required'    => 'Email wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        // ── Rate limiting ──────────────────────────────────────────
        $throttleKey = Str::lower($request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts('superadmin-login:' . $throttleKey, 5)) {
            $seconds = RateLimiter::availableIn('superadmin-login:' . $throttleKey);

            return back()->withErrors([
                'email' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
            ]);
        }

        // ── Attempt login ──────────────────────────────────────────
        $credentials = [
            'email'    => $request->input('email'),
            'password' => $request->input('password'),
        ];

        if (! Auth::attempt($credentials, false)) {
            RateLimiter::hit('superadmin-login:' . $throttleKey, 60);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Email atau password salah.']);
        }

        $user = Auth::user();

        // ── Pastikan hanya superadmin ──────────────────────────────
        if ($user->role !== 'superadmin') {
            Auth::logout();
            RateLimiter::hit('superadmin-login:' . $throttleKey, 60);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Akses ditolak. Halaman ini khusus superadmin.']);
        }

        // ── Pastikan akun aktif ────────────────────────────────────
        if (! $user->is_active) {
            Auth::logout();

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Akun superadmin ini telah dinonaktifkan.']);
        }

        // ── Login sukses ───────────────────────────────────────────
        RateLimiter::clear('superadmin-login:' . $throttleKey);

        // Pastikan search_path benar sebelum query
        \Illuminate\Support\Facades\DB::statement('SET search_path TO public');

        \Illuminate\Support\Facades\DB::table('users')
            ->where('id', $user->id)
            ->update(['last_login_at' => now()]);

        $request->session()->regenerate();

        return redirect()->route('superadmin.dashboard');
    }

    /**
     * Logout superadmin.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('superadmin.login');
    }
}
