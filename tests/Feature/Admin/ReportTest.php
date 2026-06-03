<?php

namespace Tests\Feature\Admin;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_manager_can_view_reports_index(): void
    {
        $manager = User::factory()->eventManager()->create();
        Event::factory()->create();

        $this->actingAs($manager)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('reports/Index')
                ->has('events')
            );
    }

    public function test_event_manager_can_view_event_report(): void
    {
        $manager = User::factory()->eventManager()->create();
        $event = Event::factory()->live()->create();

        $this->actingAs($manager)
            ->get(route('reports.show', $event))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('reports/Show')
                ->has('totals')
                ->has('by_department')
            );
    }
}
