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
            'event_date_id' => null,
            'started_by' => User::factory()->eventManager(),
            'status' => EventSessionStatus::Active,
            'started_at' => now(),
            'ended_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (EventSession $session): void {
            if ($session->event_date_id !== null) {
                return;
            }

            $event = $session->event;

            if ($event === null) {
                return;
            }

            $schedule = $event->dates()->whereDate('event_date', today())->first()
                ?? $event->dates()->first();

            if ($schedule !== null) {
                $session->event_date_id = $schedule->id;
            }
        });
    }

    public function ended(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EventSessionStatus::Ended,
            'ended_at' => now(),
        ]);
    }

    public function paused(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EventSessionStatus::Paused,
        ]);
    }
}
