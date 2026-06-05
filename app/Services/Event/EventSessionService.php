<?php

namespace App\Services\Event;

use App\Enums\EventSessionStatus;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventDate;
use App\Models\EventSession;
use App\Models\User;
use App\Services\Qr\QrTokenService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventSessionService
{
    public function __construct(
        private QrTokenService $qrTokenService,
        private EventScheduleService $scheduleService,
    ) {}

    public function start(Event $event, User $startedBy): EventSession
    {
        $schedule = $this->scheduleService->assertCanStartSessionManually($event);

        return $this->openSession($event, $schedule, $startedBy);
    }

    public function autoStart(Event $event, EventDate $schedule, User $startedBy): EventSession
    {
        if ($event->sessions()->where('status', EventSessionStatus::Active)->exists()) {
            throw ValidationException::withMessages([
                'session' => __('This event already has an active check-in session.'),
            ]);
        }

        if ($this->scheduleService->hasSessionForScheduleToday($schedule)) {
            throw ValidationException::withMessages([
                'session' => __('A check-in session already ran for this event date.'),
            ]);
        }

        if (! now()->greaterThanOrEqualTo($schedule->checkInOpensAt())) {
            throw ValidationException::withMessages([
                'session' => __('Check-in is not open yet for this event date.'),
            ]);
        }

        return $this->openSession($event, $schedule, $startedBy);
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

            if ($event !== null && ! $event->sessions()->whereIn('status', [EventSessionStatus::Active, EventSessionStatus::Paused])->exists()) {
                $hasFutureDates = $event->dates()
                    ->whereDate('event_date', '>', today()->toDateString())
                    ->exists();

                $event->update([
                    'status' => $hasFutureDates ? EventStatus::Scheduled : EventStatus::Closed,
                ]);
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

    public function manualStartAvailable(Event $event): bool
    {
        if ($this->activeSessionFor($event) !== null) {
            return false;
        }

        if ($this->scheduleService->scheduleForToday($event) === null) {
            return false;
        }

        $schedule = $this->scheduleService->scheduleForToday($event);

        if ($schedule !== null && $this->scheduleService->hasSessionForScheduleToday($schedule)) {
            return false;
        }

        return $this->scheduleService->canStartSessionManually($event);
    }

    private function openSession(Event $event, EventDate $schedule, User $startedBy): EventSession
    {
        return DB::transaction(function () use ($event, $schedule, $startedBy): EventSession {
            $event->update(['status' => EventStatus::Live]);

            $session = EventSession::query()->create([
                'event_id' => $event->id,
                'event_date_id' => $schedule->id,
                'started_by' => $startedBy->id,
                'status' => EventSessionStatus::Active,
                'started_at' => now(),
            ]);

            $session->setRelation('event', $event);

            $this->qrTokenService->issueToken($session);

            return $session;
        });
    }
}
