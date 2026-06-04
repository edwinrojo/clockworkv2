<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceStatus;
use App\Enums\EventRosterScope;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ExpectedRosterService
{
    /**
     * @return Collection<int, array{id: string, name: string, employee_number: string|null, department_name: string|null}>
     */
    public function missingEmployees(Event $event, ?string $departmentId = null): Collection
    {
        $checkedInUserIds = Attendance::query()
            ->where('event_id', $event->id)
            ->pluck('user_id');

        return $this->expectedEmployeesQuery($event)
            ->when($departmentId !== null, fn (Builder $query) => $query->where('department_id', $departmentId))
            ->whereNotIn('id', $checkedInUserIds)
            ->with('department:id,name')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'employee_number' => $user->employee_number,
                'department_name' => $user->department?->name,
            ]);
    }

    /**
     * @return array{
     *     expected: int,
     *     checked_in: int,
     *     missing: int,
     *     present: int,
     *     late: int,
     *     manual_override: int,
     *     roster_scope: string,
     *     roster_scope_label: string,
     * }
     */
    public function counts(Event $event, ?string $departmentId = null): array
    {
        $expectedQuery = $this->expectedEmployeesQuery($event);

        if ($departmentId !== null) {
            $expectedQuery->where('department_id', $departmentId);
        }

        $expected = $expectedQuery->count();

        $attendanceQuery = Attendance::query()->where('event_id', $event->id);

        if ($departmentId !== null) {
            $attendanceQuery->whereHas('user', fn (Builder $query) => $query->where('department_id', $departmentId));
        }

        $checkedIn = (clone $attendanceQuery)->count();

        return [
            'expected' => $expected,
            'checked_in' => $checkedIn,
            'missing' => max(0, $expected - $checkedIn),
            'present' => (clone $attendanceQuery)->where('status', AttendanceStatus::Present)->count(),
            'late' => (clone $attendanceQuery)->where('status', AttendanceStatus::Late)->count(),
            'manual_override' => (clone $attendanceQuery)->where('status', AttendanceStatus::ManualOverride)->count(),
            'roster_scope' => $event->roster_scope->value,
            'roster_scope_label' => $event->roster_scope->label(),
        ];
    }

    public function expectedCount(Event $event): int
    {
        return $this->expectedEmployeesQuery($event)->count();
    }

    /**
     * @return Builder<User>
     */
    public function expectedEmployeesQuery(Event $event): Builder
    {
        $event->loadMissing(['rosterDepartments:id', 'rosterUsers:id']);

        $query = User::query()
            ->where('role', UserRole::Employee)
            ->where('is_active', true);

        return match ($event->roster_scope) {
            EventRosterScope::AllActiveEmployees => $query,
            EventRosterScope::Departments => $query->whereIn(
                'department_id',
                $event->rosterDepartments->pluck('id'),
            ),
            EventRosterScope::Employees => $query->whereIn(
                'id',
                $event->rosterUsers->pluck('id'),
            ),
        };
    }
}
