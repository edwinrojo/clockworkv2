<?php

namespace App\Enums;

enum EventType: string
{
    case Convocation = 'convocation';
    case Training = 'training';
    case Meeting = 'meeting';
    case Seminar = 'seminar';
    case SpecialEvent = 'special_event';
    case Assembly = 'assembly';
    case Workshop = 'workshop';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Convocation => 'Convocation',
            self::Training => 'Training',
            self::Meeting => 'Meeting',
            self::Seminar => 'Seminar',
            self::SpecialEvent => 'Special Event',
            self::Assembly => 'Assembly',
            self::Workshop => 'Workshop',
            self::Other => 'Other',
        };
    }
}
