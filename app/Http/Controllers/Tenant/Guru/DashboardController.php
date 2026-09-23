<?php

namespace App\Http\Controllers\Tenant\Guru;

use App\Http\Controllers\Controller;
use App\Models\HomeroomAssignment;
use App\Models\Teacher;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $data = ['homeroomClass' => null, 'totalSiswaKelas' => 0];

        // Hanya wali kelas yang perlu info kelas
        if ($user->role === 'wali_kelas') {
            $teacher = Teacher::where('user_id', $user->id)->first();

            if ($teacher) {
                $assignment = HomeroomAssignment::with('class.major')
                    ->where('teacher_id', $teacher->id)
                    ->where('is_active', true)
                    ->first();

                if ($assignment?->class) {
                    $data['homeroomClass']    = $assignment->class;
                    $data['totalSiswaKelas']  = $assignment->class->students()
                        ->where('status', 'aktif')
                        ->count();
                }
            }
        }

        return view('tenant.guru.dashboard.index', $data);
    }
}
