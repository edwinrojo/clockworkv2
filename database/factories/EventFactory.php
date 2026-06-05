<?php

namespace Database\Factories;

use App\Enums\DuplicatePolicy;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Event;
use App\Models\EventDate;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $eventDate = fake()->dateTimeBetween('+1 day', '+2 weeks')->format('Y-m-d');

        return [
            'venue_id' => Venue::factory(),
            'created_by' => User::factory()->eventManager(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'type' => EventType::Convocation,
            'status' => EventStatus::Scheduled,
            'is_multi_day' => false,
            'starts_at' => $eventDate.' 00:00:00',
            'ends_at' => $eventDate.' 23:59:59',
            'check_in_opens_at' => null,
            'check_in_closes_at' => null,
            'qr_rotation_seconds' => 60,
            'duplicate_policy' => DuplicatePolicy::PerEvent,
            'display_secret' => Str::random(64),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Event $event): void {
            if ($event->dates()->exists()) {
                return;
            }

            $date = $event->starts_at?->toDateString() ?? today()->toDateString();

            EventDate::factory()->for($event)->create([
                'event_date' => $date,
                'check_in_time' => '08:00:00',
                'check_out_time' => '17:00:00',
                'late_cutoff_time' => '09:00:00',
            ]);
        });
    }

    public function live(): static
    {
        return $this->state(fn (): array => [
            'status' => EventStatus::Live,
            'starts_at' => today()->startOfDay(),
            'ends_at' => today()->endOfDay(),
        ])->afterCreating(function (Event $event): void {
            $event->dates()->delete();

            EventDate::factory()->for($event)->openNow()->create();
        });
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EventStatus::Draft,
        ]);
    }

    public function scheduledForToday(): static
    {
        return $this->state(fn (): array => [
            'starts_at' => today()->startOfDay(),
            'ends_at' => today()->endOfDay(),
        ])->afterCreating(function (Event $event): void {
            $event->dates()->delete();

            EventDate::factory()->for($event)->openNow()->create();
        });
    }
}
