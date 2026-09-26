<?php

namespace App\Http\Requests\Tenant\Tu;

use App\Rules\UniqueHashedField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $schoolId = $this->user()->school_id;
        $teacherId = $this->route('teacher')->id;

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('teachers', 'email')
                    ->where(fn ($q) => $q->where('school_id', $schoolId))
                    ->ignore($teacherId),
            ],
            'gender' => ['required', Rule::in(['L', 'P'])],
            'last_education' => ['nullable', 'string', 'max:50'],
            'education_major' => ['nullable', 'string', 'max:255'],
            'employment_status' => ['nullable', 'string', 'max:50'],
            'joined_date' => ['nullable', 'date'],
            'major_id' => ['nullable', 'uuid', Rule::exists('majors', 'id')->where(fn ($q) => $q->where('school_id', $schoolId))],
            'is_homeroom' => ['nullable', 'boolean'],
            'is_active' => ['required', 'boolean'],

            'nip' => ['nullable', 'string', 'max:50', (new UniqueHashedField('teachers', 'nip_hash'))->ignore($teacherId)],
            'nuptk' => ['nullable', 'string', 'max:50', (new UniqueHashedField('teachers', 'nuptk_hash'))->ignore($teacherId)],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'religion' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:30'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ];
    }
}