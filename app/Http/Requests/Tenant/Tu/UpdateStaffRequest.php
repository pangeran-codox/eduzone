<?php

namespace App\Http\Requests\Tenant\Tu;

use App\Rules\UniqueHashedField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $schoolId = $this->user()->school_id;
        $staffId = $this->route('staff')->id;

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('staff', 'email')
                    ->where(fn ($q) => $q->where('school_id', $schoolId))
                    ->ignore($staffId),
            ],
            'gender' => ['required', Rule::in(['L', 'P'])],
            'position' => ['nullable', 'string', 'max:255'],
            'joined_date' => ['nullable', 'date'],
            'is_active' => ['required', 'boolean'],

            'nip' => ['nullable', 'string', 'max:50', (new UniqueHashedField('staff', 'nip_hash'))->ignore($staffId)],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'religion' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:30'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ];
    }
}