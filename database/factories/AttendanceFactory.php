<?php

namespace Database\Factories;

use App\Enums\AttendanceSource;
use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $checkedInAt = now();

        return [
            'event_id' => Event::factory()->live(),
            'event_session_id' => EventSession::factory(),
            'user_id' => User::factory()->employee(),
            'checked_in_at' => $checkedInAt,
            'latitude' => fake()->latitude(6.4, 6.9),
            'longitude' => fake()->longitude(125.0, 125.6),
            'accuracy_meters' => fake()->randomFloat(2, 5, 25),
            'gps_captured_at' => $checkedInAt,
            'source' => AttendanceSource::Mobile,
            'status' => AttendanceStatus::Present,
            'idempotency_key' => Str::ulid(),
            'manual_override_by' => null,
            'manual_override_reason' => null,
            'validation_metadata' => null,
        ];
    }

    public function manual(User $admin, string $reason = 'Verified on-site by coordinator'): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => AttendanceSource::Manual,
            'status' => AttendanceStatus::ManualOverride,
            'manual_override_by' => $admin->id,
            'manual_override_reason' => $reason,
            'latitude' => null,
            'longitude' => null,
            'accuracy_meters' => null,
            'gps_captured_at' => null,
        ]);
    }
}
