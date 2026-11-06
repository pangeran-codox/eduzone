<?php

namespace App\Http\Requests\Superadmin;

use Illuminate\Foundation\Http\FormRequest;

class RejectSchoolRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses dibatasi middleware `superadmin` di group route
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return ['reason' => 'alasan penolakan'];
    }
}