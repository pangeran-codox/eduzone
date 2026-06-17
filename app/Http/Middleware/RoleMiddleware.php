<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Role yang diizinkan mengakses route ini.
     */
    protected array $roles;

    public function __construct(string ...$roles)
    {
        $this->roles = $roles;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Middleware bisa menerima roles dari constructor (DI) atau dari route parameter
        $allowedRoles = ! empty($roles) ? $roles : $this->roles;

        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $userRole = auth()->user()->role;

        if (! in_array($userRole, $allowedRoles)) {
            abort(403, 'Kamu tidak punya akses ke halaman ini.');
        }

        return $next($request);
    }
}
