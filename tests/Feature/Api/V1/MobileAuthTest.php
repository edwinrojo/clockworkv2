<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_login_and_receive_bearer_token(): void
    {
        $employee = User::factory()->employee()->create([
            'email' => 'employee@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'employee@example.com',
            'password' => 'password',
            'device_id' => 'test-device-001',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', 'employee@example.com')
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'name']]]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $employee->id,
            'name' => 'mobile',
        ]);
    }

    public function test_admin_cannot_login_via_mobile_api(): void
    {
        User::factory()->eventManager()->create([
            'email' => 'manager@example.com',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'manager@example.com',
            'password' => 'password',
            'device_id' => 'test-device-001',
        ])
            ->assertForbidden()
            ->assertJsonPath('code', 'UNAUTHORIZED');
    }

    public function test_inactive_employee_cannot_login(): void
    {
        User::factory()->employee()->inactive()->create([
            'email' => 'inactive@example.com',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'inactive@example.com',
            'password' => 'password',
            'device_id' => 'test-device-001',
        ])
            ->assertForbidden()
            ->assertJsonPath('code', 'ACCOUNT_INACTIVE');
    }

    public function test_employee_can_logout_and_revoke_token(): void
    {
        $employee = User::factory()->employee()->create();
        $token = $employee->createToken('mobile')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/v1/profile')
            ->assertUnauthorized();
    }

    public function test_profile_requires_authentication(): void
    {
        $this->getJson('/api/v1/profile')->assertUnauthorized();
    }

    public function test_authenticated_employee_can_view_profile(): void
    {
        $employee = User::factory()->employee()->create();
        Sanctum::actingAs($employee);

        $this->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('data.user.id', $employee->id);
    }
}
