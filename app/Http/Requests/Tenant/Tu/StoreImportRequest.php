<?php

namespace App\Http\Requests\Tenant\Tu;

use App\Services\Import\ImportRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dibatasi middleware role:tu di group route
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(array_keys(ImportRegistry::all()))],
            // `extensions` memeriksa nama file; isi file sebenarnya diperiksa saat dibaca (ImportSpreadsheet::read).
            'file' => ['required', 'file', 'max:2048', 'extensions:xlsx'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'Jenis impor tidak dikenal.',
            'file.required' => 'Pilih file Excel (.xlsx) terlebih dahulu.',
            'file.max' => 'Ukuran file maksimal 2 MB. Pecah menjadi beberapa file bila perlu.',
            'file.extensions' => 'File harus berformat .xlsx. Gunakan template dari menu Impor Data.',
            'file.uploaded' => 'File gagal diunggah. Coba lagi.',
        ];
    }
}
