<?php

namespace Tests\Feature\Admin;

use App\Enums\EventSessionStatus;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\User;
use App\Services\Qr\QrTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesMobileCheckInScenario;
use Tests\TestCase;

class EventSessionTest extends TestCase
{
    use CreatesMobileCheckInScenario;
    use RefreshDatabase;

    public function test_event_manager_can_start_pause_resume_and_end_session(): void
    {
        $manager = User::factory()->eventManager()->create();
        $event = Event::factory()->create(['status' => EventStatus::Scheduled]);

        $this->actingAs($manager)
            ->post(route('events.session.start', $event))
            ->assertRedirect(route('events.live', $event));

        $event->refresh();
        $this->assertSame(EventStatus::Live, $event->status);

        $session = EventSession::query()->where('event_id', $event->id)->first();
        $this->assertNotNull($session);
        $this->assertSame(EventSessionStatus::Active, $session->status);

        $this->actingAs($manager)
            ->post(route('events.session.pause', $event))
            ->assertRedirect(route('events.live', $event));

        $session->refresh();
        $this->assertSame(EventSessionStatus::Paused, $session->status);

        $this->actingAs($manager)
            ->post(route('events.session.resume', $event))
            ->assertRedirect(route('events.live', $event));

        $session->refresh();
        $this->assertSame(EventSessionStatus::Active, $session->status);

        $this->actingAs($manager)
            ->post(route('events.session.end', $event))
            ->assertRedirect(route('events.live', $event));

        $session->refresh();
        $this->assertSame(EventSessionStatus::Ended, $session->status);
        $this->assertSame(EventStatus::Closed, $event->fresh()->status);
    }

    public function test_rotate_now_issues_new_cached_token(): void
    {
        $manager = User::factory()->eventManager()->create();
        $event = Event::factory()->live()->create();
        $session = EventSession::factory()->for($event)->create();

        $service = app(QrTokenService::class);
        $first = $service->issueToken($session);

        $this->actingAs($manager)
            ->post(route('events.session.rotate', $event))
            ->assertRedirect(route('events.live', $event));

        $current = $service->currentToken($session->fresh());
        $this->assertNotNull($current);
        $this->assertNotSame($first['plain'], $current['plain']);
    }

    public function test_mobile_check_in_works_after_session_start(): void
    {
        $scenario = $this->createMobileCheckInScenario();
        $scenario['session']->delete();
        $manager = User::factory()->eventManager()->create();

        $this->actingAs($manager)
            ->post(route('events.session.start', $scenario['event']))
            ->assertRedirect();

        $session = EventSession::query()
            ->where('event_id', $scenario['event']->id)
            ->where('status', EventSessionStatus::Active)
            ->first();

        $token = app(QrTokenService::class)->currentToken($session);
        $this->assertNotNull($token);

        $this->actingAs($scenario['employee'], 'sanctum')
            ->postJson('/api/v1/check-in', [
                'qr_token' => $token['plain'],
                'latitude' => 6.75,
                'longitude' => 125.35,
            ])
            ->assertCreated();
    }

    public function test_viewer_cannot_start_session(): void
    {
        $viewer = User::factory()->viewer()->create();
        $event = Event::factory()->create();

        $this->actingAs($viewer)
            ->post(route('events.session.start', $event))
            ->assertForbidden();
    }
}
