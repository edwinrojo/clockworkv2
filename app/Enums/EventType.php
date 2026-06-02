<?php

namespace App\Enums;

enum EventType: string
{
    case Convocation = 'convocation';
    case Training = 'training';
    case Assembly = 'assembly';
    case Other = 'other';
}
