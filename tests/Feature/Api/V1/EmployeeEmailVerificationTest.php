<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Notifications\EmployeeEmailVerificationCode;
use App\Services\Auth\EmployeeEmailVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmployeeEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_employee_cannot_login(): void
    {
        User::factory()->employee()->unverified()->create([
            'email' => 'unverified@clockwork.test',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'unverified@clockwork.test',
            'password' => 'password',
        ])
            ->assertForbidden()
            ->assertJsonPath('code', 'EMAIL_NOT_VERIFIED');
    }

    public function test_employee_can_verify_email_with_code_and_then_login(): void
    {
        $employee = User::factory()->employee()->unverified()->create([
            'email' => 'verify.me@clockwork.test',
        ]);

        Cache::put(
            'employee_email_verify:'.$employee->id,
            Hash::make('123456'),
            now()->addMinutes(30),
        );

        $this->postJson('/api/v1/auth/email-verification/verify', [
            'email' => 'verify.me@clockwork.test',
            'code' => '123456',
        ])
            ->assertOk()
            ->assertJsonPath('data.message', 'Email confirmed. You can sign in now.');

        $this->assertNotNull($employee->refresh()->email_verified_at);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'verify.me@clockwork.test',
            'password' => 'password',
        ])->assertOk();
    }

    public function test_employee_can_request_new_code_with_email_and_password(): void
    {
        Notification::fake();

        $employee = User::factory()->employee()->unverified()->create([
            'email' => 'resend@clockwork.test',
        ]);

        $this->postJson('/api/v1/auth/email-verification/send', [
            'email' => 'resend@clockwork.test',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath(
                'data.message',
                'If your account needs verification, a new code has been sent to your email.',
            );

        Notification::assertSentTo($employee, EmployeeEmailVerificationCode::class);
    }

    public function test_send_code_does_not_reveal_invalid_credentials(): void
    {
        Notification::fake();

        User::factory()->employee()->unverified()->create([
            'email' => 'resend@clockwork.test',
        ]);

        $this->postJson('/api/v1/auth/email-verification/send', [
            'email' => 'resend@clockwork.test',
            'password' => 'wrong-password',
        ])->assertOk();

        Notification::assertNothingSent();
    }

    public function test_send_code_dispatches_notification_and_stores_code_in_cache(): void
    {
        Notification::fake();

        $employee = User::factory()->employee()->unverified()->create([
            'email' => 'mail.test@clockwork.test',
        ]);

        app(EmployeeEmailVerificationService::class)->sendCode($employee);

        $this->assertTrue(Cache::has('employee_email_verify:'.$employee->id));
        Notification::assertSentTo($employee, EmployeeEmailVerificationCode::class);
    }
}
