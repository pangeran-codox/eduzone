<?php

namespace App\Http\Controllers\Tenant\Tu;

use App\Exceptions\SensitiveDataEncryptionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Tu\StoreStudentRequest;
use App\Http\Requests\Tenant\Tu\UpdateStudentRequest;
use App\Models\Major;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public const STATUSES = ['aktif' => 'Aktif', 'pindah' => 'Pindah', 'lulus' => 'Lulus', 'keluar' => 'Keluar'];
    public const GENDERS = ['L' => 'Laki-laki', 'P' => 'Perempuan'];

    /**
     * Field sensitif - HARUS sinkron manual dengan
     * Student::$proxiedSensitiveFields (protected, tidak bisa diakses
     * dari sini). Kalau nambah field sensitif baru di model, tambahkan
     * juga di sini + kedua FormRequest.
     */
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

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $student = new Student();
        $student->school_id = $request->user()->school_id;
        $this->fillNonSensitive($student, $validated);
        $this->fillSensitive($student, $validated);

        try {
            $student->save();
        } catch (SensitiveDataEncryptionException $e) {
            return back()->withInput()->withErrors([
                'general' => 'Data siswa gagal disimpan karena layanan enkripsi data sensitif (NISN, alamat, dll) sedang tidak tersedia. Coba lagi sesaat lagi, atau hubungi admin kalau berlanjut.',
            ]);
        }

        return redirect()
            ->route('tu.siswa.index')
            ->with('success', "Data siswa \"{$student->full_name}\" berhasil ditambahkan.");
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

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $validated = $request->validated();

        $this->fillNonSensitive($student, $validated);
        $this->fillSensitive($student, $validated);

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
     * Dibungkus try/catch - siswa dengan relasi aktif (nilai, absensi,
     * prestasi, dll) kemungkinan besar dibatasi foreign key constraint.
     */
    public function destroy(Student $student): RedirectResponse
    {
        try {
            $student->delete();
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

    /**
     * WAJIB assign satu-satu (bukan mass-assignment/create()) - proxy
     * enkripsi di Student::__set() cuma terpicu lewat assignment property
     * langsung, tidak lewat fill()/create(). Lihat catatan di model.
     */
    private function fillSensitive(Student $student, array $validated): void
    {
        foreach (self::SENSITIVE_FIELDS as $field) {
            if (array_key_exists($field, $validated)) {
                $student->{$field} = $validated[$field];
            }
        }
    }
}