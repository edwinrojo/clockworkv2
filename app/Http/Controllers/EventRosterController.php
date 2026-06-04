<?php

namespace App\Http\Controllers;

use App\Enums\EventRosterScope;
use App\Enums\UserRole;
use App\Http\Requests\UpdateEventRosterRequest;
use App\Models\Department;
use App\Models\Event;
use App\Models\User;
use App\Support\Admin\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventRosterController extends Controller
{
    public function edit(Request $request, Event $event): Response
    {
        $this->authorize('update', $event);

        $event->load(['rosterDepartments:id,name', 'rosterUsers:id,first_name,middle_name,last_name,suffix,employee_number']);

        return Inertia::render('events/Roster', [
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'status_label' => $event->status->label(),
            ],
            'roster' => [
                'scope' => $event->roster_scope->value,
                'scope_label' => $event->roster_scope->label(),
                'department_ids' => $event->rosterDepartments->pluck('id')->all(),
                'user_ids' => $event->rosterUsers->pluck('id')->all(),
            ],
            'scopes' => collect(EventRosterScope::cases())->map(fn (EventRosterScope $scope) => [
                'value' => $scope->value,
                'label' => $scope->label(),
            ])->all(),
            'departments' => Department::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'employees' => User::query()
                ->where('role', UserRole::Employee)
                ->where('is_active', true)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'middle_name', 'last_name', 'suffix', 'employee_number'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'employee_number' => $user->employee_number,
                ]),
        ]);
    }

    public function update(UpdateEventRosterRequest $request, Event $event): RedirectResponse
    {
        $data = $request->validated();

        $event->update([
            'roster_scope' => $data['roster_scope'],
        ]);

        if ($data['roster_scope'] === EventRosterScope::Departments->value) {
            $event->rosterDepartments()->sync($data['department_ids'] ?? []);
            $event->rosterUsers()->sync([]);
        } elseif ($data['roster_scope'] === EventRosterScope::Employees->value) {
            $event->rosterUsers()->sync($data['user_ids'] ?? []);
            $event->rosterDepartments()->sync([]);
        } else {
            $event->rosterDepartments()->sync([]);
            $event->rosterUsers()->sync([]);
        }

        ActivityLogger::log($request, 'event_roster_updated', $event, [
            'roster_scope' => $data['roster_scope'],
            'department_ids' => $data['department_ids'] ?? [],
            'user_ids' => $data['user_ids'] ?? [],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Expected roster updated.')]);

        return to_route('events.roster.edit', $event);
    }
}
