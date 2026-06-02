<?php

namespace App\Http\Requests\Concerns;

use App\Enums\DuplicatePolicy;
use App\Enums\EventStatus;
use App\Enums\EventType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ValidatesEventAttributes
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function eventAttributeRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'venue_id' => ['required', 'ulid', Rule::exists('venues', 'id')],
            'type' => ['required', Rule::enum(EventType::class)],
            'status' => ['required', Rule::enum(EventStatus::class)],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'check_in_opens_at' => ['nullable', 'date'],
            'check_in_closes_at' => ['nullable', 'date', 'after:check_in_opens_at'],
            'qr_rotation_seconds' => ['required', 'integer', 'min:15', 'max:300'],
            'duplicate_policy' => ['required', Rule::enum(DuplicatePolicy::class)],
        ];
    }
}
