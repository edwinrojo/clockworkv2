<?php

namespace App\Support\Display;

use App\Models\Event;
use Illuminate\Http\Request;

class DisplayAccess
{
    public static function sessionKey(Event $event): string
    {
        return 'display_unlocked_'.$event->display_secret;
    }

    public static function isUnlocked(Event $event, Request $request): bool
    {
        if ($event->display_pin_hash === null) {
            return true;
        }

        return (bool) $request->session()->get(self::sessionKey($event), false);
    }

    public static function requiresPin(Event $event): bool
    {
        return $event->display_pin_hash !== null;
    }
}
