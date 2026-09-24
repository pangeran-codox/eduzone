<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Services\Absensi\AttendanceOverviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AbsensiRekapController extends Controller
{
    public function __construct(private readonly AttendanceOverviewService $overview)
    {
    }

    public function index(): View
    {
        // Sama seperti AbsensiHealthController - view cuma shell, data
        // diisi via fetch() ke status() supaya auto-refresh tanpa reload.
        return view('superadmin.absensi.rekap');
    }

    public function status(): JsonResponse
    {
        return response()->json($this->overview->todayReport());
    }
}