<?php

namespace App\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Live = 'live';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
