<?php

namespace App\Http\Requests\Guest;

use App\Http\Controllers\Superadmin\SchoolController;
use App\Models\SchoolRegistration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSchoolRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->filled('applicant_email')) {
            $merge['applicant_email'] = strtolower(trim((string) $this->input('applicant_email')));
        }

        if ($this->filled('npsn')) {
            $merge['npsn'] = preg_replace('/\s+/', '', (string) $this->input('npsn'));
        }

        $this->merge($merge);
    }

    public function rules(): array
    {
        // Pengajuan yang masih "hidup" (sama dengan kondisi partial unique index di migration)
        $active = [
            SchoolRegistration::STATUS_PENDING_EMAIL,
            SchoolRegistration::STATUS_PENDING_APPROVAL,
        ];

        return [
            'school_name' => ['required', 'string', 'max:255'],
            'npsn' => [
                'required', 'digits:8',
                Rule::unique('school_registrations', 'npsn')->whereIn('status', $active),
                Rule::unique('schools', 'npsn'),
            ],
            'level' => ['required', Rule::in(SchoolController::LEVELS)],
            'address' => ['required', 'string', 'max:1000'],
            'village' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'school_phone' => ['nullable', 'string', 'max:30'],

            'applicant_name' => ['required', 'string', 'max:255'],
            // max:100 karena users.email hanya 100 karakter
            'applicant_email' => [
                'required', 'email:rfc', 'max:100',
                Rule::unique('school_registrations', 'applicant_email')->whereIn('status', $active),
                // LoginController mencoba email dulu dan hanya memeriksa user PERTAMA dengan email itu,
                // sedangkan users.email tidak terbukti unik: hindari tabrakan dengan akun yang sudah ada.
                Rule::unique('users', 'email'),
            ],
            'applicant_phone' => ['nullable', 'string', 'max:30'],
            'applicant_position' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'npsn.unique' => 'NPSN ini sudah terdaftar atau sedang dalam proses pendaftaran.',
            'npsn.digits' => 'NPSN harus terdiri dari 8 digit angka.',
            'applicant_email.unique' => 'Email ini sudah terdaftar atau sedang dalam proses pendaftaran. Gunakan email lain.',
        ];
    }

    public function attributes(): array
    {
        return [
            'school_name' => 'nama sekolah',
            'npsn' => 'NPSN',
            'level' => 'jenjang',
            'address' => 'alamat',
            'city' => 'kota/kabupaten',
            'province' => 'provinsi',
            'applicant_name' => 'nama pendaftar',
            'applicant_email' => 'email pendaftar',
        ];
    }
}
