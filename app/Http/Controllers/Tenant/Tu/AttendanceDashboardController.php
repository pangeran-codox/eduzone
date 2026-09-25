<?php

namespace App\Http\Controllers\Tenant\Tu;

use App\Http\Controllers\Controller;
use App\Services\Absensi\AttendanceDailyPayloadBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AttendanceDashboardController extends Controller
{
    private const PERSON_TYPES = ['student', 'teacher', 'staff'];

    public function __construct(private AttendanceDailyPayloadBuilder $builder)
    {
    }

    public function index(Request $request): View
    {
        $date = $this->resolveDate($request);
        $personType = $this->resolvePersonType($request);

        return view('tenant.tu.absensi.index', [
            'date' => $date->toDateString(),
            'personType' => $personType,
            'initial' => $this->builder->build($request->user()->school_id, $date, $personType, 20, 50),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $date = $this->resolveDate($request);
        $personType = $this->resolvePersonType($request);

        return response()->json(
            $this->builder->build($request->user()->school_id, $date, $personType, 20, 50)
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

    private function resolvePersonType(Request $request): ?string
    {
        $type = $request->query('person_type');

        return in_array($type, self::PERSON_TYPES, true) ? $type : null;
    }
}