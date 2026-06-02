<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_employees_cannot_access_the_dashboard(): void
    {
        $employee = User::factory()->employee()->create();

        $this->actingAs($employee)
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_viewers_can_access_the_dashboard(): void
    {
        $viewer = User::factory()->viewer()->create();

        $this->actingAs($viewer)
            ->get(route('dashboard'))
            ->assertOk();
    }
}
