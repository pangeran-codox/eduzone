<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guest\StorePasswordSetupRequest;
use App\Models\SchoolRegistration;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Halaman publik tempat akun Tata Usaha membuat kata sandi pertama lewat link di email approve.
 * Token disimpan sebagai hash di school_registrations (bukan password broker Laravel).
 * Single-use: password_setup_used_at diisi setelah berhasil.
 */
class PasswordSetupController extends Controller
{
    public function show(string $registration, string $token): View
    {
        $model = SchoolRegistration::find($registration);

        return $this->render($this->stateOf($model, $token), $registration, $token);
    }

    public function store(StorePasswordSetupRequest $request, string $registration, string $token): RedirectResponse|View
    {
        $state = DB::transaction(function () use ($request, $registration, $token) {
            // Kunci baris supaya dua submit bersamaan tidak sama-sama lolos.
            $model = SchoolRegistration::query()->whereKey($registration)->lockForUpdate()->first();

            $state = $this->stateOf($model, $token);
            if ($state !== 'form') {
                return $state;
            }

            $user = User::query()->whereKey($model->user_id)->first();
            if (! $user) {
                return 'invalid';
            }

            // Cast `hashed` di model User yang meng-hash kata sandi ini.
            $user->update(['password' => $request->validated('password')]);
            $model->update(['password_setup_used_at' => now()]);

            return 'done';
        });

        if ($state === 'done') {
            // View login menampilkan session('status') di kotak hijau.
            return redirect()->route('login')->with('status', 'Kata sandi berhasil dibuat. Silakan masuk.');
        }

        return $this->render($state, $registration, $token);
    }

    /** form | used | expired | invalid */
    private function stateOf(?SchoolRegistration $model, string $token): string
    {
        $tokenValid = $model
            && $model->password_setup_token_hash
            && hash_equals($model->password_setup_token_hash, hash('sha256', $token));

        if (! $tokenValid || $model->status !== SchoolRegistration::STATUS_APPROVED || ! $model->user_id) {
            return 'invalid';
        }

        if ($model->password_setup_used_at !== null) {
            return 'used';
        }

        if ($model->password_setup_expires_at?->isPast()) {
            return 'expired';
        }

        return 'form';
    }

    private function render(string $state, string $registration, string $token): View
    {
        return view('guest.password-setup.show', [
            'state' => $state,
            'registration' => $registration,
            'token' => $token,
        ]);
    }
}
