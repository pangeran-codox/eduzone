<?php

namespace App\Http\Requests\Tenant\Absensi;

use App\Models\Absensi\Device;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $schoolId = $this->user()->school_id;

        return [
            // device_code SENGAJA tidak bisa diubah — sudah jadi bagian
            // URL kiosk (/kiosk/{deviceCode}).
            'name' => ['required', 'string', 'max:255'],
            'capabilities' => ['required', 'array', 'min:1'],
            'capabilities.*' => [Rule::in(array_keys(Device::CAPABILITIES))],
            'location' => ['nullable', 'string', 'max:255'],
            'default_class_id' => [
                'nullable', 'uuid',
                Rule::exists('classes', 'id')->where('school_id', $schoolId),
            ],
            'ip_address' => ['nullable', 'ip'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'capabilities.required' => 'Pilih minimal 1 metode input.',
            'capabilities.min' => 'Pilih minimal 1 metode input.',
            'default_class_id.exists' => 'Kelas yang dipilih tidak ditemukan.',
        ];
    }
}