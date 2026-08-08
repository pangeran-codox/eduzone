<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public const ROLE_LABELS = [
        'superadmin' => 'Superadmin',
        'kepsek' => 'Kepala Sekolah',
        'kurikulum' => 'Kurikulum',
        'tu' => 'Tata Usaha',
        'guru_mapel' => 'Guru Mapel',
        'wali_kelas' => 'Wali Kelas',
        'kesiswaan' => 'Kesiswaan',
        'bk' => 'Bimbingan Konseling',
        'toolman' => 'Toolman',
        'siswa' => 'Siswa',
    ];

    public function index(Request $request): View
    {
        $users = User::query()
            // Superadmin lintas sekolah — lihat SKILL.md bagian "Query &
            // Tenant Scope". Kalau ternyata User model tidak pakai
            // BelongsToSchool/SchoolScope (school_id-nya nullable, dipakai
            // juga untuk akun superadmin sendiri), method ini mungkin tidak
            // perlu/tidak ada — hapus baris ini kalau muncul error method
            // withoutTenant() not found.
            ->withoutTenant()
            ->when($request->filled('school'), fn ($q) => $q->where('school_id', $request->input('school')))
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->input('role')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->input('search').'%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('username', 'ilike', $term)
                        ->orWhere('email', 'ilike', $term);
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        // Nama lengkap ada di tabel profil (teachers/staff/students), bukan
        // di tabel users. Ambil sekaligus (3 query, bukan N+1) buat semua
        // user_id di halaman ini, lalu cocokkan siapa pemilik profil yang
        // mana — daripada nebak role mana masuk tabel mana.
        $userIds = $users->pluck('id');

        $teacherNames = Teacher::query()->whereIn('user_id', $userIds)->pluck('full_name', 'user_id');
        $staffNames = Staff::query()->whereIn('user_id', $userIds)->pluck('full_name', 'user_id');
        $studentNames = Student::query()->whereIn('user_id', $userIds)->pluck('full_name', 'user_id');

        $schoolNames = School::query()
            ->whereIn('id', $users->pluck('school_id')->filter())
            ->pluck('name', 'id');

        foreach ($users as $user) {
            $user->display_name = $teacherNames[$user->id]
                ?? $staffNames[$user->id]
                ?? $studentNames[$user->id]
                ?? $user->username
                ?? $user->email
                ?? '—';

            $user->school_name = $user->school_id ? ($schoolNames[$user->school_id] ?? '—') : null;
        }

        return view('superadmin.users.index', [
            'users' => $users,
            'roleLabels' => self::ROLE_LABELS,
            'schools' => School::orderBy('name')->pluck('name', 'id'),
            'search' => $request->input('search', ''),
            'schoolFilter' => $request->input('school', ''),
            'roleFilter' => $request->input('role', ''),
        ]);
    }
}
