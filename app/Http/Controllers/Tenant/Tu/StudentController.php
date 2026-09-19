<?php

namespace App\Http\Controllers\Tenant\Tu;

use App\Exceptions\SensitiveDataEncryptionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Tu\StoreStudentRequest;
use App\Http\Requests\Tenant\Tu\UpdateStudentRequest;
use App\Models\Major;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

class StudentController extends Controller
{
    public const STATUSES = ['aktif' => 'Aktif', 'pindah' => 'Pindah', 'lulus' => 'Lulus', 'keluar' => 'Keluar'];
    public const GENDERS = ['L' => 'Laki-laki', 'P' => 'Perempuan'];

    private const SENSITIVE_FIELDS = [
        'nis', 'nisn', 'birth_place', 'birth_date', 'religion', 'address', 'phone',
        'father_name', 'mother_name', 'father_job', 'mother_job', 'parent_address', 'parent_phone',
    ];

    public function index(Request $request): View
    {
        $students = Student::query()
            ->with('class')
            ->when($request->filled('search'), fn ($q) => $q->where('full_name', 'ilike', "%{$request->input('search')}%"))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderBy('full_name')
            ->paginate(15)
            ->withQueryString();

        return view('tenant.tu.siswa.index', [
            'students' => $students,
            'statuses' => self::STATUSES,
            'search' => $request->input('search', ''),
            'statusFilter' => $request->input('status', ''),
        ]);
    }

    public function create(): View
    {
        return view('tenant.tu.siswa.create', [
            'majors' => Major::where('is_active', true)->orderBy('name')->get(),
            'classes' => SchoolClass::where('is_active', true)->orderBy('nama_kelas')->get(),
            'statuses' => self::STATUSES,
            'genders' => self::GENDERS,
        ]);
    }

    /**
     * students.user_id NOT NULL - setiap siswa WAJIB punya akun User.
     * Dibuat otomatis di sini (role 'siswa'), TU tidak perlu isi form
     * akun terpisah. Skema kredensial default (disepakati 8 Sep 2026):
     *   - username = NISN kalau diisi, fallback slug nama + angka urut
     *   - password = tanggal lahir format DDMMYYYY
     * Ditampilkan SEKALI ke TU lewat pesan sukses setelah simpan -
     * TIDAK disimpan di tempat lain selain hash di kolom password.
     */
    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $schoolId = $request->user()->school_id;

        $username = $this->generateUsername($validated['nisn'] ?? null, $validated['full_name']);
        $plainPassword = Carbon::parse($validated['birth_date'])->format('dmY');

        $user = User::create([
            'school_id' => $schoolId,
            'username' => $username,
            'email' => "{$username}@siswa.eduzone.local",
            'role' => 'siswa',
            'password' => bcrypt($plainPassword),
            'is_active' => true,
        ]);

        $student = new Student();
        $student->school_id = $schoolId;
        $student->user_id = $user->id;
        $this->fillNonSensitive($student, $validated);
        $this->fillSensitive($student, $validated);
        $this->handlePhotoUpload($student, $request);

        try {
            $student->save();
        } catch (SensitiveDataEncryptionException $e) {
            $user->delete();

            return back()->withInput()->withErrors([
                'general' => 'Data siswa gagal disimpan karena layanan enkripsi data sensitif (NISN, alamat, dll) sedang tidak tersedia. Coba lagi sesaat lagi, atau hubungi admin kalau berlanjut.',
            ]);
        }

        return redirect()
            ->route('tu.siswa.index')
            ->with('success', "Data siswa \"{$student->full_name}\" berhasil ditambahkan. Akun login — Username: {$username}, Password: {$plainPassword} (catat sekarang, tidak ditampilkan lagi).");
    }

    public function edit(Student $student): View
    {
        return view('tenant.tu.siswa.edit', [
            'student' => $student,
            'majors' => Major::where('is_active', true)->orderBy('name')->get(),
            'classes' => SchoolClass::where('is_active', true)->orderBy('nama_kelas')->get(),
            'statuses' => self::STATUSES,
            'genders' => self::GENDERS,
        ]);
    }

    /**
     * Update TIDAK menyentuh akun User/password sama sekali - siswa yang
     * sudah pernah ganti password sendiri tidak boleh ke-reset diam-diam
     * cuma karena TU edit data lain (mis. ganti kelas).
     */
    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $validated = $request->validated();

        $this->fillNonSensitive($student, $validated);
        $this->fillSensitive($student, $validated);
        $this->handlePhotoUpload($student, $request);

        try {
            $student->save();
        } catch (SensitiveDataEncryptionException $e) {
            return back()->withInput()->withErrors([
                'general' => 'Data pokok tersimpan, tapi data sensitif (NISN, alamat, dll) gagal disimpan karena layanan enkripsi sedang tidak tersedia. Coba lagi, atau hubungi admin kalau berlanjut.',
            ]);
        }

        return redirect()
            ->route('tu.siswa.index')
            ->with('success', "Data siswa \"{$student->full_name}\" berhasil diperbarui.");
    }

    /**
     * Hapus Student SEKALIGUS User terkait (siswa tanpa akun = data
     * yatim yang melanggar constraint NOT NULL kalau ada yang lain
     * kebetulan masih reference user_id ini).
     */
    public function destroy(Student $student): RedirectResponse
    {
        try {
            $userId = $student->user_id;
            $student->delete();
            User::where('id', $userId)->delete();
        } catch (QueryException $e) {
            return redirect()
                ->route('tu.siswa.index')
                ->with('error', "Data siswa \"{$student->full_name}\" tidak bisa dihapus permanen karena masih punya data terkait (nilai/absensi/dll). Ubah status jadi \"Keluar\" lewat Edit sebagai gantinya.");
        }

        return redirect()
            ->route('tu.siswa.index')
            ->with('success', "Data siswa \"{$student->full_name}\" berhasil dihapus.");
    }

    private function fillNonSensitive(Student $student, array $validated): void
    {
        $student->full_name = $validated['full_name'];
        $student->email = $validated['email'] ?? null;
        $student->gender = $validated['gender'];
        $student->grade = $validated['grade'] ?? null;
        $student->major_id = $validated['major_id'] ?? null;
        $student->class_group = $validated['class_group'] ?? null;
        $student->class_id = $validated['class_id'] ?? null;
        $student->joined_date = $validated['joined_date'] ?? null;
        $student->status = $validated['status'];
    }

    private function fillSensitive(Student $student, array $validated): void
    {
        foreach (self::SENSITIVE_FIELDS as $field) {
            if (array_key_exists($field, $validated)) {
                $student->{$field} = $validated[$field];
            }
        }
    }

    /**
     * Upload foto - disimpan di disk 'private_photos' (BUKAN 'public'),
     * diakses hanya lewat route bertoken (PersonPhotoController). Kalau
     * ada foto lama, dihapus dulu supaya tidak numpuk file yatim di disk.
     */
    private function handlePhotoUpload(Student $student, Request $request): void
    {
        if (! $request->hasFile('photo')) {
            return;
        }

        if ($student->photo) {
            Storage::disk('private_photos')->delete($student->photo);
        }

        $student->photo = $request->file('photo')->store('', 'private_photos');
    }

    /**
     * username = NISN kalau diisi (unik nasional di dunia nyata).
     * Fallback: slug nama lengkap, ditambah angka urut kalau bentrok.
     * Cek keunikan LINTAS SEKOLAH (withoutGlobalScopes) - username dipakai
     * buat Auth::attempt() yang tidak di-scope per sekolah, jadi harus
     * unik secara global supaya tidak ambigu login-nya.
     */
    private function generateUsername(?string $nisn, string $fullName): string
    {
        if (! empty($nisn)) {
            return $nisn;
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