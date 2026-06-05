<?php

namespace App\Services\Event;

use App\Enums\EventSessionStatus;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventDate;
use App\Models\EventSession;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class EventScheduleService
{
    public function scheduleForToday(Event $event): ?EventDate
    {
        return $this->scheduleForDate($event, today());
    }

    public function scheduleForDate(Event $event, CarbonInterface $date): ?EventDate
    {
        return $event->dates()
            ->whereDate('event_date', $date->toDateString())
            ->first();
    }

    public function canStartSessionManually(Event $event, ?CarbonInterface $at = null): bool
    {
        $at ??= now();
        $schedule = $this->scheduleForDate($event, $at);

        if ($schedule === null) {
            return false;
        }

        return $at->greaterThanOrEqualTo($schedule->checkInOpensAt());
    }

    public function assertCanStartSessionManually(Event $event): EventDate
    {
        $schedule = $this->scheduleForToday($event);

        if ($schedule === null) {
            throw ValidationException::withMessages([
                'session' => __('There is no scheduled event date for today.'),
            ]);
        }

        if (! now()->greaterThanOrEqualTo($schedule->checkInOpensAt())) {
            throw ValidationException::withMessages([
                'session' => __('Check-in opens at :time. Manual start is disabled until then.', [
                    'time' => $schedule->checkInOpensAt()->format('g:i A'),
                ]),
            ]);
        }

        if ($event->sessions()->where('status', EventSessionStatus::Active)->exists()) {
            throw ValidationException::withMessages([
                'session' => __('This event already has an active check-in session.'),
            ]);
        }

        if ($this->hasSessionForScheduleToday($schedule)) {
            throw ValidationException::withMessages([
                'session' => __('A check-in session already ran for this event date.'),
            ]);
        }

        return $schedule;
    }

    public function hasSessionForScheduleToday(EventDate $schedule): bool
    {
        return EventSession::query()
            ->where('event_date_id', $schedule->id)
            ->whereDate('started_at', today()->toDateString())
            ->exists();
    }

    /**
     * @return list<EventDate>
     */
    public function schedulesReadyForAutoStart(?CarbonInterface $at = null): array
    {
        $at ??= now();

        return EventDate::query()
            ->with('event')
            ->whereDate('event_date', $at->toDateString())
            ->whereHas('event', function ($query): void {
                $query->whereIn('status', [EventStatus::Scheduled, EventStatus::Live]);
            })
            ->get()
            ->filter(function (EventDate $schedule) use ($at): bool {
                $event = $schedule->event;

                if ($event === null) {
                    return false;
                }

                if ($event->sessions()->where('status', EventSessionStatus::Active)->exists()) {
                    return false;
                }

                if ($this->hasSessionForScheduleToday($schedule)) {
                    return false;
                }

                return $at->greaterThanOrEqualTo($schedule->checkInOpensAt());
            })
            ->values()
            ->all();
    }

    public function resolveStarterForAutoStart(Event $event): User
    {
        return User::query()->findOrFail($event->created_by);
    }

    public function syncEventDates(Event $event, bool $isMultiDay, array $dates): void
    {
        $event->dates()->delete();

        foreach ($dates as $row) {
            $event->dates()->create([
                'event_date' => $row['event_date'],
                'check_in_time' => $this->normalizeTime($row['check_in_time']),
                'check_out_time' => $this->normalizeTime($row['check_out_time']),
                'late_cutoff_time' => $this->normalizeTime($row['late_cutoff_time']),
            ]);
        }

        $event->update([
            'is_multi_day' => $isMultiDay,
            'starts_at' => $dates[0]['event_date'].' 00:00:00',
            'ends_at' => $dates[array_key_last($dates)]['event_date'].' 23:59:59',
            'check_in_opens_at' => null,
            'check_in_closes_at' => null,
        ]);
    }

    private function normalizeTime(string $time): string
    {
        return strlen($time) === 5 ? "{$time}:00" : $time;
    }
}
