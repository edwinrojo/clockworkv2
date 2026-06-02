<?php

namespace App\Enums;

enum EventSessionStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Ended = 'ended';
}
