<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceSource;
use App\Enums\AttendanceStatus;
use App\Enums\CheckInErrorCode;
use App\Enums\EventSessionStatus;
use App\Exceptions\CheckInException;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ManualAttendanceService
{
    public function record(
        Event $event,
        User $employee,
        User $admin,
        string $reason,
        AttendanceStatus $status = AttendanceStatus::ManualOverride,
    ): Attendance {
        if (! $employee->isEmployee() || ! $employee->is_active) {
            throw new CheckInException(CheckInErrorCode::Unauthorized);
        }

        if (Attendance::query()->where('event_id', $event->id)->where('user_id', $employee->id)->exists()) {
            throw new CheckInException(CheckInErrorCode::AlreadyCheckedIn);
        }

        $session = $event->sessions()
            ->where('status', EventSessionStatus::Active)
            ->latest('started_at')
            ->first();

        return DB::transaction(function () use ($event, $employee, $admin, $reason, $status, $session): Attendance {
            $attendance = Attendance::query()->create([
                'event_id' => $event->id,
                'event_session_id' => $session?->id,
                'user_id' => $employee->id,
                'checked_in_at' => now(),
                'source' => AttendanceSource::Manual,
                'status' => $status,
                'manual_override_by' => $admin->id,
                'manual_override_reason' => $reason,
            ]);

            ActivityLog::query()->create([
                'user_id' => $admin->id,
                'subject_type' => Attendance::class,
                'subject_id' => $attendance->id,
                'action' => 'manual_attendance',
                'properties' => [
                    'event_id' => $event->id,
                    'employee_id' => $employee->id,
                    'reason' => $reason,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $attendance->load(['user', 'event']);
        });
    }
}
