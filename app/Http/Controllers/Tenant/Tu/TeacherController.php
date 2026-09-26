<?php

namespace App\Http\Controllers\Tenant\Tu;

use App\Exceptions\SensitiveDataEncryptionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Tu\StoreTeacherRequest;
use App\Http\Requests\Tenant\Tu\UpdateTeacherRequest;
use App\Models\Major;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * CRUD Data Guru, disalin persis dari pola StudentController (auto-provisioning
 * akun, penanganan gagal enkripsi, photo upload private) — lihat komentar di
 * sana untuk detail desain lengkap. Bedanya cuma field & skema kredensial:
 * username = NIP kalau diisi, fallback slug nama; password = DDMMYYYY.
 */
class TeacherController extends Controller
{
    public const GENDERS = ['L' => 'Laki-laki', 'P' => 'Perempuan'];
    public const EMPLOYMENT_STATUSES = ['PNS', 'PPPK', 'Honorer', 'GTY', 'GTT'];

    private const SENSITIVE_FIELDS = ['nip', 'nuptk', 'birth_place', 'birth_date', 'religion', 'address', 'phone'];

    public function index(Request $request): View
    {
        $teachers = Teacher::query()
            ->with('major')
            ->when($request->filled('search'), fn ($q) => $q->where('full_name', 'ilike', "%{$request->input('search')}%"))
            ->when($request->has('active'), fn ($q) => $q->where('is_active', $request->boolean('active')))
            ->orderBy('full_name')
            ->paginate(15)
            ->withQueryString();

        return view('tenant.tu.guru.index', [
            'teachers' => $teachers,
            'search' => $request->input('search', ''),
            'activeFilter' => $request->input('active', ''),
        ]);
    }

    public function create(): View
    {
        return view('tenant.tu.guru.create', [
            'majors' => Major::where('is_active', true)->orderBy('name')->get(),
            'genders' => self::GENDERS,
            'employmentStatuses' => self::EMPLOYMENT_STATUSES,
        ]);
    }

    public function store(StoreTeacherRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $schoolId = $request->user()->school_id;

        $username = $this->generateUsername($validated['nip'] ?? null, $validated['full_name']);
        $plainPassword = ! empty($validated['birth_date'])
            ? Carbon::parse($validated['birth_date'])->format('dmY')
            : 'eduzone123';

        $user = User::create([
            'school_id' => $schoolId,
            'username' => $username,
            'email' => "{$username}@guru.eduzone.local",
            'role' => 'guru_mapel',
            'password' => bcrypt($plainPassword),
            'is_active' => true,
        ]);

        $teacher = new Teacher();
        $teacher->school_id = $schoolId;
        $teacher->user_id = $user->id;
        $this->fillNonSensitive($teacher, $validated);
        $this->fillSensitive($teacher, $validated);
        $this->handlePhotoUpload($teacher, $request);

        try {
            $teacher->save();
        } catch (SensitiveDataEncryptionException $e) {
            $user->delete();

            return back()->withInput()->withErrors([
                'general' => 'Data guru gagal disimpan karena layanan enkripsi data sensitif (NIP, alamat, dll) sedang tidak tersedia. Coba lagi sesaat lagi, atau hubungi admin kalau berlanjut.',
            ]);
        }

        return redirect()
            ->route('tu.guru.index')
            ->with('success', "Data guru \"{$teacher->full_name}\" berhasil ditambahkan. Akun login — Username: {$username}, Password: {$plainPassword} (catat sekarang, tidak ditampilkan lagi).");
    }

    public function edit(Teacher $teacher): View
    {
        return view('tenant.tu.guru.edit', [
            'teacher' => $teacher,
            'majors' => Major::where('is_active', true)->orderBy('name')->get(),
            'genders' => self::GENDERS,
            'employmentStatuses' => self::EMPLOYMENT_STATUSES,
        ]);
    }

    public function update(UpdateTeacherRequest $request, Teacher $teacher): RedirectResponse
    {
        $validated = $request->validated();

        $this->fillNonSensitive($teacher, $validated);
        $this->fillSensitive($teacher, $validated);
        $this->handlePhotoUpload($teacher, $request);

        try {
            $teacher->save();
        } catch (SensitiveDataEncryptionException $e) {
            return back()->withInput()->withErrors([
                'general' => 'Data pokok tersimpan, tapi data sensitif (NIP, alamat, dll) gagal disimpan karena layanan enkripsi sedang tidak tersedia. Coba lagi, atau hubungi admin kalau berlanjut.',
            ]);
        }

        return redirect()
            ->route('tu.guru.index')
            ->with('success', "Data guru \"{$teacher->full_name}\" berhasil diperbarui.");
    }

    public function destroy(Teacher $teacher): RedirectResponse
    {
        try {
            $userId = $teacher->user_id;
            $teacher->delete();
            User::where('id', $userId)->delete();
        } catch (QueryException $e) {
            return redirect()
                ->route('tu.guru.index')
                ->with('error', "Data guru \"{$teacher->full_name}\" tidak bisa dihapus permanen karena masih punya data terkait (jadwal/absensi/dll). Nonaktifkan lewat Edit sebagai gantinya.");
        }

        return redirect()
            ->route('tu.guru.index')
            ->with('success', "Data guru \"{$teacher->full_name}\" berhasil dihapus.");
    }

    private function fillNonSensitive(Teacher $teacher, array $validated): void
    {
        $teacher->full_name = $validated['full_name'];
        $teacher->email = $validated['email'] ?? null;
        $teacher->gender = $validated['gender'];
        $teacher->last_education = $validated['last_education'] ?? null;
        $teacher->education_major = $validated['education_major'] ?? null;
        $teacher->employment_status = $validated['employment_status'] ?? null;
        $teacher->joined_date = $validated['joined_date'] ?? null;
        $teacher->major_id = $validated['major_id'] ?? null;
        $teacher->is_homeroom = $validated['is_homeroom'] ?? false;
        $teacher->is_active = $validated['is_active'];
    }

    private function fillSensitive(Teacher $teacher, array $validated): void
    {
        foreach (self::SENSITIVE_FIELDS as $field) {
            if (array_key_exists($field, $validated)) {
                $teacher->{$field} = $validated[$field];
            }
        }
    }

    private function handlePhotoUpload(Teacher $teacher, Request $request): void
    {
        if (! $request->hasFile('photo')) {
            return;
        }

        if ($teacher->photo) {
            Storage::disk('private_photos')->delete($teacher->photo);
        }

        $teacher->photo = $request->file('photo')->store('', 'private_photos');
    }

    private function generateUsername(?string $nip, string $fullName): string
    {
        if (! empty($nip)) {
            return $nip;
        }

        $base = Str::slug($fullName, '.');
        $username = $base;
        $suffix = 1;

        while (User::withoutGlobalScopes()->where('username', $username)->exists()) {
            $username = "{$base}{$suffix}";
            $suffix++;
        }

        return $username;
    }
}