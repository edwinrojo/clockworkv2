<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_operations_dashboard(): void
    {
        $manager = User::factory()->eventManager()->create();

        $this->actingAs($manager)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->has('live_events_count')
                ->has('check_ins_today')
                ->has('recent_check_ins'));
    }
}
