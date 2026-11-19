<?php

namespace App\Http\Requests\Guest;

use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

class StorePasswordSetupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // bail: tampilkan satu pesan saja, dimulai dari yang paling mendasar.
            'password' => ['bail', 'required', new StrongPassword, 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'Kata sandi wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
        ];
    }

    public function attributes(): array
    {
        return ['password' => 'kata sandi'];
    }
}
