<?php

namespace App\Enums;

enum DuplicatePolicy: string
{
    case PerEvent = 'per_event';
    case PerCalendarDay = 'per_calendar_day';

    public function label(): string
    {
        return match ($this) {
            self::PerEvent => 'One check-in per event',
            self::PerCalendarDay => 'One check-in per calendar day',
        };
    }
}
