<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->eventManager(),
            'subject_type' => Event::class,
            'subject_id' => Event::factory(),
            'action' => fake()->randomElement(['created', 'updated', 'session.started']),
            'properties' => ['ip' => fake()->ipv4()],
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}
