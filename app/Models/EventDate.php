<?php

namespace App\Models;

use Database\Factories\EventDateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable([
    'event_id',
    'event_date',
    'check_in_time',
    'check_out_time',
    'late_cutoff_time',
])]
class EventDate extends Model
{
    /** @use HasFactory<EventDateFactory> */
    use HasFactory, HasUlids;

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(EventSession::class);
    }

    public function checkInOpensAt(?Carbon $timezone = null): Carbon
    {
        return $this->atTimeOnDate($this->check_in_time, $timezone);
    }

    public function checkOutOpensAt(?Carbon $timezone = null): Carbon
    {
        return $this->atTimeOnDate($this->check_out_time, $timezone);
    }

    public function lateCutoffAt(?Carbon $timezone = null): Carbon
    {
        return $this->atTimeOnDate($this->late_cutoff_time, $timezone);
    }

    private function atTimeOnDate(string $time, ?Carbon $timezone = null): Carbon
    {
        $date = $this->event_date->format('Y-m-d');
        $parsed = Carbon::parse("{$date} {$time}", $timezone?->timezone ?? config('app.timezone'));

        return $parsed;
    }
}
