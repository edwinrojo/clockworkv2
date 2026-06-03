<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Event
 */
class EventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $activeSession = $this->relationLoaded('sessions')
            ? $this->sessions->first()
            : null;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'check_in_opens_at' => $this->check_in_opens_at?->toIso8601String(),
            'check_in_closes_at' => $this->check_in_closes_at?->toIso8601String(),
            'active_session_id' => $activeSession?->id,
            'venue' => [
                'id' => $this->venue->id,
                'name' => $this->venue->name,
                'address' => $this->venue->address,
                'latitude' => (float) $this->venue->latitude,
                'longitude' => (float) $this->venue->longitude,
            ],
        ];
    }
}
