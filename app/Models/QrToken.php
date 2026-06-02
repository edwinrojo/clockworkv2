<?php

namespace App\Models;

use Database\Factories\QrTokenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'event_session_id',
    'token_hash',
    'issued_at',
    'expires_at',
])]
class QrToken extends Model
{
    /** @use HasFactory<QrTokenFactory> */
    use HasFactory, HasUlids;

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function eventSession(): BelongsTo
    {
        return $this->belongsTo(EventSession::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
