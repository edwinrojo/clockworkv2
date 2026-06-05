<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VerifyEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $length = (int) config('clockwork.email_verification_code_length', 6);

        return [
            'email' => ['required', 'string', 'email'],
            'code' => ['required', 'string', 'digits:'.$length],
        ];
    }
}
