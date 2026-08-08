<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Services\Absensi\HealthCheckService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AbsensiHealthController extends Controller
{
    public function __construct(private readonly HealthCheckService $healthCheck)
    {
    }

    public function index(): View
    {
        // View hanya render shell; datanya diisi lewat fetch() ke status()
        // supaya bisa auto-refresh tanpa reload halaman.
        return view('superadmin.absensi.health');
    }

    public function status(): JsonResponse
    {
        return response()->json($this->healthCheck->fullReport());
    }
}
