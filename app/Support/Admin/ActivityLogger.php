<?php

namespace App\Support\Admin;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogger
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public static function log(
        Request $request,
        string $action,
        ?object $subject = null,
        array $properties = [],
    ): ActivityLog {
        return ActivityLog::query()->create([
            'user_id' => $request->user()?->id,
            'subject_type' => $subject !== null ? $subject::class : null,
            'subject_id' => $subject !== null && method_exists($subject, 'getKey') ? $subject->getKey() : null,
            'action' => $action,
            'properties' => $properties !== [] ? $properties : null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
