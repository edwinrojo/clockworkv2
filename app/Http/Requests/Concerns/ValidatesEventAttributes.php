<?php

namespace App\Http\Requests\Concerns;

use App\Enums\DuplicatePolicy;
use App\Enums\EventStatus;
use App\Enums\EventType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'is_multi_day' => ['boolean'],
            'schedule' => ['required', 'array', 'min:1'],
            'schedule.*.event_date' => ['required', 'date'],
            'schedule.*.check_in_time' => ['required', 'date_format:H:i'],
            'schedule.*.check_out_time' => ['required', 'date_format:H:i'],
            'schedule.*.late_cutoff_time' => ['required', 'date_format:H:i'],
            'qr_rotation_seconds' => ['required', 'integer', 'min:15', 'max:300'],
            'duplicate_policy' => ['required', Rule::enum(DuplicatePolicy::class)],
        ];
    }

    protected function prepareEventScheduleValidation(): void
    {
        $this->merge([
            'is_multi_day' => $this->boolean('is_multi_day'),
        ]);
    }

    protected function validateEventSchedule(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $schedule = $this->input('schedule', []);

            if ($this->boolean('is_multi_day') && count($schedule) < 2) {
                $validator->errors()->add('schedule', __('Add at least two dates for a multi-day event.'));
            }

            foreach ($schedule as $index => $row) {
                $checkIn = $row['check_in_time'] ?? null;
                $lateCutoff = $row['late_cutoff_time'] ?? null;

                if (is_string($checkIn) && is_string($lateCutoff) && $lateCutoff <= $checkIn) {
                    $validator->errors()->add(
                        "schedule.{$index}.late_cutoff_time",
                        __('The on-time cutoff must be after the check-in open time.'),
                    );
                }
            }
        });
    }
}
