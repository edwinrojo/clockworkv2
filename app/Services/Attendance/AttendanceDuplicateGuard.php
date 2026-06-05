<?php

namespace App\Services\Attendance;

use App\Enums\CheckInErrorCode;
use App\Enums\DuplicatePolicy;
use App\Exceptions\CheckInException;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\EventDate;
use App\Models\User;

class AttendanceDuplicateGuard
{
    public function assertNotDuplicate(User $user, Event $event, ?EventDate $schedule = null): void
    {
        $query = Attendance::query()->where('user_id', $user->id);

        if ($event->duplicate_policy === DuplicatePolicy::PerCalendarDay) {
            if ($schedule !== null) {
                $query->where('event_date_id', $schedule->id);
            } else {
                $query
                    ->where('event_id', $event->id)
                    ->whereDate('checked_in_at', today());
            }
        } else {
            $query->where('event_id', $event->id);
        }

        if ($query->exists()) {
            throw new CheckInException(CheckInErrorCode::AlreadyCheckedIn);
        }
    }
}
