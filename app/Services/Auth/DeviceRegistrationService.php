<?php

namespace App\Services\Auth;

use App\Enums\DeviceChangeRequestStatus;
use App\Models\DeviceChangeRequest;
use App\Models\EmployeeDevice;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeviceRegistrationService
{
    /**
     * @param  array{device_name?: ?string, device_model?: ?string, platform?: ?string, os_version?: ?string, reason?: ?string}  $metadata
     */
    public function attemptLogin(User $user, string $deviceId, array $metadata = []): DeviceLoginResult
    {
        $hash = $this->hashDeviceId($deviceId);
        $device = EmployeeDevice::query()->where('user_id', $user->id)->first();

        if ($device === null) {
            $this->registerDevice($user, $hash, $metadata, autoApproved: true);

            return DeviceLoginResult::allowed();
        }

        if (hash_equals($device->device_id_hash, $hash)) {
            $this->touchDevice($device, $metadata);

            return DeviceLoginResult::allowed();
        }

        $pending = DeviceChangeRequest::query()
            ->pending()
            ->where('user_id', $user->id)
            ->first();

        if ($pending !== null) {
            if (! hash_equals($pending->requested_device_id_hash, $hash)) {
                $pending->update($this->requestAttributes($hash, $metadata));
            }

            return DeviceLoginResult::changePending($pending->id);
        }

        $request = DeviceChangeRequest::query()->create([
            'user_id' => $user->id,
            ...$this->requestAttributes($hash, $metadata),
            'status' => DeviceChangeRequestStatus::Pending,
        ]);

        return DeviceLoginResult::changeRequired($request->id);
    }

    public function approve(DeviceChangeRequest $request, User $admin): void
    {
        if ($request->status !== DeviceChangeRequestStatus::Pending) {
            return;
        }

        DB::transaction(function () use ($request, $admin): void {
            $employee = $request->user;

            EmployeeDevice::query()->updateOrCreate(
                ['user_id' => $employee->id],
                [
                    'device_id_hash' => $request->requested_device_id_hash,
                    'device_name' => $request->device_name,
                    'device_model' => $request->device_model,
                    'platform' => $request->platform,
                    'os_version' => $request->os_version,
                    'approved_by' => $admin->id,
                    'approved_at' => now(),
                    'last_seen_at' => null,
                ],
            );

            $request->update([
                'status' => DeviceChangeRequestStatus::Approved,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            DeviceChangeRequest::query()
                ->pending()
                ->where('user_id', $employee->id)
                ->whereKeyNot($request->id)
                ->update([
                    'status' => DeviceChangeRequestStatus::Rejected,
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => now(),
                    'rejection_reason' => __('Superseded by an approved device change.'),
                ]);

            $employee->tokens()->delete();
        });
    }

    public function reject(DeviceChangeRequest $request, User $admin, ?string $reason = null): void
    {
        if ($request->status !== DeviceChangeRequestStatus::Pending) {
            return;
        }

        $request->update([
            'status' => DeviceChangeRequestStatus::Rejected,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function unlink(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->employeeDevice()?->delete();

            DeviceChangeRequest::query()
                ->pending()
                ->where('user_id', $user->id)
                ->update([
                    'status' => DeviceChangeRequestStatus::Rejected,
                    'reviewed_at' => now(),
                    'rejection_reason' => __('Device unlinked by an administrator.'),
                ]);

            $user->tokens()->delete();
        });
    }

    public function hashDeviceId(string $deviceId): string
    {
        return hash('sha256', $deviceId);
    }

    /**
     * @param  array{device_name?: ?string, device_model?: ?string, platform?: ?string, os_version?: ?string}  $metadata
     */
    private function registerDevice(User $user, string $hash, array $metadata, bool $autoApproved): void
    {
        EmployeeDevice::query()->create([
            'user_id' => $user->id,
            'device_id_hash' => $hash,
            'device_name' => $metadata['device_name'] ?? null,
            'device_model' => $metadata['device_model'] ?? null,
            'platform' => $metadata['platform'] ?? null,
            'os_version' => $metadata['os_version'] ?? null,
            'approved_by' => $autoApproved ? null : null,
            'approved_at' => $autoApproved ? now() : null,
            'last_seen_at' => now(),
        ]);
    }

    /**
     * @param  array{device_name?: ?string, device_model?: ?string, platform?: ?string, os_version?: ?string}  $metadata
     */
    private function touchDevice(EmployeeDevice $device, array $metadata): void
    {
        $device->update([
            'device_name' => $metadata['device_name'] ?? $device->device_name,
            'device_model' => $metadata['device_model'] ?? $device->device_model,
            'platform' => $metadata['platform'] ?? $device->platform,
            'os_version' => $metadata['os_version'] ?? $device->os_version,
            'last_seen_at' => now(),
        ]);
    }

    /**
     * @param  array{device_name?: ?string, device_model?: ?string, platform?: ?string, os_version?: ?string, reason?: ?string}  $metadata
     * @return array<string, mixed>
     */
    private function requestAttributes(string $hash, array $metadata): array
    {
        return [
            'requested_device_id_hash' => $hash,
            'device_name' => $metadata['device_name'] ?? null,
            'device_model' => $metadata['device_model'] ?? null,
            'platform' => $metadata['platform'] ?? null,
            'os_version' => $metadata['os_version'] ?? null,
            'reason' => $metadata['reason'] ?? null,
        ];
    }
}
