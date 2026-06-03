<?php

namespace App\Http\Requests\Concerns;

use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Models\User;
use App\Support\Admin\AssignableRoles;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

trait ValidatesManagedUser
{
    use ProfileValidationRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function managedUserRules(?User $user = null): array
    {
        $actor = $this->user();

        return [
            ...$this->structuredNameRules(),
            'email' => $this->emailRules($user?->id),
            'role' => ['required', Rule::in(AssignableRoles::valuesFor($actor))],
            'department_id' => [
                Rule::requiredIf(fn (): bool => $this->input('role') === UserRole::Employee->value),
                'nullable',
                'ulid',
                Rule::exists('departments', 'id'),
            ],
            'employee_number' => [
                Rule::requiredIf(fn (): bool => $this->input('role') === UserRole::Employee->value),
                'nullable',
                'string',
                'max:50',
                $user === null
                    ? Rule::unique('users', 'employee_number')
                    : Rule::unique('users', 'employee_number')->ignore($user),
            ],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function optionalPasswordRules(): array
    {
        return ['nullable', 'string', Password::default(), 'confirmed'];
    }
}
