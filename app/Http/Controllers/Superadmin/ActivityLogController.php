<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = ActivityLog::query()
            ->withoutTenant()
            ->with('user:id,username,email')
            ->when($request->filled('school'), fn ($q) => $q->where('school_id', $request->input('school')))
            ->when($request->filled('activity'), fn ($q) => $q->where('activity', 'ilike', '%'.$request->input('activity').'%'))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        $schoolNames = School::query()
            ->whereIn('id', $logs->pluck('school_id')->filter())
            ->pluck('name', 'id');

        foreach ($logs as $log) {
            $log->school_name = $log->school_id ? ($schoolNames[$log->school_id] ?? '—') : null;

            // description kadang berisi JSON (lihat ActivityLog::sensitiveDataHistory()
            // di model — dipakai buat activity semacam "sensitive_data.updated").
            // Coba decode buat ditampilkan rapi; kalau bukan JSON valid, biarkan
            // sebagai teks biasa.
            $decoded = json_decode((string) $log->description, true);
            $log->description_is_json = json_last_error() === JSON_ERROR_NONE && is_array($decoded);
            $log->description_pretty = $log->description_is_json
                ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : null;
        }

        return view('superadmin.logs.index', [
            'logs' => $logs,
            'schools' => School::orderBy('name')->pluck('name', 'id'),
            'schoolFilter' => $request->input('school', ''),
            'activityFilter' => $request->input('activity', ''),
            'from' => $request->input('from', ''),
            'to' => $request->input('to', ''),
        ]);
    }
}
