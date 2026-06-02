<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_admin_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->eventManager()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_employees_cannot_visit_the_dashboard(): void
    {
        $user = User::factory()->employee()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertForbidden();
    }
}
