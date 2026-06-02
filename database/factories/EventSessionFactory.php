<?php

namespace Database\Factories;

use App\Enums\EventSessionStatus;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventSession>
 */
class EventSessionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory()->live(),
            'started_by' => User::factory()->eventManager(),
            'status' => EventSessionStatus::Active,
            'started_at' => now(),
            'ended_at' => null,
        ];
    }

    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EventSessionStatus::Ended,
            'ended_at' => now(),
        ]);
    }

    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EventSessionStatus::Paused,
        ]);
    }
}
