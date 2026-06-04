<?php

namespace App\Services\Reports;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Event;
use App\Services\Attendance\ExpectedRosterService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceReportService
{
    public function __construct(private ExpectedRosterService $rosterService) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function eventsInRange(?string $from = null, ?string $to = null): Collection
    {
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : now()->subDays(30)->startOfDay();
        $toDate = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();

        return Event::query()
            ->with('venue:id,name')
            ->withCount('attendances')
            ->whereBetween('starts_at', [$fromDate, $toDate])
            ->orderByDesc('starts_at')
            ->get()
            ->map(fn (Event $event) => $this->eventSummaryRow($event));
    }

    /**
     * @return array<string, mixed>
     */
    public function eventDetail(Event $event): array
    {
        $event->load('venue:id,name')->loadCount('attendances');

        $byStatus = Attendance::query()
            ->where('event_id', $event->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $byDepartment = Attendance::query()
            ->where('event_id', $event->id)
            ->join('users', 'attendances.user_id', '=', 'users.id')
            ->leftJoin('departments', 'users.department_id', '=', 'departments.id')
            ->selectRaw('departments.id as department_id, departments.name as department_name, count(*) as total')
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'department_id' => $row->department_id,
                'department_name' => $row->department_name ?? 'Unassigned',
                'total' => (int) $row->total,
            ]);

        $expectedEmployees = $this->rosterService->expectedCount($event);

        return [
            'event' => $this->eventSummaryRow($event),
            'totals' => [
                'expected_employees' => $expectedEmployees,
                'checked_in' => $event->attendances_count,
                'missing' => max(0, $expectedEmployees - $event->attendances_count),
                'present' => (int) ($byStatus[AttendanceStatus::Present->value] ?? 0),
                'late' => (int) ($byStatus[AttendanceStatus::Late->value] ?? 0),
                'manual_override' => (int) ($byStatus[AttendanceStatus::ManualOverride->value] ?? 0),
                'attendance_rate' => $expectedEmployees > 0
                    ? round(($event->attendances_count / $expectedEmployees) * 100, 1)
                    : 0,
            ],
            'by_department' => $byDepartment,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function eventSummaryRow(Event $event): array
    {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'venue_name' => $event->venue?->name,
            'status' => $event->status->value,
            'status_label' => $event->status->label(),
            'starts_at' => $event->starts_at->toIso8601String(),
            'ends_at' => $event->ends_at->toIso8601String(),
            'attendances_count' => $event->attendances_count ?? 0,
        ];
    }

    /**
     * @return Collection<int, Department>
     */
    public function departmentOptions(): Collection
    {
        return Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
