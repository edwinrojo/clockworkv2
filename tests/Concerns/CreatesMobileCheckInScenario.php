<?php

namespace Tests\Concerns;

use App\Models\Event;
use App\Models\EventSession;
use App\Models\QrToken;
use App\Models\User;
use App\Models\Venue;

trait CreatesMobileCheckInScenario
{
    /**
     * @return array{
     *     venue: Venue,
     *     event: Event,
     *     session: EventSession,
     *     qr: QrToken,
     *     employee: User,
     *     plainToken: string,
     * }
     */
    protected function createMobileCheckInScenario(
        string $plainToken = 'test-qr-token',
        ?float $latitude = 6.75,
        ?float $longitude = 125.35,
    ): array {
        $venue = Venue::factory()->create([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'geofence_radius_meters' => 500,
            'geofence_polygon' => null,
        ]);

        $event = Event::factory()->live()->for($venue)->create();
        $session = EventSession::factory()->for($event)->create();
        $qr = QrToken::factory()
            ->for($session)
            ->forPlainToken($plainToken)
            ->create([
                'expires_at' => now()->addMinutes(5),
            ]);

        $employee = User::factory()->employee()->create();

        return [
            'venue' => $venue,
            'event' => $event,
            'session' => $session,
            'qr' => $qr,
            'employee' => $employee,
            'plainToken' => $plainToken,
        ];
    }
}
