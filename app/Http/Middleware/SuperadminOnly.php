<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware khusus superadmin area.
 * Kalau belum login → redirect ke superadmin login page (bukan login tenant).
 * Kalau sudah login tapi bukan superadmin → 403.
 */
class SuperadminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('superadmin.login')
                ->withErrors(['email' => 'Silakan login terlebih dahulu.']);
        }

        if (Auth::user()->role !== 'superadmin') {
            abort(403, 'Akses ditolak.');
        }

        if (! Auth::user()->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('superadmin.login')
                ->withErrors(['email' => 'Akun superadmin telah dinonaktifkan.']);
        }

        return $next($request);
    }
}
