<?php

namespace App\Http\Requests\Superadmin\Absensi;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Otorisasi sudah ditangani middleware 'superadmin' di route.
        return true;
    }

    public function rules(): array
    {
        return [
            'school_id' => ['required', 'uuid', 'exists:pgsql_absensi.school_refs,school_id'],
            'device_code' => [
                'required', 'string', 'max:50',
                Rule::unique('pgsql_absensi.devices', 'device_code'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'device_type' => ['required', Rule::in([
                'face_camera', 'rfid_reader', 'qr_scanner', 'hybrid', 'manual_kiosk',
            ])],
            'location' => ['nullable', 'string', 'max:255'],
            'default_class_id' => ['nullable', 'uuid'],
            'ip_address' => ['nullable', 'ip'],
        ];
    }

    public function messages(): array
    {
        return [
            'school_id.exists' => 'Sekolah yang dipilih tidak ditemukan (belum tersinkron ke layanan absensi).',
            'device_code.unique' => 'Kode device ini sudah dipakai, pakai kode lain.',
            'device_type.in' => 'Tipe device tidak valid.',
            'default_class_id.uuid' => 'ID kelas default harus berupa UUID yang valid.',
        ];
    }
}
