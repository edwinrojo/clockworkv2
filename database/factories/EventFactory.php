<?php

namespace Database\Factories;

use App\Enums\DuplicatePolicy;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Event;
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
        $startsAt = fake()->dateTimeBetween('+1 day', '+2 weeks');
        $endsAt = (clone $startsAt)->modify('+2 hours');

        return [
            'venue_id' => Venue::factory(),
            'created_by' => User::factory()->eventManager(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'type' => EventType::Convocation,
            'status' => EventStatus::Scheduled,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'check_in_opens_at' => (clone $startsAt)->modify('-30 minutes'),
            'check_in_closes_at' => (clone $startsAt)->modify('+1 hour'),
            'qr_rotation_seconds' => 60,
            'duplicate_policy' => DuplicatePolicy::PerEvent,
            'display_secret' => Str::random(64),
        ];
    }

    public function live(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EventStatus::Live,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(2),
            'check_in_opens_at' => now()->subHour(),
            'check_in_closes_at' => now()->addHours(2),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EventStatus::Draft,
        ]);
    }
}
