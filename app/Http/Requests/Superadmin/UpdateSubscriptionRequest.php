<?php

namespace App\Http\Requests\Superadmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan' => ['required', Rule::in(['trial', 'basic', 'pro'])],
            'started_at' => ['required', 'date'],
            'expired_at' => ['required', 'date', 'after:started_at'],
            'amount' => ['required', 'numeric', 'min:0'],
            'invoice_no' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'expired', 'cancelled'])],
            'note' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'expired_at.after' => 'Tanggal berakhir harus setelah tanggal mulai.',
        ];
    }
}
