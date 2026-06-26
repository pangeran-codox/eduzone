<?php

namespace App\Http\Requests;

use App\Rules\UniqueHashedField;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Ambil student dari route parameter, misal Route::put('/students/{student}', ...)
        $student = $this->route('student');

        return [
            'full_name' => ['sometimes', 'string', 'max:100'],
            'gender' => ['sometimes', 'in:L,P'],
            'status' => ['sometimes', 'in:aktif,lulus,pindah,drop_out'],

            'nisn' => [
                'sometimes',
                'string',
                'size:10',
                (new UniqueHashedField('students', 'nisn_hash'))->ignore($student->id),
            ],
            'nis' => [
                'sometimes',
                'nullable',
                'string',
                (new UniqueHashedField('students', 'nis_hash'))->ignore($student->id),
            ],
            'address' => ['sometimes', 'nullable', 'string'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:15'],
            // ... field lain sama seperti StoreStudentRequest, pakai 'sometimes'
        ];
    }
}