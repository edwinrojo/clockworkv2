<?php

namespace App\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Live = 'live';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Scheduled => 'Scheduled',
            self::Live => 'Live',
            self::Closed => 'Closed',
            self::Cancelled => 'Cancelled',
        };
    }
}
