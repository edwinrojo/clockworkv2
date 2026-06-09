<?php

namespace Tests\Feature\Admin;

use App\Enums\DeviceChangeRequestStatus;
use App\Models\DeviceChangeRequest;
use App\Models\EmployeeDevice;
use App\Models\User;
use App\Services\Auth\DeviceRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceChangeRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_pending_device_change_requests(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $employee = User::factory()->employee()->create();

        $this->seedPendingRequest($employee, 'new-device');

        $this->actingAs($admin)
            ->get(route('device-change-requests.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('device-requests/Index')
                ->has('requests.data', 1)
                ->where('requests.data.0.employee.id', $employee->id));
    }

    public function test_admin_can_reject_device_change_request(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $employee = User::factory()->employee()->create();

        $request = $this->seedPendingRequest($employee, 'new-device');

        $this->actingAs($admin)
            ->post(route('device-change-requests.reject', $request), [
                'rejection_reason' => 'Please visit IT first.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('device_change_requests', [
            'id' => $request->id,
            'status' => DeviceChangeRequestStatus::Rejected->value,
            'rejection_reason' => 'Please visit IT first.',
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_admin_can_unlink_employee_device(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $employee = User::factory()->employee()->create();
        $employee->createToken('mobile');

        EmployeeDevice::query()->create([
            'user_id' => $employee->id,
            'device_id_hash' => app(DeviceRegistrationService::class)->hashDeviceId('device-a'),
            'device_name' => 'PG-DDS-100',
            'approved_at' => now(),
            'last_seen_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('users.unlink-device', $employee))
            ->assertRedirect();

        $this->assertDatabaseMissing('employee_devices', [
            'user_id' => $employee->id,
        ]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    private function seedPendingRequest(User $employee, string $deviceId): DeviceChangeRequest
    {
        EmployeeDevice::query()->create([
            'user_id' => $employee->id,
            'device_id_hash' => app(DeviceRegistrationService::class)->hashDeviceId('old-device'),
            'device_name' => 'Old phone',
            'approved_at' => now(),
            'last_seen_at' => now(),
        ]);

        return DeviceChangeRequest::query()->create([
            'user_id' => $employee->id,
            'requested_device_id_hash' => app(DeviceRegistrationService::class)->hashDeviceId($deviceId),
            'device_name' => 'New phone',
            'device_model' => 'Pixel 8',
            'platform' => 'android',
            'status' => DeviceChangeRequestStatus::Pending,
        ]);
    }
}
