<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Notifications\MobileResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class MobilePasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_request_password_reset_link(): void
    {
        Notification::fake();

        $employee = User::factory()->employee()->create([
            'email' => 'employee@example.com',
        ]);

        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'employee@example.com',
        ])
            ->assertOk()
            ->assertJsonPath('data.message', fn (string $message): bool => $message !== '');

        Notification::assertSentTo($employee, MobileResetPassword::class);
    }

    public function test_forgot_password_does_not_reveal_unknown_emails(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'missing@example.com',
        ])->assertOk();

        Notification::assertNothingSent();
    }

    public function test_employee_can_reset_password_and_tokens_are_revoked(): void
    {
        $employee = User::factory()->employee()->create([
            'email' => 'employee@example.com',
        ]);

        $employee->createToken('mobile');
        $token = Password::createToken($employee);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'employee@example.com',
            'token' => $token,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
            ->assertOk();

        $employee->refresh();

        $this->assertTrue(Hash::check('new-password-123', $employee->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_admin_cannot_reset_password_via_mobile_api(): void
    {
        $admin = User::factory()->eventManager()->create([
            'email' => 'manager@example.com',
        ]);

        $token = Password::createToken($admin);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'manager@example.com',
            'token' => $token,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertStatus(422);
    }
}
