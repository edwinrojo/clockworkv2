<?php

namespace App\Enums;

enum EventType: string
{
    case Convocation = 'convocation';
    case Training = 'training';
    case Assembly = 'assembly';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Convocation => 'Convocation',
            self::Training => 'Training',
            self::Assembly => 'Assembly',
            self::Other => 'Other',
        };
    }
}
