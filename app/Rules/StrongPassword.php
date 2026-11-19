<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Kebijakan kata sandi akun sekolah (Tata Usaha memegang data siswa dan guru).
 * Dibuat sebagai rule sendiri, bukan Password::letters()/numbers(), supaya semua pesan
 * berbahasa Indonesia dan tidak bergantung pada file terjemahan.
 */
class StrongPassword implements ValidationRule
{
    public function __construct(private int $min = 10)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || mb_strlen($value) < $this->min) {
            $fail("Kata sandi minimal {$this->min} karakter.");

            return;
        }

        if (! preg_match('/\p{L}/u', $value)) {
            $fail('Kata sandi harus memuat setidaknya satu huruf.');

            return;
        }

        if (! preg_match('/\p{N}/u', $value)) {
            $fail('Kata sandi harus memuat setidaknya satu angka.');

            return;
        }

        // Cek kebocoran lewat Have I Been Pwned (k-anonymity: hanya 5 karakter awal hash yang dikirim).
        // Kalau layanan tidak terjangkau, kata sandi diloloskan supaya form tidak macet.
        $leaked = Validator::make(
            [$attribute => $value],
            [$attribute => [Password::min($this->min)->uncompromised()]],
        )->fails();

        if ($leaked) {
            $fail('Kata sandi ini pernah muncul dalam kebocoran data dan mudah ditebak. Pilih kata sandi lain.');
        }
    }
}
