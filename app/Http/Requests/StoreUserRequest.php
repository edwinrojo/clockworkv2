<?php

namespace App\Http\Requests;

use App\Concerns\PasswordValidationRules;
use App\Http\Requests\Concerns\ValidatesManagedUser;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    use PasswordValidationRules, ValidatesManagedUser;

    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->managedUserRules(),
            'password' => $this->passwordRules(),
        ];
    }
}
