<?php

namespace Tests\Feature\Api\V1;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesMobileCheckInScenario;
use Tests\TestCase;

class MobileAttendancesTest extends TestCase
{
    use CreatesMobileCheckInScenario;
    use RefreshDatabase;

    public function test_employee_can_list_their_attendance_history(): void
    {
        $scenario = $this->createMobileCheckInScenario();
        Sanctum::actingAs($scenario['employee']);

        $this->postJson('/api/v1/check-in', [
            'qr_token' => $scenario['plainToken'],
            'latitude' => 6.75,
            'longitude' => 125.35,
        ])->assertCreated();

        $other = User::factory()->employee()->create();
        Attendance::factory()->for($other)->create();

        $response = $this->getJson('/api/v1/attendances')->assertOk();

        $this->assertCount(1, $response->json('data.attendances'));
        $this->assertSame($scenario['event']->id, $response->json('data.attendances.0.event_id'));
        $this->assertSame(1, $response->json('meta.total'));
    }
}
