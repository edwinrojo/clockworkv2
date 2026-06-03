<?php

namespace Tests\Feature\Admin;

use App\Enums\AttendanceStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesMobileCheckInScenario;
use Tests\TestCase;

class LateAttendanceTest extends TestCase
{
    use CreatesMobileCheckInScenario;
    use RefreshDatabase;

    public function test_check_in_after_grace_period_is_marked_late(): void
    {
        config(['clockwork.late_grace_minutes' => 0]);

        $scenario = $this->createMobileCheckInScenario();
        $scenario['event']->update([
            'check_in_opens_at' => now()->subHours(2),
            'starts_at' => now()->subHours(2),
        ]);

        $this->actingAs($scenario['employee'], 'sanctum')
            ->postJson('/api/v1/check-in', [
                'qr_token' => $scenario['plainToken'],
                'latitude' => 6.75,
                'longitude' => 125.35,
            ])
            ->assertCreated()
            ->assertJsonPath('data.attendance.status', AttendanceStatus::Late->value);
    }
}
