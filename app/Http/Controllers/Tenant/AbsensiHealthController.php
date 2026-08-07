<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\Absensi\HealthCheckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AbsensiHealthController extends Controller
{
    public function __construct(private readonly HealthCheckService $healthCheck)
    {
    }

    public function status(Request $request): JsonResponse
    {
        // Sesuaikan $request->user()->school_id kalau nama kolom/relasinya
        // beda di model User project ini.
        $schoolId = $request->user()->school_id;

        return response()->json(
            $this->healthCheck->schoolReport($schoolId)
        );
    }
}