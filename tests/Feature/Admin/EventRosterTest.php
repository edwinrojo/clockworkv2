<?php

namespace Tests\Feature\Admin;

use App\Enums\EventRosterScope;
use App\Enums\EventStatus;
use App\Models\Department;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventRosterTest extends TestCase
{
    use RefreshDatabase;

    public function test_coordinator_can_limit_expected_roster_to_departments(): void
    {
        $manager = User::factory()->eventManager()->create();
        $hr = Department::factory()->create(['name' => 'Human Resources']);
        $finance = Department::factory()->create(['name' => 'Finance']);

        User::factory()->employee()->create([
            'department_id' => $hr->id,
            'employee_number' => 'EMP-HR-1',
        ]);
        User::factory()->employee()->create([
            'department_id' => $finance->id,
            'employee_number' => 'EMP-FIN-1',
        ]);

        $event = Event::factory()->live()->create([
            'roster_scope' => EventRosterScope::AllActiveEmployees,
        ]);

        $this->actingAs($manager)
            ->put(route('events.roster.update', $event), [
                'roster_scope' => EventRosterScope::Departments->value,
                'department_ids' => [$hr->id],
            ])
            ->assertRedirect(route('events.roster.edit', $event));

        $event->refresh();

        $this->assertSame(EventRosterScope::Departments, $event->roster_scope);
        $this->assertCount(1, $event->rosterDepartments);

        $this->actingAs($manager)
            ->get(route('events.live', $event))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('events/Live')
                ->where('rosterStats.expected', 1));
    }

    public function test_live_page_shows_zero_expected_when_department_roster_is_empty(): void
    {
        $manager = User::factory()->eventManager()->create();
        User::factory()->employee()->create();

        $event = Event::factory()->live()->create([
            'roster_scope' => EventRosterScope::Departments,
            'status' => EventStatus::Live,
        ]);

        $this->actingAs($manager)
            ->get(route('events.live', $event))
            ->assertInertia(fn ($page) => $page->where('rosterStats.expected', 0));
    }
}
