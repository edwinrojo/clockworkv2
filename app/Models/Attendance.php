<?php

namespace App\Models;

use App\Enums\AttendanceSource;
use App\Enums\AttendanceStatus;
use Database\Factories\AttendanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'event_id',
    'event_session_id',
    'event_date_id',
    'user_id',
    'checked_in_at',
    'latitude',
    'longitude',
    'accuracy_meters',
    'gps_captured_at',
    'source',
    'status',
    'idempotency_key',
    'manual_override_by',
    'manual_override_reason',
    'validation_metadata',
])]
class Attendance extends Model
{
    /** @use HasFactory<AttendanceFactory> */
    use HasFactory, HasUlids;

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'accuracy_meters' => 'decimal:2',
            'gps_captured_at' => 'datetime',
            'source' => AttendanceSource::class,
            'status' => AttendanceStatus::class,
            'validation_metadata' => 'array',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function eventSession(): BelongsTo
    {
        return $this->belongsTo(EventSession::class);
    }

    public function eventDate(): BelongsTo
    {
        return $this->belongsTo(EventDate::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function manualOverrideBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manual_override_by');
    }
}
