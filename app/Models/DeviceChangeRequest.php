<?php

namespace App\Models;

use App\Enums\DeviceChangeRequestStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'requested_device_id_hash',
    'device_name',
    'device_model',
    'platform',
    'os_version',
    'reason',
    'status',
    'reviewed_by',
    'reviewed_at',
    'rejection_reason',
])]
class DeviceChangeRequest extends Model
{
    use HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DeviceChangeRequestStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<DeviceChangeRequest>  $query
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', DeviceChangeRequestStatus::Pending);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
