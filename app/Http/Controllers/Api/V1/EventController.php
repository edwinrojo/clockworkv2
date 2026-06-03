<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EventSessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\EventResource;
use App\Models\Event;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    public function index(): JsonResponse
    {
        $events = Event::query()
            ->eligibleForCheckIn()
            ->with([
                'venue',
                'sessions' => fn ($query) => $query
                    ->where('status', EventSessionStatus::Active)
                    ->latest('started_at'),
            ])
            ->orderBy('starts_at')
            ->get();

        return ApiResponse::success([
            'events' => EventResource::collection($events),
        ]);
    }
}
