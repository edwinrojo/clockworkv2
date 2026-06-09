<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Event;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_index_can_be_filtered_by_search_and_role(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $employee = User::factory()->employee()->create([
            'first_name' => 'Unique',
            'last_name' => 'Employee',
            'email' => 'unique.employee@example.com',
        ]);
        User::factory()->eventManager()->create([
            'first_name' => 'Other',
            'last_name' => 'Manager',
        ]);

        $this->actingAs($admin)
            ->get(route('users.index', [
                'search' => 'Unique',
                'role' => UserRole::Employee->value,
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('users/Index')
                ->has('filters.search')
                ->has('users.data', 1)
                ->where('users.data.0.id', $employee->id));
    }

    public function test_events_index_can_be_filtered_by_status_and_search(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $venue = Venue::factory()->create();
        $matching = Event::factory()->create([
            'title' => 'Quarterly Assembly',
            'status' => 'scheduled',
            'venue_id' => $venue->id,
        ]);
        Event::factory()->create([
            'title' => 'Other Meeting',
            'status' => 'closed',
            'venue_id' => $venue->id,
        ]);

        $this->actingAs($admin)
            ->get(route('events.index', [
                'search' => 'Quarterly',
                'status' => 'scheduled',
                'venue_id' => $venue->id,
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('events/Index')
                ->has('events.data', 1)
                ->where('events.data.0.id', $matching->id));
    }

    public function test_departments_index_can_be_filtered_by_active_status(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $active = Department::factory()->create(['name' => 'Active Office', 'is_active' => true]);
        Department::factory()->create(['name' => 'Inactive Office', 'is_active' => false]);

        $this->actingAs($admin)
            ->get(route('departments.index', ['is_active' => '1', 'search' => 'Active']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('departments/Index')
                ->has('departments.data', 1)
                ->where('departments.data.0.id', $active->id));
    }

    public function test_reports_index_returns_paginated_events_in_date_range(): void
    {
        $manager = User::factory()->eventManager()->create();
        $event = Event::factory()->create([
            'title' => 'Monthly Report Event',
            'starts_at' => now()->subDays(3),
            'ends_at' => now()->subDays(3)->endOfDay(),
        ]);

        $this->actingAs($manager)
            ->get(route('reports.index', [
                'from' => now()->subDays(7)->format('Y-m-d'),
                'to' => now()->format('Y-m-d'),
                'search' => 'Monthly',
                'per_page' => 15,
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('reports/Index')
                ->has('events.data', 1)
                ->where('events.data.0.id', $event->id));
    }
}
