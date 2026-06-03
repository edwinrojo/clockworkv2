<?php

namespace App\Http\Controllers;

use App\Enums\EventSessionStatus;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Event;
use App\Services\Attendance\ExpectedRosterService;
use App\Services\Event\EventSessionService;
use App\Services\Qr\QrTokenService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventLiveController extends Controller
{
    public function __construct(
        private EventSessionService $sessionService,
        private QrTokenService $qrTokenService,
        private ExpectedRosterService $rosterService,
    ) {}

    public function show(Request $request, Event $event): Response
    {
        $this->authorize('view', $event);

        $event->load('venue:id,name');
        $event->loadCount('attendances');

        $departmentId = $request->string('department_id')->toString() ?: null;

        $session = $this->sessionService->activeSessionFor($event);
        $session?->load('starter:id,first_name,last_name');

        $qrPreview = null;
        if ($session !== null && $session->status === EventSessionStatus::Active) {
            $token = $this->qrTokenService->currentToken($session);
            if ($token !== null) {
                $qrPreview = [
                    'expires_at' => $token['expires_at'],
                    'seconds_remaining' => max(0, $token['expires_at'] - now()->getTimestamp()),
                ];
            }
        }

        $recentAttendances = Attendance::query()
            ->where('event_id', $event->id)
            ->with('user:id,first_name,middle_name,last_name,suffix,employee_number')
            ->orderByDesc('checked_in_at')
            ->limit(15)
            ->get()
            ->map(fn (Attendance $attendance) => [
                'id' => $attendance->id,
                'employee_name' => $attendance->user->name,
                'employee_number' => $attendance->user->employee_number,
                'checked_in_at' => $attendance->checked_in_at->toIso8601String(),
                'source' => $attendance->source->value,
                'status' => $attendance->status->value,
                'status_label' => $attendance->status->label(),
            ]);

        return Inertia::render('events/Live', [
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'status' => $event->status->value,
                'status_label' => $event->status->label(),
                'venue_name' => $event->venue?->name,
                'qr_rotation_seconds' => $event->qr_rotation_seconds,
                'attendances_count' => $event->attendances_count,
                'display_url' => route('display.show', $event->display_secret),
                'has_display_pin' => $event->display_pin_hash !== null,
            ],
            'session' => $session ? [
                'id' => $session->id,
                'status' => $session->status->value,
                'status_label' => $session->status->label(),
                'started_at' => $session->started_at->toIso8601String(),
                'started_by_name' => $session->starter?->name,
            ] : null,
            'qr' => $qrPreview,
            'recentAttendances' => $recentAttendances,
            'rosterStats' => $this->rosterService->counts($event, $departmentId),
            'missingEmployees' => $this->rosterService->missingEmployees($event, $departmentId),
            'departments' => Department::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'filters' => [
                'department_id' => $departmentId,
            ],
            'can' => [
                'manageSession' => $request->user()?->can('manageSession', $event) ?? false,
                'viewAttendances' => $request->user()?->can('viewAttendances', $event) ?? false,
                'manageAttendances' => $request->user()?->can('manageAttendances', $event) ?? false,
            ],
        ]);
    }
}
