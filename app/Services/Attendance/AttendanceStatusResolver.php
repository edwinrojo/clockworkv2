<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceStatus;
use App\Models\Event;
use App\Models\EventDate;
use App\Services\Event\EventScheduleService;
use Carbon\CarbonInterface;

class AttendanceStatusResolver
{
    public function __construct(private EventScheduleService $scheduleService) {}

    public function forCheckIn(Event $event, CarbonInterface $checkedInAt, ?EventDate $schedule = null): AttendanceStatus
    {
        $schedule ??= $this->scheduleService->scheduleForDate($event, $checkedInAt);

        if ($schedule === null) {
            return AttendanceStatus::Present;
        }

        if ($checkedInAt->gt($schedule->lateCutoffAt())) {
            return AttendanceStatus::Late;
        }

        return AttendanceStatus::Present;
    }
}
