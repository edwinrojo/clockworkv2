<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\MobileResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobilePasswordResetBridgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_bridge_page_opens_deep_link_for_valid_query_params(): void
    {
        $this->get(route('mobile.password-reset', [
            'token' => 'reset-token',
            'email' => 'employee@example.com',
        ]))
            ->assertOk()
            ->assertSee('Open Clockwork app', false)
            ->assertSee('clockwork://reset-password?token=reset-token', false)
            ->assertSee('email=employee%40example.com', false);
    }

    public function test_bridge_page_requires_token_and_email(): void
    {
        $this->get(route('mobile.password-reset'))
            ->assertSessionHasErrors(['token', 'email']);
    }

    public function test_reset_email_uses_https_bridge_instead_of_custom_scheme(): void
    {
        $employee = User::factory()->employee()->create([
            'email' => 'employee@example.com',
        ]);

        $notification = new MobileResetPassword('reset-token');
        $mail = $notification->toMail($employee);

        $this->assertStringStartsWith('http', $mail->actionUrl);
        $this->assertStringContainsString(
            route('mobile.password-reset', [
                'token' => 'reset-token',
                'email' => 'employee@example.com',
            ]),
            $mail->actionUrl,
        );
        $this->assertStringNotContainsString('clockwork://', $mail->actionUrl);
    }
}
