<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Superadmin\StoreSchoolRequest;
use App\Http\Requests\Superadmin\UpdateSchoolRequest;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SchoolController extends Controller
{
    public const LEVELS = ['SD', 'SMP', 'SMA', 'SMK'];
    public const STATUSES = ['Negeri', 'Swasta'];
    public const ACCREDITATIONS = ['A', 'B', 'C', 'Belum Terakreditasi'];
    public const PLANS = ['trial' => 'Trial', 'basic' => 'Basic', 'pro' => 'Pro'];

    public function index(Request $request): View
    {
        $schools = School::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = "%{$request->input('search')}%";
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'ilike', $term)
                        ->orWhere('npsn', 'ilike', $term)
                        ->orWhere('city', 'ilike', $term);
                });
            })
            ->when($request->filled('plan'), fn ($query) => $query->where('subscription_plan', $request->input('plan')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('superadmin.schools.index', [
            'schools' => $schools,
            'plans' => self::PLANS,
            'search' => $request->input('search', ''),
            'planFilter' => $request->input('plan', ''),
        ]);
    }

    public function create(): View
    {
        return view('superadmin.schools.create', [
            'levels' => self::LEVELS,
            'statuses' => self::STATUSES,
            'accreditations' => self::ACCREDITATIONS,
            'plans' => self::PLANS,
        ]);
    }

    public function store(StoreSchoolRequest $request): RedirectResponse
    {
        $school = School::create($request->validated());

        return redirect()
            ->route('superadmin.schools.index')
            ->with('success', "Sekolah \"{$school->name}\" berhasil ditambahkan.");
    }

    public function edit(School $school): View
    {
        return view('superadmin.schools.edit', [
            'school' => $school,
            'levels' => self::LEVELS,
            'statuses' => self::STATUSES,
            'accreditations' => self::ACCREDITATIONS,
            'plans' => self::PLANS,
        ]);
    }

    public function update(UpdateSchoolRequest $request, School $school): RedirectResponse
    {
        $school->update($request->validated());

        return redirect()
            ->route('superadmin.schools.index')
            ->with('success', "Sekolah \"{$school->name}\" berhasil diperbarui.");
    }

    /**
     * Hapus sekolah. Dibungkus try/catch karena sekolah dengan relasi aktif
     * (users, students, teachers, dll — lihat App\Models\School::users()
     * dkk) kemungkinan besar dibatasi foreign key constraint. Kalau gagal,
     * arahkan ke nonaktifkan saja (is_active = false) lewat Edit, jangan
     * tampilkan stack trace SQL mentah.
     */
    public function destroy(School $school): RedirectResponse
    {
        try {
            $school->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()
                ->route('superadmin.schools.index')
                ->with('error', "Sekolah \"{$school->name}\" tidak bisa dihapus permanen karena masih punya data terkait (user/siswa/dll). Nonaktifkan saja lewat Edit (set Status ke Nonaktif).");
        }

        return redirect()
            ->route('superadmin.schools.index')
            ->with('success', "Sekolah \"{$school->name}\" berhasil dihapus.");
    }
}
