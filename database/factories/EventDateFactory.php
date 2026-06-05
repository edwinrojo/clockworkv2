<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventDate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventDate>
 */
class EventDateFactory extends Factory
{
    protected $model = EventDate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'event_date' => today()->toDateString(),
            'check_in_time' => '08:00:00',
            'check_out_time' => '17:00:00',
            'late_cutoff_time' => '09:00:00',
        ];
    }

    public function openNow(): static
    {
        return $this->state(fn (): array => [
            'event_date' => today()->toDateString(),
            'check_in_time' => now()->subHour()->format('H:i:s'),
            'late_cutoff_time' => now()->subMinutes(30)->format('H:i:s'),
            'check_out_time' => '17:00:00',
        ]);
    }

    public function forDate(string $date): static
    {
        return $this->state(fn (): array => [
            'event_date' => $date,
        ]);
    }
}
