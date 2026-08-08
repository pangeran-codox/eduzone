<?php

namespace App\Http\Requests\Superadmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $schoolId = $this->route('school')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('schools', 'slug')->ignore($schoolId)],
            'npsn' => ['nullable', 'string', 'max:20', Rule::unique('schools', 'npsn')->ignore($schoolId)],
            'nss' => ['nullable', 'string', 'max:20'],
            'level' => ['nullable', Rule::in(['SD', 'SMP', 'SMA', 'SMK'])],
            'status' => ['nullable', Rule::in(['Negeri', 'Swasta'])],
            'accreditation' => ['nullable', Rule::in(['A', 'B', 'C', 'Belum Terakreditasi'])],

            'address' => ['nullable', 'string'],
            'village' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:10'],

            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],

            'principal_name' => ['nullable', 'string', 'max:255'],
            // Lihat catatan sama di StoreSchoolRequest soal principal_nip
            // dkk yang sengaja di-skip karena butuh gRPC yang belum aktif.

            'vision' => ['nullable', 'string'],
            'mission' => ['nullable', 'string'],
            'motto' => ['nullable', 'string', 'max:255'],

            'bank_name' => ['nullable', 'string', 'max:255'],

            'subscription_plan' => ['required', Rule::in(['trial', 'basic', 'pro'])],
            'subscription_until' => ['nullable', 'date'],
            'max_users' => ['required', 'integer', 'min:1'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique' => 'Slug ini sudah dipakai sekolah lain, coba ubah.',
            'npsn.unique' => 'NPSN ini sudah terdaftar untuk sekolah lain.',
            'slug.alpha_dash' => 'Slug cuma boleh huruf, angka, strip, dan underscore.',
        ];
    }
}
