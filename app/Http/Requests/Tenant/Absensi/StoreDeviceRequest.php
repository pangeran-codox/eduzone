<?php

namespace App\Http\Requests\Tenant\Absensi;

use App\Models\Absensi\Device;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role:kepsek,tu sudah dicek middleware route
    }

    public function rules(): array
    {
        $schoolId = $this->user()->school_id;

        return [
            'device_code' => [
                'required', 'string', 'max:50',
                Rule::unique('pgsql_absensi.devices', 'device_code')
                    ->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'capabilities' => ['required', 'array', 'min:1'],
            'capabilities.*' => [Rule::in(array_keys(Device::CAPABILITIES))],
            'location' => ['nullable', 'string', 'max:255'],
            'default_class_id' => [
                'nullable', 'uuid',
                Rule::exists('classes', 'id')->where('school_id', $schoolId),
            ],
            'ip_address' => ['nullable', 'ip'],
        ];
    }

    public function messages(): array
    {
        return [
            'device_code.unique' => 'Kode device ini sudah dipakai di sekolah Anda, pakai kode lain.',
            'capabilities.required' => 'Pilih minimal 1 metode input.',
            'capabilities.min' => 'Pilih minimal 1 metode input.',
            'default_class_id.exists' => 'Kelas yang dipilih tidak ditemukan.',
        ];
    }
}