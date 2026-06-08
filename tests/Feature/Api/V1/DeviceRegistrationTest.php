<?php

namespace Tests\Feature\Api\V1;

use App\Enums\DeviceChangeRequestStatus;
use App\Models\DeviceChangeRequest;
use App\Models\EmployeeDevice;
use App\Models\User;
use App\Services\Auth\DeviceRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_login_auto_registers_device(): void
    {
        $employee = User::factory()->employee()->create([
            'email' => 'employee@example.com',
        ]);

        $this->postJson('/api/v1/auth/login', $this->loginPayload('device-a'))
            ->assertOk()
            ->assertJsonPath('data.user.email', 'employee@example.com');

        $this->assertDatabaseHas('employee_devices', [
            'user_id' => $employee->id,
            'device_name' => 'PG-DDS-100',
            'device_model' => 'Samsung A14',
            'platform' => 'android',
        ]);

        $this->assertDatabaseCount('device_change_requests', 0);
    }

    public function test_registered_device_can_login_again(): void
    {
        User::factory()->employee()->create([
            'email' => 'employee@example.com',
        ]);

        $this->postJson('/api/v1/auth/login', $this->loginPayload('device-a'))->assertOk();

        $this->postJson('/api/v1/auth/login', $this->loginPayload('device-a'))
            ->assertOk()
            ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_new_device_requires_admin_approval(): void
    {
        User::factory()->employee()->create([
            'email' => 'employee@example.com',
        ]);

        $this->postJson('/api/v1/auth/login', $this->loginPayload('device-a'))->assertOk();

        $this->postJson('/api/v1/auth/login', $this->loginPayload('device-b', [
            'reason' => 'Lost my old phone.',
        ]))
            ->assertForbidden()
            ->assertJsonPath('code', 'DEVICE_CHANGE_REQUIRED')
            ->assertJsonPath('data.device_change_request_id', fn ($id) => is_string($id) && $id !== '');

        $this->assertDatabaseHas('device_change_requests', [
            'status' => DeviceChangeRequestStatus::Pending->value,
            'reason' => 'Lost my old phone.',
        ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_repeat_login_on_pending_device_returns_pending_status(): void
    {
        User::factory()->employee()->create([
            'email' => 'employee@example.com',
        ]);

        $this->postJson('/api/v1/auth/login', $this->loginPayload('device-a'))->assertOk();
        $this->postJson('/api/v1/auth/login', $this->loginPayload('device-b'))->assertForbidden();

        $this->postJson('/api/v1/auth/login', $this->loginPayload('device-b'))
            ->assertForbidden()
            ->assertJsonPath('code', 'DEVICE_CHANGE_PENDING');
    }

    public function test_login_requires_device_id(): void
    {
        User::factory()->employee()->create([
            'email' => 'employee@example.com',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'employee@example.com',
            'password' => 'password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['device_id']);
    }

    public function test_approved_device_change_allows_login_on_new_phone(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $employee = User::factory()->employee()->create([
            'email' => 'employee@example.com',
        ]);

        $this->postJson('/api/v1/auth/login', $this->loginPayload('device-a'))->assertOk();
        $this->postJson('/api/v1/auth/login', $this->loginPayload('device-b'))->assertForbidden();

        $request = DeviceChangeRequest::query()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('device-change-requests.approve', $request))
            ->assertRedirect();

        $this->postJson('/api/v1/auth/login', $this->loginPayload('device-b'))
            ->assertOk();

        $this->assertDatabaseHas('employee_devices', [
            'user_id' => $employee->id,
            'device_name' => 'PG-DDS-100',
        ]);

        $device = EmployeeDevice::query()->where('user_id', $employee->id)->firstOrFail();
        $this->assertSame(
            app(DeviceRegistrationService::class)->hashDeviceId('device-b'),
            $device->device_id_hash,
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function loginPayload(string $deviceId, array $overrides = []): array
    {
        return array_merge([
            'email' => 'employee@example.com',
            'password' => 'password',
            'device_id' => $deviceId,
            'device_name' => 'PG-DDS-100',
            'device_model' => 'Samsung A14',
            'platform' => 'android',
            'os_version' => '14',
        ], $overrides);
    }
}
