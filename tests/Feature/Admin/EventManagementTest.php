<?php

namespace Tests\Feature\Admin;

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_manager_can_create_an_event(): void
    {
        $manager = User::factory()->eventManager()->create();
        $venue = Venue::factory()->create();

        $response = $this->actingAs($manager)->post(route('events.store'), [
            'title' => 'Monday Convocation',
            'description' => 'Weekly assembly',
            'venue_id' => $venue->id,
            'type' => EventType::Convocation->value,
            'status' => EventStatus::Scheduled->value,
            'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDay()->addHours(2)->format('Y-m-d H:i:s'),
            'check_in_opens_at' => now()->addDay()->subMinutes(30)->format('Y-m-d H:i:s'),
            'check_in_closes_at' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
            'qr_rotation_seconds' => 60,
            'duplicate_policy' => 'per_event',
        ]);

        $response->assertRedirect(route('events.index'));

        $this->assertDatabaseHas('events', [
            'title' => 'Monday Convocation',
            'venue_id' => $venue->id,
            'created_by' => $manager->id,
        ]);
    }

    public function test_viewer_can_list_events_but_cannot_create(): void
    {
        $viewer = User::factory()->viewer()->create();
        Event::factory()->create(['title' => 'Flag Raising']);

        $this->actingAs($viewer)
            ->get(route('events.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('events/Index')
                ->has('events', 1)
            );

        $this->actingAs($viewer)
            ->get(route('events.create'))
            ->assertForbidden();
    }

    public function test_event_with_sessions_cannot_be_deleted(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $event = Event::factory()->create();
        EventSession::factory()->create(['event_id' => $event->id]);

        $this->actingAs($admin)
            ->delete(route('events.destroy', $event))
            ->assertRedirect()
            ->assertSessionHasErrors('event');

        $this->assertModelExists($event);
    }
}
