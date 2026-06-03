<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceStatus;
use App\Models\Event;
use Carbon\CarbonInterface;

class AttendanceStatusResolver
{
    public function forCheckIn(Event $event, CarbonInterface $checkedInAt): AttendanceStatus
    {
        $onTimeUntil = $event->check_in_opens_at ?? $event->starts_at;

        if ($onTimeUntil === null) {
            return AttendanceStatus::Present;
        }

        $graceMinutes = config('clockwork.late_grace_minutes', 15);

        if ($checkedInAt->gt($onTimeUntil->copy()->addMinutes($graceMinutes))) {
            return AttendanceStatus::Late;
        }

        return AttendanceStatus::Present;
    }
}
