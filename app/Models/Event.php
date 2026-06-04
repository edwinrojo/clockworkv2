<?php

namespace App\Models;

use App\Enums\DuplicatePolicy;
use App\Enums\EventRosterScope;
use App\Enums\EventSessionStatus;
use App\Enums\EventStatus;
use App\Enums\EventType;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

#[Fillable([
    'venue_id',
    'created_by',
    'title',
    'description',
    'type',
    'status',
    'starts_at',
    'ends_at',
    'check_in_opens_at',
    'check_in_closes_at',
    'qr_rotation_seconds',
    'duplicate_policy',
    'roster_scope',
    'display_secret',
    'display_pin_hash',
])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory, HasUlids;

    protected static function booted(): void
    {
        static::creating(function (Event $event): void {
            if (blank($event->display_secret)) {
                $event->display_secret = Str::random(64);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'type' => EventType::class,
            'status' => EventStatus::class,
            'duplicate_policy' => DuplicatePolicy::class,
            'roster_scope' => EventRosterScope::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'check_in_opens_at' => 'datetime',
            'check_in_closes_at' => 'datetime',
        ];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(EventSession::class);
    }

    public function activeSession(): HasOne
    {
        return $this->hasOne(EventSession::class)
            ->whereIn('status', [EventSessionStatus::Active, EventSessionStatus::Paused])
            ->latestOfMany('started_at');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function rosterDepartments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'event_department');
    }

    public function rosterUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_user');
    }

    /**
     * @param  Builder<Event>  $query
     */
    public function scopeEligibleForCheckIn(Builder $query): void
    {
        $now = now();

        $query
            ->where('status', EventStatus::Live)
            ->where(function (Builder $query) use ($now): void {
                $query->whereNull('check_in_opens_at')
                    ->orWhere('check_in_opens_at', '<=', $now);
            })
            ->where(function (Builder $query) use ($now): void {
                $query->whereNull('check_in_closes_at')
                    ->orWhere('check_in_closes_at', '>=', $now);
            })
            ->whereHas('sessions', function (Builder $query): void {
                $query->where('status', EventSessionStatus::Active);
            });
    }
}
