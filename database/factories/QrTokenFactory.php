<?php

namespace Database\Factories;

use App\Models\EventSession;
use App\Models\QrToken;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<QrToken>
 */
class QrTokenFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $issuedAt = now();

        return [
            'event_session_id' => EventSession::factory(),
            'token_hash' => hash('sha256', Str::random(40)),
            'issued_at' => $issuedAt,
            'expires_at' => $issuedAt->copy()->addSeconds(60),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'issued_at' => now()->subMinutes(5),
            'expires_at' => now()->subMinutes(4),
        ]);
    }

    public function forPlainToken(string $plainToken): static
    {
        return $this->state(fn (array $attributes) => [
            'token_hash' => hash('sha256', $plainToken),
        ]);
    }
}
