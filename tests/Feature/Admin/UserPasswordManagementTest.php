<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Notifications\MobileResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserPasswordManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_send_password_reset_to_employee(): void
    {
        Notification::fake();

        $admin = User::factory()->superAdmin()->create();
        $employee = User::factory()->employee()->create();

        $this->actingAs($admin)
            ->post(route('users.send-password-reset', $employee))
            ->assertRedirect();

        Notification::assertSentTo($employee, MobileResetPassword::class);
    }

    public function test_admin_can_set_temporary_password_and_revoke_tokens(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $employee = User::factory()->employee()->create();
        $employee->createToken('mobile');

        $this->actingAs($admin)
            ->post(route('users.set-password', $employee), [
                'password' => 'new-temp-password',
                'password_confirmation' => 'new-temp-password',
            ])
            ->assertRedirect();

        $employee->refresh();

        $this->assertTrue(Hash::check('new-temp-password', $employee->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
