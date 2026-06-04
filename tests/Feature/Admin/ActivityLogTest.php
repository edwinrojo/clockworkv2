<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_audit_log(): void
    {
        $manager = User::factory()->eventManager()->create();

        ActivityLog::factory()->create([
            'user_id' => $manager->id,
            'action' => 'manual_attendance',
        ]);

        $this->actingAs($manager)
            ->get(route('audit-log.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('audit/Index')
                ->has('logs.data', 1));
    }
}
