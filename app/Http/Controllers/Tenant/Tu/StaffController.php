<?php

namespace App\Http\Controllers\Tenant\Tu;

use App\Exceptions\SensitiveDataEncryptionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Tu\StoreStaffRequest;
use App\Http\Requests\Tenant\Tu\UpdateStaffRequest;
use App\Models\Staff;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * CRUD Data Staff, disalin persis dari pola StudentController/TeacherController
 * — lihat komentar di StudentController untuk detail desain lengkap. Skema
 * kredensial: username = NIP kalau diisi, fallback slug nama; password =
 * DDMMYYYY. Role user dibuat: sesuai posisi staff (default 'tu' kalau tidak
 * diketahui — TU sendiri yang membuat, jadi umumnya bukan role administratif).
 */
class StaffController extends Controller
{
    public const GENDERS = ['L' => 'Laki-laki', 'P' => 'Perempuan'];

    private const SENSITIVE_FIELDS = ['nip', 'birth_place', 'birth_date', 'religion', 'address', 'phone'];

    public function index(Request $request): View
    {
        $staff = Staff::query()
            ->when($request->filled('search'), fn ($q) => $q->where('full_name', 'ilike', "%{$request->input('search')}%"))
            ->when($request->has('active'), fn ($q) => $q->where('is_active', $request->boolean('active')))
            ->orderBy('full_name')
            ->paginate(15)
            ->withQueryString();

        return view('tenant.tu.staff.index', [
            'staffs' => $staff,
            'search' => $request->input('search', ''),
            'activeFilter' => $request->input('active', ''),
        ]);
    }

    public function create(): View
    {
        return view('tenant.tu.staff.create', [
            'genders' => self::GENDERS,
        ]);
    }

    public function store(StoreStaffRequest $request): RedirectResponse
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
            'email' => "{$username}@staff.eduzone.local",
            'role' => 'toolman',
            'password' => bcrypt($plainPassword),
            'is_active' => true,
        ]);

        $staff = new Staff();
        $staff->school_id = $schoolId;
        $staff->user_id = $user->id;
        $this->fillNonSensitive($staff, $validated);
        $this->fillSensitive($staff, $validated);
        $this->handlePhotoUpload($staff, $request);

        try {
            $staff->save();
        } catch (SensitiveDataEncryptionException $e) {
            $user->delete();

            return back()->withInput()->withErrors([
                'general' => 'Data staff gagal disimpan karena layanan enkripsi data sensitif (NIP, alamat, dll) sedang tidak tersedia. Coba lagi sesaat lagi, atau hubungi admin kalau berlanjut.',
            ]);
        }

        return redirect()
            ->route('tu.staff.index')
            ->with('success', "Data staff \"{$staff->full_name}\" berhasil ditambahkan. Akun login — Username: {$username}, Password: {$plainPassword} (catat sekarang, tidak ditampilkan lagi).");
    }

    public function edit(Staff $staff): View
    {
        return view('tenant.tu.staff.edit', [
            'staff' => $staff,
            'genders' => self::GENDERS,
        ]);
    }

    public function update(UpdateStaffRequest $request, Staff $staff): RedirectResponse
    {
        $validated = $request->validated();

        $this->fillNonSensitive($staff, $validated);
        $this->fillSensitive($staff, $validated);
        $this->handlePhotoUpload($staff, $request);

        try {
            $staff->save();
        } catch (SensitiveDataEncryptionException $e) {
            return back()->withInput()->withErrors([
                'general' => 'Data pokok tersimpan, tapi data sensitif (NIP, alamat, dll) gagal disimpan karena layanan enkripsi sedang tidak tersedia. Coba lagi, atau hubungi admin kalau berlanjut.',
            ]);
        }

        return redirect()
            ->route('tu.staff.index')
            ->with('success', "Data staff \"{$staff->full_name}\" berhasil diperbarui.");
    }

    public function destroy(Staff $staff): RedirectResponse
    {
        try {
            $userId = $staff->user_id;
            $staff->delete();
            User::where('id', $userId)->delete();
        } catch (QueryException $e) {
            return redirect()
                ->route('tu.staff.index')
                ->with('error', "Data staff \"{$staff->full_name}\" tidak bisa dihapus permanen karena masih punya data terkait. Nonaktifkan lewat Edit sebagai gantinya.");
        }

        return redirect()
            ->route('tu.staff.index')
            ->with('success', "Data staff \"{$staff->full_name}\" berhasil dihapus.");
    }

    private function fillNonSensitive(Staff $staff, array $validated): void
    {
        $staff->full_name = $validated['full_name'];
        $staff->email = $validated['email'] ?? null;
        $staff->gender = $validated['gender'];
        $staff->position = $validated['position'] ?? null;
        $staff->joined_date = $validated['joined_date'] ?? null;
        $staff->is_active = $validated['is_active'];
    }

    private function fillSensitive(Staff $staff, array $validated): void
    {
        foreach (self::SENSITIVE_FIELDS as $field) {
            if (array_key_exists($field, $validated)) {
                $staff->{$field} = $validated[$field];
            }
        }
    }

    private function handlePhotoUpload(Staff $staff, Request $request): void
    {
        if (! $request->hasFile('photo')) {
            return;
        }

        if ($staff->photo) {
            Storage::disk('private_photos')->delete($staff->photo);
        }

        $staff->photo = $request->file('photo')->store('', 'private_photos');
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