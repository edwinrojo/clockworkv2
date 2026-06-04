<?php

namespace App\Http\Requests;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SetUserPasswordRequest extends FormRequest
{
    use PasswordValidationRules;

    public function authorize(): bool
    {
        /** @var User $user */
        $user = $this->route('user');

        return $this->user()->can('update', $user) && $user->isEmployee();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => $this->passwordRules(),
        ];
    }
}
