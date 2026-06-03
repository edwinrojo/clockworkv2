<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Exceptions\CheckInException;
use App\Http\Requests\StoreManualAttendanceRequest;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\User;
use App\Services\Attendance\ManualAttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventAttendanceController extends Controller
{
    public function __construct(private ManualAttendanceService $manualAttendanceService) {}

    public function index(Request $request, Event $event): Response
    {
        $this->authorize('viewAttendances', $event);

        $event->load('venue:id,name')->loadCount('attendances');

        $attendances = Attendance::query()
            ->where('event_id', $event->id)
            ->with(['user.department:id,name', 'manualOverrideBy:id,first_name,last_name'])
            ->orderByDesc('checked_in_at')
            ->get()
            ->map(fn (Attendance $attendance) => [
                'id' => $attendance->id,
                'employee_name' => $attendance->user->name,
                'employee_number' => $attendance->user->employee_number,
                'department_name' => $attendance->user->department?->name,
                'checked_in_at' => $attendance->checked_in_at->toIso8601String(),
                'source' => $attendance->source->value,
                'status' => $attendance->status->value,
                'manual_override_reason' => $attendance->manual_override_reason,
                'manual_override_by_name' => $attendance->manualOverrideBy?->name,
            ]);

        $employees = User::query()
            ->where('role', UserRole::Employee)
            ->where('is_active', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'suffix', 'employee_number'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'employee_number' => $user->employee_number,
            ]);

        return Inertia::render('events/Attendances', [
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'venue_name' => $event->venue?->name,
                'attendances_count' => $event->attendances_count,
            ],
            'attendances' => $attendances,
            'employees' => $employees,
            'can' => [
                'manageAttendances' => $request->user()?->can('manageAttendances', $event) ?? false,
                'manageSession' => $request->user()?->can('manageSession', $event) ?? false,
            ],
        ]);
    }

    public function store(StoreManualAttendanceRequest $request, Event $event): RedirectResponse
    {
        $employee = User::query()->findOrFail($request->validated('user_id'));

        try {
            $this->manualAttendanceService->record(
                event: $event,
                employee: $employee,
                admin: $request->user(),
                reason: $request->validated('reason'),
                status: AttendanceStatus::from($request->validated('status')),
            );
        } catch (CheckInException $exception) {
            return back()->withErrors([
                'user_id' => $exception->getMessage(),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Attendance recorded manually.')]);

        return to_route('events.attendances', $event);
    }

    public function export(Request $request, Event $event): StreamedResponse
    {
        $this->authorize('viewAttendances', $event);

        $filename = 'attendances-'.str($event->title)->slug().'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($event): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Employee Number',
                'Name',
                'Department',
                'Checked In At',
                'Source',
                'Status',
                'Override Reason',
            ]);

            Attendance::query()
                ->where('event_id', $event->id)
                ->with(['user.department:id,name'])
                ->orderBy('checked_in_at')
                ->chunk(200, function ($rows) use ($handle): void {
                    foreach ($rows as $attendance) {
                        fputcsv($handle, [
                            $attendance->user->employee_number,
                            $attendance->user->name,
                            $attendance->user->department?->name,
                            $attendance->checked_in_at->toDateTimeString(),
                            $attendance->source->value,
                            $attendance->status->value,
                            $attendance->manual_override_reason,
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
