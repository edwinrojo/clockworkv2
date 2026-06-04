<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTokenRevocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_revoke_employee_mobile_tokens(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $employee = User::factory()->employee()->create();

        $employee->createToken('mobile');
        $employee->createToken('tablet');

        $this->actingAs($admin)
            ->post(route('users.revoke-tokens', $employee))
            ->assertRedirect();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_revokes_all_previous_tokens_for_one_device_policy(): void
    {
        $employee = User::factory()->employee()->create([
            'email' => 'employee@example.com',
        ]);

        $employee->createToken('old-phone');
        $employee->createToken('old-tablet');

        $this->postJson('/api/v1/auth/login', [
            'email' => 'employee@example.com',
            'password' => 'password',
            'device_name' => 'PG-DDS-100',
        ])->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $employee->id,
            'name' => 'PG-DDS-100',
        ]);
    }
}
