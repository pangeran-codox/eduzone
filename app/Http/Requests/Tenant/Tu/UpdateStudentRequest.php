<?php

namespace App\Http\Requests\Tenant\Tu;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $schoolId = $this->user()->school_id;
        $studentId = $this->route('student')->id;

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('students', 'email')
                    ->where(fn ($q) => $q->where('school_id', $schoolId))
                    ->ignore($studentId),
            ],
            'gender' => ['required', Rule::in(['L', 'P'])],
            'grade' => ['nullable', 'string', 'max:50'],
            'major_id' => ['nullable', 'uuid', Rule::exists('majors', 'id')->where(fn ($q) => $q->where('school_id', $schoolId))],
            'class_group' => ['nullable', 'string', 'max:50'],
            'class_id' => ['nullable', 'uuid', Rule::exists('classes', 'id')->where(fn ($q) => $q->where('school_id', $schoolId))],
            'joined_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['aktif', 'pindah', 'lulus', 'keluar'])],

            'nis' => ['nullable', 'string', 'max:50', (new \App\Rules\UniqueHashedField('students', 'nis_hash'))->ignore($studentId)],
            'nisn' => ['nullable', 'string', 'max:50', (new \App\Rules\UniqueHashedField('students', 'nisn_hash'))->ignore($studentId)],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['required', 'date'],
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
}