<?php

namespace App\Enums;

enum DuplicatePolicy: string
{
    case PerEvent = 'per_event';
    case PerCalendarDay = 'per_calendar_day';
}
