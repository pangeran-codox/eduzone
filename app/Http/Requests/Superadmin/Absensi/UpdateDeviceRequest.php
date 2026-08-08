<?php

namespace App\Http\Requests\Superadmin\Absensi;

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
        return [
            'school_id' => ['required', 'uuid', 'exists:pgsql_absensi.school_refs,school_id'],
            // device_code SENGAJA tidak boleh diubah di sini — sudah dipakai
            // sebagai bagian URL kiosk (/kiosk/{deviceCode}) yang mungkin
            // sudah di-bookmark/di-setting di device fisik. Kalau memang
            // perlu ganti kode, lebih aman hapus device lama + buat baru.
            'name' => ['required', 'string', 'max:255'],
            'device_type' => ['required', Rule::in([
                'face_camera', 'rfid_reader', 'qr_scanner', 'hybrid', 'manual_kiosk',
            ])],
            'location' => ['nullable', 'string', 'max:255'],
            'default_class_id' => ['nullable', 'uuid'],
            'ip_address' => ['nullable', 'ip'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'school_id.exists' => 'Sekolah yang dipilih tidak ditemukan (belum tersinkron ke layanan absensi).',
            'device_type.in' => 'Tipe device tidak valid.',
            'default_class_id.uuid' => 'ID kelas default harus berupa UUID yang valid.',
        ];
    }
}
