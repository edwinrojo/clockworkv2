<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesManagedUser;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    use ValidatesManagedUser;

    public function authorize(): bool
    {
        /** @var User $managedUser */
        $managedUser = $this->route('user');

        return $this->user()->can('update', $managedUser);
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
        /** @var User $managedUser */
        $managedUser = $this->route('user');

        return [
            ...$this->managedUserRules($managedUser),
            'password' => $this->optionalPasswordRules(),
        ];
    }
}
