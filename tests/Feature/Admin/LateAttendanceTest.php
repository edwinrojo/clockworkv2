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

    public function test_check_in_after_on_time_cutoff_is_marked_late(): void
    {
        $scenario = $this->createMobileCheckInScenario();

        $schedule = $scenario['event']->dates()->first();
        $this->assertNotNull($schedule);

        $schedule->update([
            'late_cutoff_time' => now()->subMinutes(5)->format('H:i:s'),
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
