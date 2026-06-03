<?php

namespace Tests\Feature\Api\V1;

use App\Models\Attendance;
use App\Models\QrToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesMobileCheckInScenario;
use Tests\TestCase;

class MobileCheckInTest extends TestCase
{
    use CreatesMobileCheckInScenario;
    use RefreshDatabase;

    public function test_employee_can_check_in_with_valid_qr_and_location(): void
    {
        $scenario = $this->createMobileCheckInScenario();
        Sanctum::actingAs($scenario['employee']);

        $response = $this->postJson('/api/v1/check-in', [
            'qr_token' => $scenario['plainToken'],
            'latitude' => 6.75,
            'longitude' => 125.35,
            'accuracy' => 10,
            'captured_at' => now()->toIso8601String(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.replayed', false)
            ->assertJsonPath('data.attendance.event_id', $scenario['event']->id);

        $this->assertDatabaseHas('attendances', [
            'event_id' => $scenario['event']->id,
            'user_id' => $scenario['employee']->id,
        ]);
    }

    public function test_check_in_rejects_expired_qr_token(): void
    {
        $scenario = $this->createMobileCheckInScenario();
        $scenario['qr']->update([
            'expires_at' => now()->subMinute(),
        ]);
        Sanctum::actingAs($scenario['employee']);

        $this->postJson('/api/v1/check-in', [
            'qr_token' => $scenario['plainToken'],
            'latitude' => 6.75,
            'longitude' => 125.35,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'QR_EXPIRED');
    }

    public function test_check_in_rejects_location_outside_geofence(): void
    {
        $scenario = $this->createMobileCheckInScenario();
        Sanctum::actingAs($scenario['employee']);

        $this->postJson('/api/v1/check-in', [
            'qr_token' => $scenario['plainToken'],
            'latitude' => 14.5995,
            'longitude' => 120.9842,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'OUTSIDE_GEOFENCE');
    }

    public function test_check_in_rejects_duplicate_attendance(): void
    {
        $scenario = $this->createMobileCheckInScenario();
        Sanctum::actingAs($scenario['employee']);

        $payload = [
            'qr_token' => $scenario['plainToken'],
            'latitude' => 6.75,
            'longitude' => 125.35,
        ];

        $this->postJson('/api/v1/check-in', $payload)->assertCreated();

        $newToken = 'second-qr-token';
        QrToken::factory()
            ->for($scenario['session'])
            ->forPlainToken($newToken)
            ->create(['expires_at' => now()->addMinutes(5)]);

        $this->postJson('/api/v1/check-in', [
            ...$payload,
            'qr_token' => $newToken,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'ALREADY_CHECKED_IN');
    }

    public function test_idempotent_check_in_returns_existing_attendance(): void
    {
        $scenario = $this->createMobileCheckInScenario();
        Sanctum::actingAs($scenario['employee']);

        $payload = [
            'qr_token' => $scenario['plainToken'],
            'latitude' => 6.75,
            'longitude' => 125.35,
            'idempotency_key' => 'retry-key-001',
        ];

        $first = $this->postJson('/api/v1/check-in', $payload)->assertCreated();
        $attendanceId = $first->json('data.attendance.id');

        $this->postJson('/api/v1/check-in', $payload)
            ->assertOk()
            ->assertJsonPath('data.replayed', true)
            ->assertJsonPath('data.attendance.id', $attendanceId);

        $this->assertSame(1, Attendance::query()->where('user_id', $scenario['employee']->id)->count());
    }
}
