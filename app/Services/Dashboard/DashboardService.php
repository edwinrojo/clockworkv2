<?php

namespace App\Services\Dashboard;

use App\Enums\EventSessionStatus;
use App\Enums\EventStatus;
use App\Models\Attendance;
use App\Models\Event;

class DashboardService
{
    /**
     * @return array{
     *     live_events_count: int,
     *     check_ins_today: int,
     *     scheduled_this_week: int,
     *     live_events: list<array<string, mixed>>,
     *     upcoming_events: list<array<string, mixed>>,
     *     recent_check_ins: list<array<string, mixed>>,
     * }
     */
    public function snapshot(): array
    {
        $liveEvents = Event::query()
            ->where('status', EventStatus::Live)
            ->with('venue:id,name')
            ->withCount('attendances')
            ->orderBy('starts_at')
            ->get();

        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        return [
            'live_events_count' => $liveEvents->count(),
            'check_ins_today' => Attendance::query()
                ->whereBetween('checked_in_at', [$todayStart, $todayEnd])
                ->count(),
            'scheduled_this_week' => Event::query()
                ->where('status', EventStatus::Scheduled)
                ->whereBetween('starts_at', [now(), now()->addDays(7)])
                ->count(),
            'live_events' => $liveEvents->map(fn (Event $event) => $this->liveEventRow($event))->all(),
            'upcoming_events' => $this->upcomingEvents(),
            'recent_check_ins' => $this->recentCheckIns(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function liveEventRow(Event $event): array
    {
        $activeSession = $event->sessions()
            ->where('status', EventSessionStatus::Active)
            ->exists();

        return [
            'id' => $event->id,
            'title' => $event->title,
            'venue_name' => $event->venue?->name,
            'attendances_count' => $event->attendances_count,
            'session_active' => $activeSession,
            'starts_at' => $event->starts_at->toIso8601String(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function upcomingEvents(): array
    {
        return Event::query()
            ->whereIn('status', [EventStatus::Scheduled, EventStatus::Draft])
            ->where('starts_at', '>=', now())
            ->with('venue:id,name')
            ->orderBy('starts_at')
            ->limit(5)
            ->get()
            ->map(fn (Event $event) => [
                'id' => $event->id,
                'title' => $event->title,
                'venue_name' => $event->venue?->name,
                'status' => $event->status->value,
                'status_label' => $event->status->label(),
                'starts_at' => $event->starts_at->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentCheckIns(): array
    {
        return Attendance::query()
            ->with([
                'user:id,first_name,middle_name,last_name,suffix,employee_number',
                'event:id,title',
            ])
            ->orderByDesc('checked_in_at')
            ->limit(10)
            ->get()
            ->map(fn (Attendance $attendance) => [
                'id' => $attendance->id,
                'employee_name' => $attendance->user->name,
                'employee_number' => $attendance->user->employee_number,
                'event_id' => $attendance->event_id,
                'event_title' => $attendance->event->title,
                'checked_in_at' => $attendance->checked_in_at->toIso8601String(),
                'status' => $attendance->status->value,
                'status_label' => $attendance->status->label(),
            ])
            ->all();
    }
}
