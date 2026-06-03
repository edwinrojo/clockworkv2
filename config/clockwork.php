<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Late check-in grace period
    |--------------------------------------------------------------------------
    |
    | Minutes after check-in opens (or event start if not set) before a
    | mobile check-in is recorded as "late" instead of "present".
    |
    */

    'late_grace_minutes' => (int) env('CLOCKWORK_LATE_GRACE_MINUTES', 15),

];
