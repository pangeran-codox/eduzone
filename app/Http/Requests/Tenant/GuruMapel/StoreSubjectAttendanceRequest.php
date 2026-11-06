<?php

namespace App\Http\Requests\Tenant\GuruMapel;

use App\Http\Controllers\Tenant\GuruMapel\SesiAbsensiController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi bentuk data saja. Kewenangan guru (pemilik jadwal / pengganti hari ini) dan kecocokan
 * daftar murid dengan kelas diperiksa di SesiAbsensiController, karena butuh data jadwal.
 */
class StoreSubjectAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses dibatasi middleware role di group route + pemeriksaan di controller
    }

    public function rules(): array
    {
        return [
            'topic' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'students' => ['required', 'array', 'min:1'],
            'students.*.status' => ['required', 'string', Rule::in(SesiAbsensiController::STATUSES)],
            'students.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'students.required' => 'Tidak ada murid yang dikirim. Muat ulang halaman lalu coba lagi.',
            'students.*.status.required' => 'Status setiap murid wajib dipilih.',
            'students.*.status.in' => 'Status murid tidak valid.',
        ];
    }

    public function attributes(): array
    {
        return [
            'topic' => 'topik pembelajaran',
            'notes' => 'catatan sesi',
        ];
    }
}
