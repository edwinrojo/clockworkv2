<?php

namespace App\Services\Event;

use App\Enums\EventSessionStatus;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\User;
use App\Services\Qr\QrTokenService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventSessionService
{
    public function __construct(private QrTokenService $qrTokenService) {}

    public function start(Event $event, User $startedBy): EventSession
    {
        if ($event->sessions()->where('status', EventSessionStatus::Active)->exists()) {
            throw ValidationException::withMessages([
                'session' => __('This event already has an active check-in session.'),
            ]);
        }

        return DB::transaction(function () use ($event, $startedBy): EventSession {
            $event->update(['status' => EventStatus::Live]);

            $session = EventSession::query()->create([
                'event_id' => $event->id,
                'started_by' => $startedBy->id,
                'status' => EventSessionStatus::Active,
                'started_at' => now(),
            ]);

            $session->setRelation('event', $event);

            $this->qrTokenService->issueToken($session);

            return $session;
        });
    }

    public function pause(EventSession $session): EventSession
    {
        if ($session->status !== EventSessionStatus::Active) {
            throw ValidationException::withMessages([
                'session' => __('Only an active session can be paused.'),
            ]);
        }

        $session->update(['status' => EventSessionStatus::Paused]);
        $this->qrTokenService->clearCache($session);

        return $session->fresh();
    }

    public function resume(EventSession $session): EventSession
    {
        if ($session->status !== EventSessionStatus::Paused) {
            throw ValidationException::withMessages([
                'session' => __('Only a paused session can be resumed.'),
            ]);
        }

        $session->update(['status' => EventSessionStatus::Active]);
        $session->loadMissing('event');

        $this->qrTokenService->issueToken($session);

        return $session->fresh();
    }

    public function end(EventSession $session): EventSession
    {
        if ($session->status === EventSessionStatus::Ended) {
            throw ValidationException::withMessages([
                'session' => __('This session has already ended.'),
            ]);
        }

        return DB::transaction(function () use ($session): EventSession {
            $session->update([
                'status' => EventSessionStatus::Ended,
                'ended_at' => now(),
            ]);

            $this->qrTokenService->clearCache($session);

            $event = $session->event;

            if (! $event->sessions()->where('status', EventSessionStatus::Active)->exists()) {
                $event->update(['status' => EventStatus::Closed]);
            }

            return $session->fresh();
        });
    }

    public function rotateNow(EventSession $session): ?array
    {
        if ($session->status !== EventSessionStatus::Active) {
            throw ValidationException::withMessages([
                'session' => __('QR can only be rotated during an active session.'),
            ]);
        }

        $session->loadMissing('event');

        return $this->qrTokenService->rotate($session);
    }

    public function activeSessionFor(Event $event): ?EventSession
    {
        return $event->sessions()
            ->whereIn('status', [EventSessionStatus::Active, EventSessionStatus::Paused])
            ->latest('started_at')
            ->first();
    }
}
