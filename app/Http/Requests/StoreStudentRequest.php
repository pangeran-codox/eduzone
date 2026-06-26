<?php

namespace App\Http\Requests;

use App\Rules\UniqueHashedField;
use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'full_name' => ['required', 'string', 'max:100'],
            'gender' => ['required', 'in:L,P'],
            'grade' => ['nullable', 'in:X,XI,XII'],
            'major_id' => ['nullable', 'uuid', 'exists:majors,id'],
            'class_id' => ['nullable', 'uuid', 'exists:classes,id'],
            'status' => ['nullable', 'in:aktif,lulus,pindah,drop_out'],

            // Field sensitif - validasi format & uniqueness pakai hash, bukan kolom asli
            'nisn' => [
                'required',
                'string',
                'size:10', // NISN standar 10 digit
                new UniqueHashedField('students', 'nisn_hash'),
            ],
            'nis' => [
                'nullable',
                'string',
                new UniqueHashedField('students', 'nis_hash'),
            ],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:15'],
            'email' => ['nullable', 'email'],
            'birth_place' => ['nullable', 'string', 'max:50'],
            'birth_date' => ['nullable', 'date'],
            'religion' => ['nullable', 'string', 'max:30'],
            'father_name' => ['nullable', 'string', 'max:100'],
            'mother_name' => ['nullable', 'string', 'max:100'],
            'father_job' => ['nullable', 'string', 'max:100'],
            'mother_job' => ['nullable', 'string', 'max:100'],
            'parent_address' => ['nullable', 'string'],
            'parent_phone' => ['nullable', 'string', 'max:15'],
        ];
    }

    public function messages(): array
    {
        return [
            'nisn.size' => 'NISN harus terdiri dari 10 digit.',
        ];
    }
}