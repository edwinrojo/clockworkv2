<?php

namespace App\Http\Requests;

use App\Enums\EventRosterScope;
use App\Models\Event;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRosterRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Event $event */
        $event = $this->route('event');

        return $this->user()->can('update', $event);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'roster_scope' => ['required', Rule::enum(EventRosterScope::class)],
            'department_ids' => [
                Rule::requiredIf(fn (): bool => $this->input('roster_scope') === EventRosterScope::Departments->value),
                'array',
                'min:1',
            ],
            'department_ids.*' => ['ulid', Rule::exists('departments', 'id')],
            'user_ids' => [
                Rule::requiredIf(fn (): bool => $this->input('roster_scope') === EventRosterScope::Employees->value),
                'array',
                'min:1',
            ],
            'user_ids.*' => ['ulid', Rule::exists('users', 'id')],
        ];
    }
}
