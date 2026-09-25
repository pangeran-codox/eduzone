<?php

namespace App\Http\Controllers\Tenant\Kepsek;

use App\Http\Controllers\Controller;
use App\Services\Absensi\AttendanceDailyPayloadBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AttendanceMonitorController extends Controller
{
    public function __construct(private AttendanceDailyPayloadBuilder $builder)
    {
    }

    public function index(Request $request): View
    {
        $date = $this->resolveDate($request);

        return view('tenant.kepsek.absensi.index', [
            'date' => $date->toDateString(),
            'initial' => $this->builder->build($request->user()->school_id, $date, null, 15),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $date = $this->resolveDate($request);

        return response()->json(
            $this->builder->build($request->user()->school_id, $date, null, 15)
        );
    }

    private function resolveDate(Request $request): Carbon
    {
        $raw = $request->query('date');

        if ($raw && preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            try {
                return Carbon::createFromFormat('Y-m-d', $raw)->startOfDay();
            } catch (\Exception) {
                //
            }
        }

        return Carbon::today();
    }
}