<?php

namespace App\Http\Requests\Tenant\Tu;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // sudah dibatasi middleware role:tu + tenant di route
    }

    public function rules(): array
    {
        $schoolId = $this->user()->school_id;

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('students', 'email')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'gender' => ['required', Rule::in(['L', 'P'])],
            'grade' => ['nullable', 'string', 'max:50'],
            'major_id' => ['nullable', 'uuid', Rule::exists('majors', 'id')->where(fn ($q) => $q->where('school_id', $schoolId))],
            'class_group' => ['nullable', 'string', 'max:50'],
            'class_id' => ['nullable', 'uuid', Rule::exists('classes', 'id')->where(fn ($q) => $q->where('school_id', $schoolId))],
            'joined_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['aktif', 'pindah', 'lulus', 'keluar'])],

            // Field sensitif - BUKAN kolom asli tabel students (proxy ke
            // student_sensitive_data via magic __set di model Student),
            // tapi validasinya tetap normal karena ini soal input request.
            'nis' => ['nullable', 'string', 'max:50', new \App\Rules\UniqueHashedField('students', 'nis_hash')],
            'nisn' => ['nullable', 'string', 'max:50', new \App\Rules\UniqueHashedField('students', 'nisn_hash')],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'religion' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:30'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'father_job' => ['nullable', 'string', 'max:255'],
            'mother_job' => ['nullable', 'string', 'max:255'],
            'parent_address' => ['nullable', 'string'],
            'parent_phone' => ['nullable', 'string', 'max:30'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ];
    }

    public function attributes(): array
    {
        return [
            'full_name' => 'nama lengkap',
            'nisn' => 'NISN',
            'nis' => 'NIS',
            'birth_place' => 'tempat lahir',
            'birth_date' => 'tanggal lahir',
        ];
    }
}