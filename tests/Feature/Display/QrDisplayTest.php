<?php

namespace Tests\Feature\Display;

use App\Enums\EventSessionStatus;
use App\Models\Event;
use App\Models\EventSession;
use App\Services\Qr\QrTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_display_page_loads_for_valid_secret(): void
    {
        $event = Event::factory()->live()->create();

        $this->get(route('display.show', $event->display_secret))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('display/Show')
                ->where('event.title', $event->title)
            );
    }

    public function test_token_endpoint_returns_active_qr_when_session_running(): void
    {
        $event = Event::factory()->live()->create();
        $session = EventSession::factory()->for($event)->create([
            'status' => EventSessionStatus::Active,
        ]);

        $issued = app(QrTokenService::class)->issueToken($session);

        $this->getJson(route('display.token', $event->display_secret))
            ->assertOk()
            ->assertJsonPath('active', true)
            ->assertJsonPath('qr_token', $issued['plain']);
    }

    public function test_token_endpoint_reports_inactive_without_session(): void
    {
        $event = Event::factory()->live()->create();

        $this->getJson(route('display.token', $event->display_secret))
            ->assertOk()
            ->assertJsonPath('active', false);
    }
}
